<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\XMLSignService;
use App\Services\Tt12\Tt12DocDong;
use App\Services\Tt12\Tt12PhongBi;
use App\Services\Tt12\Tt12MauRegistry;
use App\Services\Tt12\Tt12QuyetDinhGui;

/**
 * Dung phong bi mot ho so, ky so, luu tep da ky.
 *
 * VI SAO TACH KHOI JOB GUI: ky hong do ly do CUC BO (USB token bi rut, HSM khong phan
 * hoi) con gui hong do MANG. Gop lai thi tries = 3 se ky lai ba lan chi vi mang chap, ma
 * ky la thao tac ton thoi gian nhat trong chuoi.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 */
class SignTt12Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Ky lai it lan hon gui: hong ky thuong la ly do cuc bo, thu lai it giup. */
    public $tries = 2;

    public $timeout = 300;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle(XMLSignService $signService)
    {
        // Kiem co TRUOC khi lam bat cu viec gi. Noi dispatch cung da kiem, nhung job co
        // the nam cho rat lau, giua luc do cau hinh co the da bi tat. Co tat thi KHONG
        // ghi signed_error - ghi la bia, nguoi doc se tuong da thu ky va that bai.
        if (!(bool) config('organization.tt12.sign_enabled', false)) {
            Log::info('SignTt12Job: chuc nang ky dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        $hoSo = Tt12HoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            Log::info('SignTt12Job: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $nenKy = Tt12QuyetDinhGui::nenKy($hoSo->checked_at, $hoSo->so_loi);

        if ($nenKy !== Tt12QuyetDinhGui::KY) {
            Log::info('SignTt12Job: ho so chua du dieu kien ky', array(
                'ma_ho_so' => $this->maHoSo,
                'ly_do'    => $nenKy,
            ));

            return;
        }

        try {
            $lop = Tt12MauRegistry::cho($hoSo->mau);

            $xml = Tt12PhongBi::dung(
                $lop,
                $hoSo->id_danh_sach,
                (new Tt12DocDong())->choPhongBi($hoSo)
            );
        } catch (\InvalidArgumentException $e) {
            // Ghi lai de nguoi van hanh doc, KHONG nem: nem chi lam hang doi thu lai hai
            // lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Không dựng được XML: ' . $e->getMessage());

            return;
        }

        $ketQua = $signService->signXml($xml);

        if (empty($ketQua['isSigned'])) {
            $this->ghiLoi(
                $hoSo,
                isset($ketQua['error']) ? (string) $ketQua['error'] : 'Ký số thất bại không rõ lý do'
            );

            return;
        }

        // Chi ghi tep KHI DA KY THANH CONG.
        $duongDan = $this->duongDan($hoSo);

        Storage::disk('exportTt12')->put($duongDan, $ketQua['data']);

        $hoSo->update(array(
            'is_signed'       => true,
            'sign_method'     => isset($ketQua['method']) ? $ketQua['method'] : null,
            'signed_at'       => Carbon::now(),
            'signed_error'    => null,
            'duong_dan_da_ky' => $duongDan,
        ));

        Log::info('SignTt12Job: da ky ' . $this->maHoSo, array('duong_dan' => $duongDan));
    }

    /** Duong dan tuong doi trong dia exportTt12, chia theo thang de thu muc khong phinh */
    private function duongDan(Tt12HoSo $hoSo)
    {
        return $hoSo->mau . '/' . Carbon::now()->format('Ym') . '/' . $hoSo->ma_ho_so . '.xml';
    }

    private function ghiLoi(Tt12HoSo $hoSo, $moTa)
    {
        Log::warning('SignTt12Job: ' . $moTa, array('ma_ho_so' => $hoSo->ma_ho_so));

        $hoSo->update(array(
            'is_signed'    => false,
            'signed_error' => $moTa,
        ));
    }
}
