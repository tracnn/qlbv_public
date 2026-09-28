<?php

namespace App\Jobs;

use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176CompleteChecker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckCompleteXml3176RecordJob implements ShouldQueue
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
    public function handle(Xml3176CompleteChecker $xmlCompleteChecker)
    {
        $xmlCompleteChecker->checkErrors($this->ma_lk);

        // Day la job kiem CUOI CUNG cua mot ho so: Xml3176Importer day cac
        // CheckXml3176TypeJob roi moi day job nay, cung mot hang doi JobXml3176
        // nen no chay sau. Dat dau da kiem xong de ExportXml3176Job - chay tren
        // hang doi khac - biet la da co du lieu loi ma hoi.
        Xml3176Information::where('ma_lk', $this->ma_lk)
            ->update(['checked_at' => now()]);
    }
}
