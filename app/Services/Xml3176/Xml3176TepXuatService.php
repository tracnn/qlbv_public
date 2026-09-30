<?php

namespace App\Services\Xml3176;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Yeu cau xuat tep chay nen cua man XML3176 (spec 2026-09-30).
 *
 * Prod KHONG chay Laravel scheduler (Kernel::schedule() trong, khong gi goi schedule:run), nen
 * don tep cu va danh dau yeu cau treo lam MOI KHI tao yeu cau hoac lay danh sach.
 */
class Xml3176TepXuatService
{
    /** Thu muc tren disk 'local' (storage/app). */
    const THU_MUC = 'xml3176-tep-xuat';

    /**
     * Tao yeu cau xuat danh sach loi. Nguoi nay da co yeu cau DANG CHO/DANG TAO voi bo loc
     * giong het thi tra yeu cau do: moi lan xuat ngay lon ton 12-30 phut va hang doi chi co
     * mot worker, bam lap nam lan thi nguoi sau cho hon hai tieng.
     *
     * @return array ['yeuCau' => Xml3176TepXuat, 'trung' => bool]
     */
    public function taoYeuCau(int $userId, array $boLoc): array
    {
        $this->donDep();
        $this->danhDauTreo();

        $chuan = self::chuanHoa($boLoc);

        $dangChay = Xml3176TepXuat::where('user_id', $userId)
            ->where('loai', Xml3176TepXuat::LOAI_LOI)
            ->whereIn('trang_thai', [Xml3176TepXuat::CHO, Xml3176TepXuat::DANG_TAO])
            ->get();

        foreach ($dangChay as $y) {
            if (self::chuanHoa((array) $y->bo_loc) === $chuan) {
                return ['yeuCau' => $y, 'trung' => true];
            }
        }

        $y = Xml3176TepXuat::create([
            'user_id' => $userId,
            'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => $chuan,
            'trang_thai' => Xml3176TepXuat::CHO,
        ]);

        // Day SAU khi dong da ghi: job doc dong theo id.
        XuatTepLoiXml3176Job::dispatch($y->id)
            ->onConnection(config('xml3176.xuat_tep_connection'))
            ->onQueue(config('xml3176.xuat_tep_queue_name'));

        return ['yeuCau' => $y, 'trung' => false];
    }

    /**
     * Cac yeu cau cua nguoi nay trong so ngay giu tep, moi nhat truoc.
     *
     * @return array mang cac mang ['id','tao_luc','bo_loc','trang_thai','so_truoc','so_phut','kich_thuoc','loi']
     */
    public function danhSachCua(int $userId): array
    {
        $this->donDep();
        $this->danhDauTreo();

        $ds = Xml3176TepXuat::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->get();

        return $ds->map(function (Xml3176TepXuat $y) {
            return [
                'id' => $y->id,
                'tao_luc' => $y->created_at->format('d/m/Y H:i'),
                'bo_loc' => self::tomTatBoLoc((array) $y->bo_loc),
                'trang_thai' => $y->trang_thai,
                // Dem tren MOI nguoi dung: hang doi chi co mot worker.
                'so_truoc' => $y->trang_thai === Xml3176TepXuat::CHO
                    ? Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::CHO)->where('id', '<', $y->id)->count()
                    + Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::DANG_TAO)->count()
                    : null,
                'so_phut' => $y->trang_thai === Xml3176TepXuat::DANG_TAO && $y->bat_dau_luc
                    ? $y->bat_dau_luc->diffInMinutes(Carbon::now())
                    : null,
                'kich_thuoc' => $y->kich_thuoc,
                'loi' => $y->loi,
            ];
        })->all();
    }

    /**
     * Yeu cau cua CHINH nguoi nay, da xong va tep con tren dia; nguoc lai null. Controller tra
     * 404 cho moi truong hop null - khong phan biet, de khong lo yeu cau cua nguoi khac.
     */
    public function timDeTai(int $userId, int $id)
    {
        $y = Xml3176TepXuat::where('id', $id)->where('user_id', $userId)->first();

        if ($y === null || $y->trang_thai !== Xml3176TepXuat::XONG || empty($y->duong_dan)) {
            return null;
        }

        return Storage::disk('local')->exists($y->duong_dan) ? $y : null;
    }

    /** Xoa yeu cau cu hon so ngay giu tep, KEM tep cua no. */
    public function donDep(): void
    {
        $moc = Carbon::now()->subDays((int) config('xml3176.xuat_tep_giu_ngay', 7));

        foreach (Xml3176TepXuat::where('created_at', '<', $moc)->get() as $y) {
            if ($y->duong_dan) {
                Storage::disk('local')->delete($y->duong_dan);
            }
            $y->delete();
        }
    }

    /**
     * Dang tao qua so phut treo thi coi nhu worker da dung (trien khai trung luc xuat, het RAM
     * - loi fatal cua PHP khong di qua failed()). Khong co buoc nay dong kep mai o dang_tao.
     */
    public function danhDauTreo(): void
    {
        $moc = Carbon::now()->subMinutes((int) config('xml3176.xuat_tep_treo_phut', 90));

        Xml3176TepXuat::where('trang_thai', Xml3176TepXuat::DANG_TAO)
            ->where('bat_dau_luc', '<', $moc)
            ->update([
                'trang_thai' => Xml3176TepXuat::LOI,
                'loi' => 'Quá thời gian, có thể dịch vụ xuất đã dừng. Bấm tạo lại.',
            ]);
    }

    public static function duongDanTep(Xml3176TepXuat $y): string
    {
        return self::THU_MUC . '/' . $y->id . '.xlsx';
    }

    public static function tenTepTai(Xml3176TepXuat $y): string
    {
        $boLoc = (array) $y->bo_loc;
        $tu = isset($boLoc['date_from']) ? preg_replace('/\D/', '', substr((string) $boLoc['date_from'], 0, 10)) : '';

        return 'xml3176_loi_' . $tu . '_' . $y->created_at->format('YmdHis') . '.xlsx';
    }

    /** Tom tat cac bo loc CO GIA TRI de nguoi dung nhan ra yeu cau cua minh. */
    public static function tomTatBoLoc(array $boLoc): string
    {
        $phan = [];

        if (!empty($boLoc['date_from']) && !empty($boLoc['date_to'])) {
            $dinhDang = function ($s) {
                return Carbon::parse(substr((string) $s, 0, 10))->format('d/m/Y');
            };
            $tu = $dinhDang($boLoc['date_from']);
            $den = $dinhDang($boLoc['date_to']);
            $phan[] = $tu === $den ? $tu : $tu . ' – ' . $den;
        }

        foreach ($boLoc as $khoa => $giaTri) {
            if (in_array($khoa, ['date_from', 'date_to'], true) || $giaTri === null || $giaTri === '') {
                continue;
            }
            $phan[] = $khoa . '=' . (is_array($giaTri) ? implode(',', $giaTri) : $giaTri);
        }

        return implode(' · ', $phan);
    }

    /** Sap khoa va bo gia tri rong de so sanh "bo loc giong het" khong phu thuoc thu tu. */
    private static function chuanHoa(array $boLoc): array
    {
        $boLoc = array_filter($boLoc, function ($v) {
            return $v !== null && $v !== '';
        });
        ksort($boLoc);

        return $boLoc;
    }
}
