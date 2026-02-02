<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Carbon\Carbon;

use App\Services\Xml3176Service;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Xml1;

class ExportXml3176Job implements ShouldQueue
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
    public function handle(Xml3176Service $xmlService)
    {
        // Kiểm tra nếu không có lỗi nghiêm trọng trước khi xuất XML
        $hasCriticalError = Xml3176ErrorResult::where('ma_lk', $this->ma_lk)
            ->where('critical_error', true)
            ->exists();

        // Lấy hồ sơ XML theo ma_lk
        $xmlRecord = Xml3176Xml1::where('ma_lk', $this->ma_lk)->first();

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
