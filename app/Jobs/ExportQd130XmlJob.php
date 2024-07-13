<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Services\Qd130XmlService;
use App\Models\BHYT\Qd130XmlErrorResult;

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

        if (!$hasCriticalError) {
            $xmlService->processExportXml($this->ma_lk);
        }
    }
}
