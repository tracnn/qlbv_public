<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtSubmitService;
use App\Services\Ctdt\CtdtXepHangKyGui;

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

    /**
     * PHAI lon hon timeout cua CtdtSubmitService (60s Guzzle): job bi cat ngang dung luc
     * HTTP sap xong la hong so - ho so co the da toi cong ma ta khong ghi duoc ket qua.
     *
     * @var int
     */
    public $timeout = 90;

    /** @var string */
    protected $maHoSo;

    /** @var string|null */
    protected $nguoiGui;

    /**
     * @var CtdtSubmitService|null Chi TEST moi dat. KHONG dua vao tham so co type-hint cua
     *      handle(): container Laravel 5.5 tiem theo getClass() TRUOC khi xet gia tri mac
     *      dinh (Illuminate\Container\BoundMethod::addDependencyForCallParameter(), dong
     *      getClass() truoc isDefaultValueAvailable()), nen mot tham so `= null` van luon bi
     *      tiem, va ban container dung ra mang BHYTLoginService RONG ma co so - moi lan gui
     *      deu nem InvalidArgumentException. Day dung la bay ma SubmitXml3176Job.php:70-73
     *      da dinh mot lan roi.
     */
    public $submitServiceGia = null;

    /**
     * Ai gay ra lan gui nay: 'man_hinh' hay 'console'.
     *
     * Co GIA TRI MAC DINH nen payload cua nhung job da nam san trong hang doi truoc khi
     * trien khai van giai tuan tu duoc - PHP dat lai gia tri mac dinh cua lop cho thuoc tinh
     * vang mat trong chuoi da serialize.
     *
     * @var string
     */
    public $nguon = CtdtLichSuGui::NGUON_MAN_HINH;

    public function __construct($maHoSo, $nguoiGui = null, $nguon = CtdtLichSuGui::NGUON_MAN_HINH)
    {
        $this->maHoSo = $maHoSo;
        $this->nguoiGui = $nguoiGui;
        $this->nguon = $nguon;
    }

    public function handle()
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('SubmitCtdtJob: khong tim thay ho so ' . $this->maHoSo);
            $this->nhaKhoa();

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
            $this->nhaKhoa();

            return;
        }

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            $this->ghiLoi($hoSo, $this->lyDo($quyetDinh));
            $this->nhaKhoa();

            return;
        }

        $duongDan = (string) $hoSo->duong_dan_da_ky;

        if ($duongDan === '' || !Storage::disk('exportCtdt')->exists($duongDan)) {
            // Tep tren dia co the bi don dep. Ghi lai de nguoi van hanh doc, dung nem: nem
            // chi lam hang doi thu lai ba lan cho cung mot ket qua.
            $this->ghiLoi($hoSo, 'Khong tim thay tep da ky: ' . ($duongDan === '' ? '(trong)' : $duongDan));
            $this->nhaKhoa();

            return;
        }

        // Doc noi dung DA KY tren dia, KHONG dung lai phong bi: dung lai la gui mot goi
        // khong co chu ky, va cong tra 205 - hoac te hon, nhan mot ho so khong co gia tri
        // phap ly.
        $xmlDaKy = Storage::disk('exportCtdt')->get($duongDan);

        // Tu dung service, KHONG nhan qua tham so co type-hint cua handle(): container
        // Laravel 5.5 tiem theo getClass() TRUOC khi xet gia tri mac dinh, nen mot tham so
        // `= null` van luon bi tiem - xem chu thich tren $submitServiceGia. Khong can dung
        // rieng BHYTLoginService(macskcb) o day: CtdtSubmitService::gui() da tu dung dung
        // theo $maCskcb duoc truyen vao, de mot noi duy nhat chiu trach nhiem noi ma co so
        // voi token.
        $submitService = $this->submitServiceGia ?: new CtdtSubmitService();

        // KHONG bat exception o day: loi mang la loi TAM THOI va phai de hang doi thu lai.
        // Nuot no thanh mot dong submit_error la ho so mat co hoi duoc gui lai tu dong.
        $ketQua = $submitService->gui($xmlDaKy, $hoSo->dich_vu, $hoSo->macskcb);

        $this->ghiKetQua($hoSo, $ketQua);
        $this->nhaKhoa();
    }

    /**
     * Nha khoa chong bam trung. Goi o MOI duong ra cua job, ke ca duong that bai.
     *
     * Khong nha thi nguoi dung phai cho het han khoa moi gui lai duoc - ke ca khi lan gui
     * truoc da xong tu lau.
     *
     * KHONG goi trong nhanh nem cua $submitService->gui(): o do job co y de ngoai le bay ra
     * cho hang doi thu lai, va lan thu sau van thuoc cung mot luot xu ly. failed() se nha
     * khoa khi het luot.
     */
    private function nhaKhoa()
    {
        // Go qua CtdtXepHangKyGui chu khong tu cham vao co che khoa: khoa da doi tu
        // Cache sang mot bang co unique index (Cache::add() KHONG nguyen tu tren
        // FileStore cua Laravel 5.5). Tu cham vao day nghia la lan doi co che sau se
        // bo sot mot noi, va trieu chung la khoa khong bao gio duoc nha - ho so do
        // khong gui lai duoc trong 30 phut ma khong co dong log nao.
        CtdtXepHangKyGui::goKhoa($this->maHoSo);
    }

    /**
     * Cat gia tri cho vua cot truoc khi ghi.
     *
     * VI SAO CAN: cac gia tri nay den tu phan hoi cua cong, con MySQL cua du an bat strict
     * mode (config/database.php:53). Mot gia tri dai hon cot lam update() nem - va no nem
     * SAU KHI cong da nhan ho so. Job co y khong bat exception (de hang doi thu lai khi mang
     * chap), nen mot lan tran cot bien thanh toi da SAU lan POST cung mot goi len cong:
     * tries = 3, moi luot con retry-on-401 mot lan nua. Body PL02 khong co ma giao dich phia
     * client nen cong khong khu trung duoc.
     *
     * thoi_gian_tiep_nhan dac biet sat: VARCHAR(14) vua khit 'yyyyMMddHHmmss', khong du mot
     * ky tu. Cong doi sang '2026-08-20 08:30:00' la tran ngay.
     *
     * Ban day du cua phan hoi van con nguyen trong submitted_message (TEXT) va trong log,
     * nen cat o day khong mat thong tin nao khong tra cuu duoc.
     *
     * DO RONG O DAY PHAI KHOP MIGRATION. Lan gui that dau tien (2026-08-20) cho thay MaGD
     * cong tra ve dai 52 ky tu trong khi cot cu la VARCHAR(50) - phep cat nay chan duoc lan
     * gui trung, nhung lai cat mat hai ky tu cuoi cua chinh cot doi soat quan trong nhat.
     * Migration 2026_08_20_100001 noi ma_gd len 100, ma_ket_qua va thoi_gian_tiep_nhan len 20.
     */
    private function cat($giaTri, $doDai)
    {
        if ($giaTri === null) {
            return null;
        }

        return mb_substr((string) $giaTri, 0, $doDai);
    }

    private function ghiKetQua(CtdtHoSo $hoSo, array $ketQua)
    {
        // So sanh LONG: cong co the tra so 200 thay vi chuoi '200'. So sanh nghiem ngat se
        // coi mot ho so THANH CONG la bi tu choi.
        $thanhCong = isset($ketQua['ma_ket_qua']) && $ketQua['ma_ket_qua'] == '200';

        $thuocTinh = [
            'ma_gd'               => $this->cat(isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null, 100),
            'ma_ket_qua'          => $this->cat(isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null, 20),
            'thoi_gian_tiep_nhan' => $this->cat(isset($ketQua['thoi_gian_tiep_nhan']) ? $ketQua['thoi_gian_tiep_nhan'] : null, 20),
            'submitted_at'        => now(),
            'submitted_by'        => $this->nguoiGui,
            // Toan van phan hoi cua cong, KHONG loc: man chi tiet ho so hien nguyen dong nay
            // cho nguoi van hanh doc, chua can cat do dai o day.
            'submitted_message'   => isset($ketQua['nguyen_van']) ? $ketQua['nguyen_van'] : null,
            'submit_error'        => $this->cat($thanhCong ? null : (isset($ketQua['thong_diep']) ? $ketQua['thong_diep'] : 'Cong tu choi'), 255),
        ];

        $lichSu = $this->noiLichSu($hoSo);

        if ($lichSu !== null) {
            $thuocTinh['lich_su_gui'] = $lichSu;
        }

        $hoSo->update($thuocTinh);

        // Ghi nhat ky TRUOC moi nhanh return phia duoi: mot lan goi cong da xay ra roi thi
        // phai co dau vet, ke ca khi cong tu choi. Dung create() chu khong updateOrCreate:
        // moi lan gui la MOT dong, gui lai lan hai khong duoc de len lan mot.
        try {
            CtdtLichSuGui::create([
                'ho_so_id'            => $hoSo->id,
                'ma_ho_so'            => $hoSo->ma_ho_so,
                'nguoi_gui'           => $this->nguoiGui,
                // Nguon la tham so TUONG MINH, khong suy tu $nguoiGui === null: kyVaGui()
                // cung truyen null khi auth()->check() tra false, nen suy se ghi mot cu bam
                // tay thanh "lenh nen". Quy sai mot lan gui khong nguoi truc cho mot con
                // nguoi la kieu noi doi te nhat mot bang nhat ky co the mac.
                'nguon'               => $this->nguon,
                // CAT y het khi ghi vao ctdt_ho_so o tren, va theo DUNG do rong cot cua
                // migration 2026_08_21_100001 (ma_gd 100, ma_ket_qua 20, thoi_gian_tiep_nhan
                // 20). Truoc day khong cat o day: mot ma_gd 101 ky tu tu cong lam create()
                // nem, va tuy try/catch ben duoi khong lam job that bai, ta van MAT NGUYEN
                // DONG NHAT KY - dung luc can no nhat, vi do la lan gui co phan hoi bat
                // thuong. thong_diep la cot TEXT nen giu nguyen ven, khong cat.
                'ma_gd'               => $this->cat(isset($ketQua['ma_gd']) ? $ketQua['ma_gd'] : null, 100),
                'ma_ket_qua'          => $this->cat(isset($ketQua['ma_ket_qua']) ? $ketQua['ma_ket_qua'] : null, 20),
                'thoi_gian_tiep_nhan' => $this->cat(isset($ketQua['thoi_gian_tiep_nhan'])
                    ? $ketQua['thoi_gian_tiep_nhan'] : null, 20),
                'thanh_cong'          => $thanhCong,
                'thong_diep'          => isset($ketQua['thong_diep']) ? $ketQua['thong_diep'] : null,
            ]);
        } catch (\Exception $e) {
            // Mat mot dong nhat ky con hon nem sau khi cong DA NHAN ho so: nem o day lam job
            // that bai, hang doi gui lai, va cong nhan LAN HAI cung mot goi - ma PL02 khong
            // co ma giao dich phia client de cong khu trung.
            Log::error('CTDT khong ghi duoc lich su gui ' . $hoSo->ma_ho_so . ': ' . $e->getMessage());
        }

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

        $hoSo->update(['submit_error' => $this->cat($loi, 255)]);
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
            $hoSo->update(['submit_error' => $this->cat('Job gửi thất bại: ' . $exception->getMessage(), 255)]);
        }

        $this->nhaKhoa();
    }
}
