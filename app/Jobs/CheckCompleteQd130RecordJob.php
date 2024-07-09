<?php

namespace App\Jobs;

use App\Services\Qd130XmlCompleteChecker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CheckCompleteQd130RecordJob implements ShouldQueue
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
    public function handle(Qd130XmlCompleteChecker $xmlCompleteChecker)
    {
         $xmlCompleteChecker->checkErrors($this->ma_lk);
    }
}
