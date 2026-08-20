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
use App\Services\BHYTLoginService;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtSubmitService;

/**
 * Gui mot ho so da ky len cong BHXH va ghi lai ket qua.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 */
class SubmitCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 60;

    /** @var string */
    protected $maHoSo;

    /** @var string|null */
    protected $nguoiGui;

    public function __construct($maHoSo, $nguoiGui = null)
    {
        $this->maHoSo = $maHoSo;
        $this->nguoiGui = $nguoiGui;
    }

    /**
     * @param CtdtSubmitService|null $submitService Chi de test tiem ban gia. Khi null, job tu
     *        dung service bang ma co so cua CHINH ho so - xem chu thich trong than ham.
     */
    public function handle(CtdtSubmitService $submitService = null)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('SubmitCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $quyetDinh = CtdtQuyetDinhGui::nen(
            config('organization.chung_tu_dien_tu.submit_enabled', false),
            $hoSo->checked_at,
            $hoSo->so_loi,
            $hoSo->is_signed
        );

        if ($quyetDinh === CtdtQuyetDinhGui::KHONG_GUI) {
            // KHONG ghi gi ca, ke ca submit_error. Khi chuc nang gui dang tat thi khong co
            // lan gui nao dien ra - ghi loi la bia, nguoi doc se tuong da thu gui va that bai.
            Log::info('SubmitCtdtJob: chuc nang gui dang tat, bo qua ' . $this->maHoSo);

            return;
        }

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            $this->ghiLoi($hoSo, $this->lyDo($quyetDinh));

            return;
        }

        $duongDan = (string) $hoSo->duong_dan_da_ky;

        if ($duongDan === '' || !Storage::disk('exportCtdt')->exists($duongDan)) {
            // Tep tren dia co the bi don dep. Ghi lai de nguoi van hanh doc, dung nem: nem
            // chi lam hang doi thu lai ba lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Khong tim thay tep da ky: ' . ($duongDan === '' ? '(trong)' : $duongDan));

            return;
        }

        // Doc noi dung DA KY tren dia, KHONG dung lai phong bi: dung lai la gui mot goi
        // khong co chu ky, va cong tra 205 - hoac te hon, nhan mot ho so khong co gia tri
        // phap ly.
        $xmlDaKy = Storage::disk('exportCtdt')->get($duongDan);

        if ($submitService === null) {
            // KHONG nhan service qua container: container khong biet ho so nay thuoc co so
            // nao nen se dung BHYTLoginService KHONG ma co so, va lan gui dau tien se nem.
            // SubmitXml3176Job.php:70-73 da dinh dung bay nay va co san chu thich canh bao.
            // Dung tuong minh bang ma co so cua CHINH ho so, de token va tai khoan trong body
            // khong the thuoc hai co so khac nhau.
            $submitService = new CtdtSubmitService(new BHYTLoginService($hoSo->macskcb));
        }

        // KHONG bat exception o day: loi mang la loi TAM THOI va phai de hang doi thu lai.
        // Nuot no thanh mot dong submit_error la ho so mat co hoi duoc gui lai tu dong.
        $ketQua = $submitService->gui($xmlDaKy, $hoSo->dich_vu, $hoSo->macskcb);

        $this->ghiKetQua($hoSo, $ketQua);
    }

    private function ghiKetQua(CtdtHoSo $hoSo, array $ketQua)
    {
        // So sanh LONG: cong co the tra so 200 thay vi chuoi '200'. So sanh nghiem ngat se
        // coi mot ho so THANH CONG la bi tu choi.
        $thanhCong = isset($ketQua['ma_ket_qua']) && $ketQua['ma_ket_qua'] == '200';

        $thuocTinh = [
            'ma_gd'               => isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null,
            'ma_ket_qua'          => isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null,
            'thoi_gian_tiep_nhan' => isset($ketQua['thoi_gian_tiep_nhan']) ? $ketQua['thoi_gian_tiep_nhan'] : null,
            'submitted_at'        => now(),
            'submitted_by'        => $this->nguoiGui,
            'submitted_message'   => isset($ketQua['nguyen_van']) ? $ketQua['nguyen_van'] : null,
            'submit_error'        => $thanhCong ? null : (isset($ketQua['thong_diep']) ? $ketQua['thong_diep'] : 'Cong tu choi'),
        ];

        $lichSu = $this->noiLichSu($hoSo);

        if ($lichSu !== null) {
            $thuocTinh['lich_su_gui'] = $lichSu;
        }

        $hoSo->update($thuocTinh);

        Log::info('SubmitCtdtJob: da gui', [
            'ma_ho_so'   => $this->maHoSo,
            'ma_ket_qua' => isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null,
            'ma_gd'      => isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null,
        ]);
    }

    /**
     * Noi mot dong vao lich su TRUOC khi ghi de.
     *
     * MaGD cu la dau vet doi soat voi BHXH. Ghi de ma khong giu lai la mat dau vet cua mot
     * lan gui da that su xay ra, va tranh chap doi soat se khong co gi de tra.
     */
    private function noiLichSu(CtdtHoSo $hoSo)
    {
        if (empty($hoSo->ma_gd) && empty($hoSo->ma_ket_qua)) {
            return null;
        }

        $dong = '[' . now()->format('Y-m-d H:i:s') . '] MaGD=' . (string) $hoSo->ma_gd
              . ' MaKetQua=' . (string) $hoSo->ma_ket_qua;

        return trim((string) $hoSo->lich_su_gui . "\n" . $dong);
    }

    private function lyDo($quyetDinh)
    {
        if ($quyetDinh === CtdtQuyetDinhGui::CHUA_KIEM) {
            return 'Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CON_LOI) {
            return 'Hồ sơ còn lỗi chặn gửi — sửa nguồn rồi nạp lại';
        }

        return 'Hồ sơ chưa ký số';
    }

    private function ghiLoi(CtdtHoSo $hoSo, $loi)
    {
        Log::info('SubmitCtdtJob: ' . $loi, ['ma_ho_so' => $this->maHoSo]);

        $hoSo->update(['submit_error' => $loi]);
    }

    public function failed(\Throwable $exception)
    {
        // Het luot thu ma khong ghi gi thi ho so o lai khong dau vet gi tren man hinh -
        // nguoi van hanh ngoi cho mot viec da chet.
        Log::error('SubmitCtdtJob that bai sau moi luot thu: ' . $exception->getMessage(), [
            'ma_ho_so' => $this->maHoSo,
        ]);

        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo !== null) {
            $hoSo->update(['submit_error' => 'Job gửi thất bại: ' . $exception->getMessage()]);
        }
    }
}
