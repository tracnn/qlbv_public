<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Services\BHYTXmlSubmitService;
use App\Services\Qd130XmlService;

class SubmitQd130XmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ma_lk;
    protected $xmlData;
    protected $macskcb;

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
     * Create a new job instance.
     *
     * @param string $ma_lk Mã liên kết
     * @param string $xmlData Nội dung XML đã ký số
     * @param string $macskcb Mã cơ sở khám chữa bệnh
     * @return void
     */
    public function __construct($ma_lk, $xmlData, $macskcb)
    {
        $this->ma_lk = $ma_lk;
        $this->xmlData = $xmlData;
        $this->macskcb = $macskcb;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(BHYTXmlSubmitService $xmlSubmitService, Qd130XmlService $qd130XmlService)
    {
        // Kiểm tra xem có bật tính năng gửi XML không
        $submitEnabled = config('organization.BHYT.submit_xml_enabled', false);
        if (!$submitEnabled) {
            Log::info('BHYT XML Submit is disabled for ma_lk: ' . $this->ma_lk);
            return;
        }

        try {
            // Gửi XML lên cổng BHXH
            $result = $xmlSubmitService->submitXml(
                $this->xmlData,
                config('organization.BHYT.submit_xml_url'),
                config('organization.BHYT.loai_ho_so_4750'),
                config('organization.BHYT.ma_tinh'),
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
                Log::error('BHYT XML Submit failed for ma_lk: ' . $this->ma_lk, [
                    'maKetQua' => $maKetQua,
                    'thongDiep' => $thongDiep,
                    'maGiaoDich' => $maGiaoDich,
                ]);
            } else {
                Log::info('BHYT XML Submit successful for ma_lk: ' . $this->ma_lk, [
                    'maGiaoDich' => $maGiaoDich,
                    'thongDiep' => $thongDiep,
                ]);
            }

            // Cập nhật thông tin gửi XML vào database
            $qd130XmlService->storeQd130XmlInfomation(
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
            Log::error('BHYT XML Submit exception for ma_lk: ' . $this->ma_lk, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Cập nhật thông tin lỗi
            $qd130XmlService->storeQd130XmlInfomation(
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

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('SubmitQd130XmlJob failed after all retries for ma_lk: ' . $this->ma_lk, [
            'error' => $exception->getMessage(),
        ]);
    }
}
