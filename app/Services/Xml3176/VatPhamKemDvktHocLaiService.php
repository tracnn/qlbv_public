<?php

namespace App\Services\Xml3176;

use App\Jobs\HocVatPhamKemDvktJob;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Luot hoc bang tra vat pham kem DVKT me: yeu cau tu man quan tri (chay nen), chay that
 * (dung chung cho job va lenh dong lenh), danh dau treo, mo ta trang thai.
 *
 * Hoc ton 2-5 phut tren ~1,4 trieu dong XML3 - qua gioi han 120 giay cua request web nen
 * phai chay nen, tren ket noi/hang doi xuat tep (retry_after 3600) da co dich vu NSSM.
 * PHP Windows khong co pcntl nen $timeout cua job khong duoc thi hanh: treo duoc phat hien
 * bang moc thoi gian (khuon Xml3176TepXuatService::danhDauTreo).
 */
class VatPhamKemDvktHocLaiService
{
    const PHUT_TREO = 60;

    /** @var VatPhamKemDvktLearner */
    private $learner;

    public function __construct(VatPhamKemDvktLearner $learner)
    {
        $this->learner = $learner;
    }

    public function yeuCau($userId): array
    {
        $this->danhDauTreo();

        $dang = Xml3176VpkdLanHoc::whereIn('trang_thai', [Xml3176VpkdLanHoc::CHO, Xml3176VpkdLanHoc::DANG_CHAY])
            ->orderBy('id', 'desc')->first();
        if ($dang !== null) {
            return ['lan_hoc' => $dang, 'moi' => false];
        }

        $lan = Xml3176VpkdLanHoc::create(['user_id' => $userId, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);

        // Day SAU khi dong da ghi: job doc dong theo id.
        try {
            HocVatPhamKemDvktJob::dispatch($lan->id)
                ->onConnection(config('xml3176.xuat_tep_connection'))
                ->onQueue(config('xml3176.xuat_tep_queue_name'));
        } catch (\Throwable $e) {
            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => 'Không xếp được vào hàng đợi, vui lòng thử lại.', 'xong_luc' => Carbon::now()]);
            throw $e;
        }

        return ['lan_hoc' => $lan, 'moi' => true];
    }

    public function thucHien(Xml3176VpkdLanHoc $lan, float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $lan->update(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()]);

        try {
            $hoc = $this->learner->hoc($nguong, $toiThieu, $tuNgay, (float) config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien', 0.5));

            $ketQua = DB::transaction(function () use ($hoc, $lan) {
                $g = $this->learner->ghi($hoc['dong']);
                $g['so_ung_vien'] = $this->learner->ghiUngVien($hoc['ung_vien'], $lan->id);

                return $g;
            });

            $soCap = [];
            foreach (array_keys($hoc['so_vat_pham']) as $loai) {
                $soCap[$loai] = count(array_filter($hoc['dong'], function ($d) use ($loai) {
                    return $d['loai'] === $loai;
                }));
            }
            $ketQua = ['so_vat_pham' => $hoc['so_vat_pham'], 'so_cap' => $soCap] + $ketQua;

            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::XONG, 'ket_qua' => $ketQua, 'xong_luc' => Carbon::now()]);

            return $ketQua;
        } catch (\Throwable $e) {
            $lan->update(['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => mb_substr($e->getMessage(), 0, 2000), 'xong_luc' => Carbon::now()]);
            throw $e;
        }
    }

    public function danhDauTreo(): int
    {
        $moc = Carbon::now()->subMinutes(self::PHUT_TREO);
        $loi = ['trang_thai' => Xml3176VpkdLanHoc::LOI, 'loi' => 'Treo quá 60 phút', 'xong_luc' => Carbon::now()];

        $n = Xml3176VpkdLanHoc::where('trang_thai', Xml3176VpkdLanHoc::DANG_CHAY)->where('bat_dau_luc', '<', $moc)->update($loi);
        $n += Xml3176VpkdLanHoc::where('trang_thai', Xml3176VpkdLanHoc::CHO)->where('created_at', '<', $moc)->update($loi);

        return $n;
    }

    public function ganNhat()
    {
        return Xml3176VpkdLanHoc::orderBy('id', 'desc')->first();
    }

    public function moTa($lan)
    {
        if ($lan === null) {
            return null;
        }

        $nguoi = $lan->user_id
            ? (DB::table('users')->where('id', $lan->user_id)->value('name') ?: '#' . $lan->user_id)
            : 'dòng lệnh';
        $k = (array) $lan->ket_qua;

        switch ($lan->trang_thai) {
            case Xml3176VpkdLanHoc::CHO:
                $nhan = 'Đang chờ chạy (yêu cầu ' . $lan->created_at->format('d/m H:i') . ' bởi ' . $nguoi . ')';
                break;
            case Xml3176VpkdLanHoc::DANG_CHAY:
                $nhan = 'Đang chạy từ ' . optional($lan->bat_dau_luc)->format('H:i') . ' (bởi ' . $nguoi . ')';
                break;
            case Xml3176VpkdLanHoc::XONG:
                $nhan = sprintf(
                    'Xong %s bởi %s: thêm %d, cập nhật %d, tắt %d, bật lại %d, ứng viên %d',
                    optional($lan->xong_luc)->format('d/m H:i'), $nguoi,
                    $k['them'] ?? 0, $k['cap_nhat'] ?? 0, $k['tat'] ?? 0, $k['bat_lai'] ?? 0, $k['so_ung_vien'] ?? 0
                );
                break;
            default:
                $nhan = 'Lỗi: ' . $lan->loi;
        }

        return [
            'id' => $lan->id,
            'trang_thai' => $lan->trang_thai,
            'dang_chay' => in_array($lan->trang_thai, [Xml3176VpkdLanHoc::CHO, Xml3176VpkdLanHoc::DANG_CHAY], true),
            'nhan' => $nhan,
        ];
    }
}
