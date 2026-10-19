<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01LichSuGui;
use App\Services\Tt12\Tt12SubmitService;
use App\Services\Tt12\Tt12QuyetDinhGui;
use App\Services\Tt12\Bh01\Bh01XepHang;

/**
 * Gui DUNG byte tep da ky cua mot ban 01/BH len cong, ghi ket qua len ban + mot dong lich su.
 *
 * Chi Bh01XepHang::xepGui() duoc xep job nay - noi do giu khoa dang_xu_ly_tu va trao gia tri
 * khoa cho job. Job chi gui khi khoa van la cua minh; nha khoa (cung chi khoa cua minh) o cuoi
 * moi luot xu ly va trong failed(), KHONG nha giua cac lan thu lai (cung khuon SubmitTt12Job).
 */
class SubmitBh01Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Gui lai nhieu hon ky: hong gui thuong do MANG, thu lai thuong an. */
    public $tries = 3;

    public $timeout = 300;

    /** Nguyen van phan hoi luu toi da bay nhieu ky tu: cong doi khi tra ca trang HTML loi. */
    const NGUYEN_VAN_TOI_DA = 10000;

    /** @var string */
    protected $maBan;

    /** @var string|null nguoi bam gui, de ghi vao lich su */
    protected $guiBoi;

    /** @var string|null gia tri dang_xu_ly_tu ma xepGui() da ghi - ma so huu khoa */
    protected $khoa;

    /**
     * @var Tt12SubmitService|null Chi TEST moi dat. KHONG dua vao tham so co type-hint cua
     *      handle(): container Laravel 5.5 tiem ca tham so `= null`, ban tiem dung ra mang
     *      BHYTLoginService RONG ma co so, va moi lan gui deu nem 'Thieu ma co so KCB' - xem
     *      chu thich day du tren $submitServiceGia trong SubmitTt12Job.
     */
    public $submitServiceGia = null;

    public function __construct($maBan, $guiBoi = null, $khoa = null)
    {
        $this->maBan = $maBan;
        $this->guiBoi = $guiBoi;
        $this->khoa = $khoa === null ? null : (string) $khoa;
    }

    /** @return string|null */
    public function khoa()
    {
        return $this->khoa;
    }

    public function handle()
    {
        // Co tat thi KHONG ghi lich su - ghi la bia, nguoi doc se tuong da thu gui va that bai.
        if (!(bool) config('organization.tt12.submit_enabled', false)) {
            Log::info('SubmitBh01Job: chuc nang gui dang tat, bo qua ' . $this->maBan);
            $this->nhaKhoa();

            return;
        }

        $ban = Bh01Ban::where('ma_ban', $this->maBan)->first();

        if ($ban === null) {
            Log::info('SubmitBh01Job: khong tim thay ban ' . $this->maBan);
            $this->nhaKhoa();

            return;
        }

        // Hoi khoa TRUOC khi doc tep va gui. Khoa khong con la cua minh nghia la: khoa da het
        // han va mot lan bam moi da xep job khac (job do se gui), hoac job nay khong di qua
        // xepGui(). Gui tiep la hai ho so tren cong that. Khong ghi lich su, khong cham khoa:
        // ca hai thu do gio thuoc ve job kia.
        if (!Bh01XepHang::conGiuKhoa($this->maBan, $this->khoa)) {
            Log::warning('SubmitBh01Job: khoa gui khong con la cua job nay, bo qua khong gui ' . $this->maBan, array(
                'khoa_cua_job' => $this->khoa,
            ));

            return;
        }

        if ($ban->trang_thai_ky !== Bh01Ban::KY_XONG) {
            $this->tuChoi($ban, 'Bản chưa ký xong (trạng thái ' . $ban->trang_thai_ky . '), không gửi.');

            return;
        }

        $xml = $this->docTepDaKy($ban);

        if ($xml === null) {
            // Ghi loi va DUNG, khong nem: nem chi lam hang doi thu lai ba lan cho cung mot
            // ket qua. Tep mat la viec nguoi van hanh phai xu ly (ky lai).
            $this->tuChoi($ban, 'Không đọc được tệp đã ký: ' . $ban->duong_dan_da_ky);

            return;
        }

        // So bam TREN DUNG CHUOI SE GUI: tep bi thay tren dia (sua tay, ghi de) ma van gui
        // thi cong nhan mot noi dung khac voi noi dung da ky luc lap ban.
        if (empty($ban->sha256_tep) || !hash_equals((string) $ban->sha256_tep, hash('sha256', $xml))) {
            $this->tuChoi($ban, 'Tệp đã ký trên đĩa không khớp sha256 lúc ký (tệp bị thay hoặc thiếu mã băm), không gửi. Hãy ký lại.');

            return;
        }

        $submitService = $this->submitServiceGia ?: new Tt12SubmitService();

        try {
            // NEM neu loi mang - de hang doi thu lai. Xem ghi chu trong Tt12SubmitService.
            $ketQua = $submitService->guiHoSoTongHop(
                $xml,
                'BH_01',
                $ban->ma_cskcb,
                $ban->nam_qt . $ban->thang_qt
            );
        } catch (\InvalidArgumentException $e) {
            // Sai du lieu/cau hinh (kyQT sai dang, loai la): thu lai van ra dung loi do.
            $this->tuChoi($ban, $e->getMessage());

            return;
        }

        // TU DAY CONG DA NHAN GOI. Moi loi ghi CSDL phia sau ma de lot ra ngoai se lam hang
        // doi thu lai - tuc GUI LAI len cong that. Bat het, ghi log du ket qua de doi soat,
        // danh dau job that bai (khong thu lai) va nha khoa.
        try {
            $this->ghiKetQua($ban, $ketQua);
        } catch (\Throwable $e) {
            Log::critical('SubmitBh01Job: DA GUI CONG nhung khong ghi duoc ket qua ' . $this->maBan, array(
                'loi'     => $e->getMessage(),
                'ket_qua' => $ketQua,
            ));
            $this->nhaKhoa();
            $this->fail($e);

            return;
        }

        $this->nhaKhoa();
    }

    /**
     * Hang doi goi khi job het luot thu lai.
     *
     * KHONG nha khoa o cac lan thu GIUA CHUNG: nem la de hang doi thu lai, va lan thu sau van
     * thuoc cung mot luot xu ly. Chi khi het luot moi mo duong cho nguoi bam lai.
     */
    public function failed(\Throwable $e)
    {
        Log::error('SubmitBh01Job that bai het luot: ' . $this->maBan, array(
            'loi' => $e->getMessage(),
        ));

        $this->nhaKhoa();
    }

    /**
     * Go qua Bh01XepHang chu khong tu cham vao cot khoa: mot noi duy nhat biet co che khoa.
     * Chi nha khi khoa van la $this->khoa - khong xoa khoa cua mot job moi hon.
     */
    private function nhaKhoa()
    {
        Bh01XepHang::nhaKhoa($this->maBan, $this->khoa);
    }

    /** @return string|null */
    private function docTepDaKy(Bh01Ban $ban)
    {
        if (empty($ban->duong_dan_da_ky)) {
            return null;
        }

        $dia = Storage::disk('exportTt12');

        if (!$dia->exists($ban->duong_dan_da_ky)) {
            return null;
        }

        return $dia->get($ban->duong_dan_da_ky);
    }

    /**
     * Khong gui: ghi mot dong lich su co `loi` + thong_diep tren ban, roi nha khoa.
     *
     * Ghi lich su ca khi KHONG goi cong: nguoi bam can thay vi sao lan bam cua minh khong ra
     * gi. ma_ket_qua de trong - khong co ma nao tu cong.
     */
    private function tuChoi(Bh01Ban $ban, $moTa)
    {
        Log::warning('SubmitBh01Job: ' . $moTa, array('ma_ban' => $ban->ma_ban));

        Bh01LichSuGui::create(array(
            'ban_id'  => $ban->id,
            'gui_luc' => Carbon::now(),
            'gui_boi' => $this->guiBoi,
            'loi'     => $moTa,
        ));

        $ban->update(array('thong_diep' => $moTa));

        $this->nhaKhoa();
    }

    private function ghiKetQua(Bh01Ban $ban, array $ketQua)
    {
        $thanhCong = Tt12QuyetDinhGui::daTiepNhan($ketQua['ma_ket_qua']);
        $bayGio = Carbon::now();

        // Cat theo do dai cot: gia tri la tu cong dai hon cot thi MySQL strict nem, va nem o
        // day la gui lai. Nguyen van day du van nam o cot nguyen_van.
        $maKetQua = $this->cat($ketQua['ma_ket_qua'], 10);
        $maGd = $this->cat($ketQua['ma_gd'], 100);
        $thoiGian = $this->cat($ketQua['thoi_gian_tiep_nhan'], 14);

        $ban->update(array(
            'submitted_at'        => $bayGio,
            'submitted_by'        => $this->guiBoi,
            'ma_ket_qua'          => $maKetQua,
            'ma_gd'               => $maGd,
            'thoi_gian_tiep_nhan' => $thoiGian,
            'thong_diep'          => $ketQua['thong_diep'],
        ));

        // Mot dong MOI LAN gui, ke ca lan hong: cac cot tren bh01_ban bi ghi de o lan sau.
        Bh01LichSuGui::create(array(
            'ban_id'              => $ban->id,
            'gui_luc'             => $bayGio,
            'gui_boi'             => $this->guiBoi,
            'ma_ket_qua'          => $maKetQua,
            'ma_gd'               => $maGd,
            'thoi_gian_tiep_nhan' => $thoiGian,
            'thong_diep'          => $ketQua['thong_diep'],
            'loi'                 => $thanhCong ? null : ($ketQua['thong_diep'] ?: 'Cổng không tiếp nhận'),
            'nguyen_van'          => $this->cat($ketQua['nguyen_van'], self::NGUYEN_VAN_TOI_DA),
        ));
    }

    /** @return string|null */
    private function cat($giaTri, $toiDa)
    {
        if ($giaTri === null) {
            return null;
        }

        return mb_substr((string) $giaTri, 0, $toiDa);
    }
}
