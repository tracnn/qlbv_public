<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\Tt12\Kiem\Tt12Kiem;

/**
 * Kiem mot ho so trong hang doi.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho rat lau, va mot model
 * serialize san se mang theo du lieu da cu.
 */
class CheckTt12Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $tries = 2;
    public $timeout = 600;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle(Tt12Kiem $kiem)
    {
        $hoSo = Tt12HoSo::where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi.
            Log::info('CheckTt12Job: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $soLoi = $kiem->kiem($hoSo);

        Log::info('CheckTt12Job: da kiem ' . $this->maHoSo, array(
            'so_dong' => (int) $hoSo->so_dong,
            'so_loi'  => $soLoi,
        ));
    }
}
