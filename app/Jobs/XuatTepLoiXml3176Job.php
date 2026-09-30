<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Tao tep "danh sach loi" XML3176 chay nen cho mot yeu cau trong xml3176_tep_xuat.
 */
class XuatTepLoiXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, KHONG tu chay lai mot viec ton 12-30 phut. */
    public $tries = 1;

    /** @var int id dong xml3176_tep_xuat - nhan id chu khong nhan model: job co the cho lau */
    public $yeuCauId;

    public function __construct($yeuCauId)
    {
        $this->yeuCauId = $yeuCauId;
    }

    public function handle()
    {
    }
}
