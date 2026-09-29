<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\BHYTXmlSubmitService;
use App\Services\BHYTLoginService;
use App\Services\BHYT\CauHinhCoSo;
use App\Services\Xml3176Service;

class SubmitXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    protected $ma_lk;
    protected $xmlFilePath;
    protected $macskcb;

    /**
     * Diem tiem cho test thay cho tham so handle() (khuon SubmitTt12Job): container Laravel
     * 5.5 tiem ca tham so khai "= null", nen dich vu gui KHONG duoc nhan qua handle().
     */
    public $submitServiceGia = null;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * @param string      $ma_lk
     * @param string|null $chainToken Ma phien cua chuoi. Duong dan tep va ma co so doc tu ho
     *                                so luc chay. Job cu (truoc chuoi) mang san $xmlFilePath
     *                                va $macskcb - giu ten thuoc tinh de chung giai nen dung.
     */
    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Xml3176Service $xml3176Service)
    {
        // Kiem co TRUOC khi lam bat cu viec gi. Noi dispatch cung da kiem roi, nhung job co
        // the nam cho trong hang doi rat lau, giua luc do cau hinh co the da bi tat.
        $submitEnabled = config('organization.BHYT.submit_xml_3176_enabled', false);
        if (!$submitEnabled) {
            Log::info('BHYT XML 3176 Submit is disabled for ma_lk: ' . $this->ma_lk);
            return;
        }

        if (!$this->laJobCu()) {
            if (!$this->conHieuLuc($this->ma_lk)) {
                $this->catChuoi();
                return;
            }

            $thongTin = Xml3176Information::where('ma_lk', $this->ma_lk)->first();
            $this->macskcb = $thongTin->macskcb;
            $this->xmlFilePath = $thongTin->signed_file_path;

            if (empty($this->xmlFilePath)) {
                $xml3176Service->storeXml3176Information($this->ma_lk, $this->macskcb, 'submit', 1,
                    'Gửi lỗi — không tìm thấy tệp đã ký');
                return;
            }
        }
        // Job cu (truoc chuoi) mang san xmlFilePath va macskcb, di thang xuong duoi.

        // KHONG nhan BHYTXmlSubmitService qua container: container khong biet ho so nay thuoc
        // co so nao nen se dung BHYTLoginService khong ma co so, va lan gui dau tien se nem.
        // Dung tuong minh bang ma co so cua chinh ho so.
        $xmlSubmitService = $this->submitServiceGia
            ?: new BHYTXmlSubmitService(new BHYTLoginService($this->macskcb));

        try {
            // Đọc nội dung XML từ file đã được lưu trước đó
            $xmlData = Storage::disk('exportXml3176')->get($this->xmlFilePath);

            // Gửi XML lên cổng BHXH
            $result = $xmlSubmitService->submitXml(
                $xmlData,
                config('organization.BHYT.submit_xml_3176_url'),
                config('organization.BHYT.loai_ho_so_3176'),
                // Ma tinh suy tu ma co so, KHONG lay tu config: cau hinh chot cung '01' trong
                // khi co so 37470 o Ninh Binh phai la '37'.
                CauHinhCoSo::maTinh($this->macskcb),
                $this->macskcb
            );

            // Kiểm tra kết quả
            $maKetQua = $result['maKetQua'] ?? null;
            $maGiaoDich = $result['maGiaoDich'] ?? null;
            $thongDiep = $result['thongDiep'] ?? null;
            $thoiGianTiepNhan = $result['thoiGianTiepNhan'] ?? null;

            // Lưu thông tin kết quả gửi
            $error = null;
            if ($maKetQua !== '200' && $maKetQua !== 200) {
                $error = 'Mã kết quả: ' . $maKetQua . '. ' . ($thongDiep ?? 'Lỗi không xác định');
                Log::error('BHYT XML 3176 Submit failed for ma_lk: ' . $this->ma_lk, [
                    'maKetQua' => $maKetQua,
                    'thongDiep' => $thongDiep,
                    'maGiaoDich' => $maGiaoDich,
                ]);
            } else {
                Log::info('BHYT XML 3176 Submit successful for ma_lk: ' . $this->ma_lk, [
                    'maGiaoDich' => $maGiaoDich,
                    'thongDiep' => $thongDiep,
                ]);
            }

            // Cập nhật thông tin gửi XML vào database
            $xml3176Service->storeXml3176Information(
                $this->ma_lk,
                $this->macskcb,
                'submit',
                1,
                $error,
                null,
                null,
                $maGiaoDich
            );

        } catch (\Exception $e) {
            $error = 'Lỗi gửi XML: ' . $e->getMessage();
            Log::error('BHYT XML 3176 Submit exception for ma_lk: ' . $this->ma_lk, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Cập nhật thông tin lỗi
            $xml3176Service->storeXml3176Information(
                $this->ma_lk,
                $this->macskcb,
                'submit',
                1,
                $error,
                null,
                null,
                null
            );

            // Throw lại exception để Laravel queue có thể retry
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('SubmitXml3176Job failed after all retries for ma_lk: ' . $this->ma_lk, [
            'error' => $exception->getMessage(),
        ]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['submit_error' => 'Gửi lỗi — ' . $exception->getMessage()]);
    }
}
