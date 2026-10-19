<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\XMLSignService;
use App\Services\Tt12\Bh01\Bh01PhongBi;

/**
 * Dung XML HSTH01BH tu anh chup bh01_dong cua mot ban, ky so, luu tep da ky + sha256.
 *
 * VI SAO DUNG TU bh01_dong chu khong truy van lai XML1 (spec muc 5.2): ky lai phai ra cung
 * noi dung truoc chu ky. Cung ma ban ma hai noi dung khac nhau thi khong giai thich duoc
 * voi BHXH.
 *
 * Nhan MA BAN chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot model
 * serialize san se mang theo du lieu da cu.
 */
class SignBh01Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Ky lai it lan: hong ky thuong la ly do cuc bo, thu lai it giup. */
    public $tries = 2;

    /**
     * PHAI NHO HON retry_after (300) cua ket noi 'database' trong config/queue.php: qua
     * retry_after ma job chua xong thi hang doi coi no da chet va giao them mot ban cho worker
     * khac - hai lan ky chay chong tren cung mot tep. SignBh01JobTest khoa dieu nay.
     */
    public $timeout = 280;

    /** @var string */
    protected $maBan;

    /**
     * @var XMLSignService|null Chi TEST moi dat. KHONG dua vao tham so co type-hint cua
     *      handle() - xem chu thich $submitServiceGia trong SubmitTt12Job (bay container
     *      Laravel 5.5 tiem ca tham so `= null`).
     */
    public $signServiceGia = null;

    public function __construct($maBan)
    {
        $this->maBan = $maBan;
    }

    public function handle()
    {
        // Co tat thi KHONG ghi signed_error - ghi la bia, nguoi doc se tuong da thu ky va
        // that bai.
        if (!(bool) config('organization.tt12.sign_enabled', false)) {
            Log::info('SignBh01Job: chuc nang ky dang tat, bo qua ' . $this->maBan);

            return;
        }

        $ban = Bh01Ban::where('ma_ban', $this->maBan)->first();

        if ($ban === null) {
            Log::info('SignBh01Job: khong tim thay ban ' . $this->maBan);

            return;
        }

        // Chi ky ban dang CHO KY. Bh01XepHang::xepKy() dat cho_ky truoc khi xep job, nen mot
        // job ky con sot trong hang doi (bam hai lan, hang doi giao lai sau retry_after) se gap
        // ban da_ky va dung o day - khong ky de tep va sha256 duoi chan mot job gui dang doc tep.
        if ($ban->trang_thai_ky !== Bh01Ban::KY_CHO) {
            Log::info('SignBh01Job: ban khong o trang thai cho ky, bo qua ' . $this->maBan, array(
                'trang_thai_ky' => $ban->trang_thai_ky,
            ));

            return;
        }

        // Chot cuoi (Bh01XepHang::xepKy da chan mot lan): ky de tep cua ban da gui se lam tep
        // tai ve khac tep cong da nhan - mat dau vet doi soat.
        if ($ban->submitted_at !== null) {
            Log::warning('SignBh01Job: ban da gui cong, khong ky de tep ' . $this->maBan);

            return;
        }

        try {
            // Con tro CSDL + XMLWriter: ban 20 nghin dong khong duoc nap het vao bo nho
            // (memory_limit 128 MB tren qlbv_public). DB::table chu khong Eloquent: PhongBi
            // ep (array), ma model ep (array) ra thuoc tinh noi bo chu khong ra cot.
            $cacDong = DB::table('bh01_dong')
                ->where('ban_id', $ban->id)
                ->orderBy('stt')
                ->cursor();

            $xml = Bh01PhongBi::dung($ban->id_ds_chitiet, $cacDong);
        } catch (\InvalidArgumentException $e) {
            // Ghi lai, KHONG nem: nem chi lam hang doi thu lai cho cung mot ket qua.
            $this->ghiLoi($ban, 'Không dựng được XML: ' . $e->getMessage());

            return;
        }

        $signService = $this->signServiceGia ?: new XMLSignService();

        $ketQua = $signService->signXml($xml);

        // Kiem ca data: "da ky" ma du lieu rong thi ghi ra mot tep rong co sha256 hop le, va
        // job gui se gui dung tep rong do len cong.
        if (empty($ketQua['isSigned']) || !isset($ketQua['data']) || !is_string($ketQua['data']) || $ketQua['data'] === '') {
            $this->ghiLoi(
                $ban,
                !empty($ketQua['error']) ? (string) $ketQua['error'] : 'Ký số thất bại không rõ lý do'
            );

            return;
        }

        $noiDungDaKy = $ketQua['data'];
        $duongDan = 'BH01/' . $ban->nam_qt . $ban->thang_qt . '/' . $ban->ma_ban . '.xml';

        // Chi ghi tep KHI DA KY THANH CONG. sha256 tinh tren DUNG chuoi byte ghi ra dia:
        // job gui so lai bam nay truoc khi gui, tep bi thay tren dia thi tu choi.
        Storage::disk('exportTt12')->put($duongDan, $noiDungDaKy);

        $ban->update(array(
            'trang_thai_ky'   => Bh01Ban::KY_XONG,
            'sign_method'     => isset($ketQua['method']) ? $ketQua['method'] : null,
            'signed_at'       => Carbon::now(),
            'signed_error'    => null,
            'duong_dan_da_ky' => $duongDan,
            'sha256_tep'      => hash('sha256', $noiDungDaKy),
        ));

        Log::info('SignBh01Job: da ky ' . $this->maBan, array('duong_dan' => $duongDan));
    }

    /**
     * Hang doi goi khi job het luot thu lai (vd dich vu ky nem loi mang).
     *
     * PHAI co: khong co thi ban nam o "cho ky" mai mai, nut "Ky lai" (chi hien khi ky loi)
     * khong bao gio hien, va khong co dong nao tren man noi vi sao.
     */
    public function failed(\Throwable $e)
    {
        Log::error('SignBh01Job that bai het luot: ' . $this->maBan, array(
            'loi' => $e->getMessage(),
        ));

        $ban = Bh01Ban::where('ma_ban', $this->maBan)->first();

        if ($ban !== null && $ban->trang_thai_ky !== Bh01Ban::KY_XONG) {
            $this->ghiLoi($ban, 'Ký số thất bại: ' . $e->getMessage());
        }
    }

    private function ghiLoi(Bh01Ban $ban, $moTa)
    {
        Log::warning('SignBh01Job: ' . $moTa, array('ma_ban' => $ban->ma_ban));

        // KHONG xoa duong_dan_da_ky/sha256_tep cu o day: trang_thai_ky = ky_loi da du chan
        // job gui, con giu lai thi nguoi van hanh van doi chieu duoc tep lan ky truoc.
        $ban->update(array(
            'trang_thai_ky' => Bh01Ban::KY_LOI,
            'signed_error'  => $moTa,
        ));
    }
}
