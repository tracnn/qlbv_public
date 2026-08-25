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
use App\Models\BHYT\Tt12\Tt12LichSuGui;
use App\Services\Tt12\Tt12SubmitService;
use App\Services\Tt12\Tt12QuyetDinhGui;
use App\Services\Tt12\Tt12DongBoDanhMuc;

/**
 * Gui mot ho so da ky len cong, ghi ket qua, va dong bo sang danh muc khi duoc tiep nhan.
 */
class SubmitTt12Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Gui lai nhieu hon ky: hong gui thuong do MANG, thu lai thuong an. */
    public $tries = 3;

    public $timeout = 300;

    /** @var string */
    protected $maHoSo;

    /** @var string|null nguoi bam gui, de ghi vao lich su */
    protected $guiBoi;

    public function __construct($maHoSo, $guiBoi = null)
    {
        $this->maHoSo = $maHoSo;
        $this->guiBoi = $guiBoi;
    }

    public function handle(Tt12SubmitService $submitService)
    {
        // Co tat thi KHONG ghi submit_error - ghi la bia, nguoi doc se tuong da thu gui
        // va that bai.
        if (!(bool) config('organization.tt12.submit_enabled', false)) {
            Log::info('SubmitTt12Job: chuc nang gui dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        $hoSo = Tt12HoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            Log::info('SubmitTt12Job: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $nenGui = Tt12QuyetDinhGui::nenGui((bool) $hoSo->is_signed, $hoSo->ma_ket_qua);

        if ($nenGui !== Tt12QuyetDinhGui::GUI) {
            Log::info('SubmitTt12Job: ho so chua du dieu kien gui', array(
                'ma_ho_so' => $this->maHoSo,
                'ly_do'    => $nenGui,
            ));

            return;
        }

        $xml = $this->docTepDaKy($hoSo);

        if ($xml === null) {
            // Ghi loi va DUNG, khong nem: nem chi lam hang doi thu lai ba lan cho cung
            // mot ket qua. Tep mat la viec nguoi van hanh phai xu ly (ky lai).
            $hoSo->update(array(
                'submit_error' => 'Không đọc được tệp đã ký: ' . $hoSo->duong_dan_da_ky,
            ));

            return;
        }

        // NEM neu loi mang - de hang doi thu lai. Xem ghi chu trong Tt12SubmitService.
        $ketQua = $submitService->gui($xml, $hoSo->mau, $hoSo->ma_cskcb);

        $this->ghiKetQua($hoSo, $ketQua);

        if (Tt12QuyetDinhGui::daTiepNhan($ketQua['ma_ket_qua'])) {
            // Chi dong bo khi cong DA TIEP NHAN. Danh muc dung de kiem XML3176 phai dung
            // bang thu BHXH da nhan, vi giam dinh se so voi chinh ban do.
            (new Tt12DongBoDanhMuc())->dongBo($hoSo->fresh());
        }
    }

    /** @return string|null */
    private function docTepDaKy(Tt12HoSo $hoSo)
    {
        if (empty($hoSo->duong_dan_da_ky)) {
            return null;
        }

        $dia = Storage::disk('exportTt12');

        if (!$dia->exists($hoSo->duong_dan_da_ky)) {
            return null;
        }

        return $dia->get($hoSo->duong_dan_da_ky);
    }

    private function ghiKetQua(Tt12HoSo $hoSo, array $ketQua)
    {
        $thanhCong = Tt12QuyetDinhGui::daTiepNhan($ketQua['ma_ket_qua']);

        $hoSo->update(array(
            'submitted_at'        => Carbon::now(),
            'submitted_by'        => $this->guiBoi,
            'ma_ket_qua'          => $ketQua['ma_ket_qua'],
            'ma_gd'               => $ketQua['ma_gd'],
            'thoi_gian_tiep_nhan' => $ketQua['thoi_gian_tiep_nhan'],
            'submitted_message'   => $ketQua['thong_diep'],
            'submit_error'        => $thanhCong ? null : $ketQua['thong_diep'],
        ));

        // Them mot dong MOI LAN gui, ke ca lan hong: giu dau vet doi soat sau khi cac cot
        // tren tt12_ho_so bi ghi de o lan gui sau.
        Tt12LichSuGui::create(array(
            'ho_so_id'            => $hoSo->id,
            'ma_ho_so'            => $hoSo->ma_ho_so,
            'gui_luc'             => Carbon::now(),
            'gui_boi'             => $this->guiBoi,
            'ma_ket_qua'          => $ketQua['ma_ket_qua'],
            'ma_gd'               => $ketQua['ma_gd'],
            'thoi_gian_tiep_nhan' => $ketQua['thoi_gian_tiep_nhan'],
            'thong_diep'          => $ketQua['thong_diep'],
            'loi'                 => $thanhCong ? null : $ketQua['nguyen_van'],
        ));
    }
}
