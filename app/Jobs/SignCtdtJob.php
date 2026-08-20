<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtPhongBi;
use App\Services\XMLSignService;

/**
 * Dung phong bi mot ho so, ky so, luu tep da ky.
 *
 * VI SAO TACH KHOI JOB GUI: ky hong do ly do CUC BO (USB token bi rut, HSM khong phan hoi)
 * con gui hong do MANG. Gop lai thi tries = 3 se ky lai ba lan chi vi mang chap, ma ky la
 * thao tac ton thoi gian nhat trong chuoi.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 */
class SignCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Ky lai it lan hon gui: hong ky thuong la ly do cuc bo, thu lai it giup */
    public $tries = 2;

    /** @var int */
    public $timeout = 120;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle(XMLSignService $signService)
    {
        // Kiem co TRUOC khi lam bat cu viec gi. Noi dispatch cung da kiem, nhung job co the
        // nam cho trong hang doi rat lau, giua luc do cau hinh co the da bi tat. Co tat thi
        // KHONG ghi signed_error - ghi la bia, nguoi doc se tuong da thu ky va that bai.
        if (!(bool) config('organization.chung_tu_dien_tu.sign_enabled', false)) {
            Log::info('SignCtdtJob: chuc nang ky dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('SignCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        if (empty($hoSo->checked_at) || (int) $hoSo->so_loi > 0) {
            // Ky mot ho so chua kiem hoac con loi la ton thao tac dat nhat trong chuoi cho
            // mot ho so chac chan khong duoc gui.
            Log::info('SignCtdtJob: ho so chua du dieu kien ky', [
                'ma_ho_so' => $this->maHoSo,
                'da_kiem'  => !empty($hoSo->checked_at),
                'so_loi'   => (int) $hoSo->so_loi,
            ]);

            return;
        }

        try {
            $phongBi = CtdtPhongBi::dung([
                'dich_vu'    => $hoSo->dich_vu,
                'macskcb'    => $hoSo->macskcb,
                'id_goi_xml' => $hoSo->id_goi_xml,
                'ngay_lap'   => $hoSo->ngay_lap,
            ], $this->chungTu($hoSo));
        } catch (\InvalidArgumentException $e) {
            // Du lieu ho so khong dung phong bi duoc - ghi lai de nguoi van hanh doc, dung
            // nem: nem chi lam hang doi thu lai hai lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Khong dung duoc phong bi: ' . $e->getMessage());

            return;
        }

        $ketQua = $signService->signXml($phongBi);

        if (empty($ketQua['isSigned'])) {
            $this->ghiLoi(
                $hoSo,
                isset($ketQua['error']) ? (string) $ketQua['error'] : 'Ky so that bai khong ro ly do'
            );

            return;
        }

        // Chi ghi tep KHI DA KY THANH CONG. Ghi mot tep chua ky vao duong "da ky" la de lai
        // mot qua bom: lan gui sau doc dung tep do va gui len cong mot goi khong co chu ky.
        $duongDan = $this->duongDan($hoSo);
        Storage::disk('exportCtdt')->put($duongDan, $ketQua['data']);

        $hoSo->update([
            'is_signed'       => true,
            'sign_method'     => isset($ketQua['method']) ? $ketQua['method'] : null,
            'signed_at'       => now(),
            'signed_error'    => null,
            'duong_dan_da_ky' => $duongDan,
        ]);

        Log::info('SignCtdtJob: ky thanh cong', [
            'ma_ho_so' => $this->maHoSo,
            'phuong_thuc' => isset($ketQua['method']) ? $ketQua['method'] : null,
        ]);
    }

    /** @return array Cac ['loai_ho_so', 'noi_dung_goc'] theo dung thu tu da luu */
    private function chungTu(CtdtHoSo $hoSo)
    {
        $ketQua = [];

        foreach ($hoSo->chungTu as $ct) {
            $ketQua[] = [
                'loai_ho_so'   => $ct->loai_ho_so,
                'noi_dung_goc' => $ct->noi_dung_goc,
            ];
        }

        return $ketQua;
    }

    /**
     * Ten tep an toan tu ma ho so.
     *
     * ma_ho_so den tu the XML ben ngoai va co the chua '#' (nhanh lui GUID) hoac '../'.
     * Ghep thang vao duong dan tep la mo duong di ra khoi thu muc.
     */
    private function duongDan(CtdtHoSo $hoSo)
    {
        // KHONG cho '.' lot qua: giu '.' trong bang trang cho phep se de nguyen chuoi
        // '..' cua '../' sau khi '/' bi thay bang '_' (vd '../' -> '.._'), van chua '..'.
        $ten = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $hoSo->ma_ho_so);

        if ($ten === '' || $ten === null) {
            $ten = 'ho-so-' . $hoSo->id;
        }

        return 'da-ky/' . $hoSo->dich_vu . '/' . $ten . '.xml';
    }

    private function ghiLoi(CtdtHoSo $hoSo, $loi)
    {
        Log::warning('SignCtdtJob: ' . $loi, ['ma_ho_so' => $this->maHoSo]);

        $hoSo->update([
            'is_signed'    => false,
            'sign_method'  => null,
            'signed_at'    => null,
            'signed_error' => $loi,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        // Het luot thu ma khong ghi gi thi ho so o lai "chua ky" vinh vien, khong dau vet
        // tren man hinh - nguoi van hanh ngoi cho mot viec da chet.
        Log::error('SignCtdtJob that bai sau moi luot thu: ' . $exception->getMessage(), [
            'ma_ho_so' => $this->maHoSo,
        ]);

        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo !== null) {
            $hoSo->update(['signed_error' => 'Job ky that bai: ' . $exception->getMessage()]);
        }
    }
}
