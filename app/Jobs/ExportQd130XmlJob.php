<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;

use App\Services\Qd130XmlService;
use App\Models\BHYT\Qd130XmlErrorResult;
use App\Models\BHYT\Qd130Xml1;

class ExportQd130XmlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ma_lk;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($ma_lk)
    {
        $this->ma_lk = $ma_lk;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Qd130XmlService $xmlService)
    {
        // Kiểm tra nếu không có lỗi nghiêm trọng trước khi xuất XML
        $hasCriticalError = Qd130XmlErrorResult::where('ma_lk', $this->ma_lk)
            ->where('critical_error', true)
            ->exists();

        // Lấy hồ sơ XML theo ma_lk
        $xmlRecord = Qd130Xml1::where('ma_lk', $this->ma_lk)->first();

        // Kiểm tra nếu hồ sơ tồn tại và ngay_ra lớn hơn thời điểm hiện tại
        if ($xmlRecord && Carbon::createFromFormat('YmdHi', $xmlRecord->ngay_ra)->gt(Carbon::now())) {
            // Nếu ngay_ra lớn hơn thời điểm hiện tại, không xuất hồ sơ XML
            return;
        }

        if (config('organization.export_xml_not_check')) {
            $xmlService->processExportXml($this->ma_lk);
        } else {
            if (!$hasCriticalError) {
                $xmlService->processExportXml($this->ma_lk);
            }
        }
    }
}
