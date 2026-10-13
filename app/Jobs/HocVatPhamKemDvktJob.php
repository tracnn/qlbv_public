<?php

namespace App\Jobs;

use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Hoc lai bang tra vat pham kem DVKT me chay nen cho mot dong xml3176_vpkd_lan_hoc.
 *
 * KHONG khai $timeout (PHP Windows khong co pcntl); treo do VatPhamKemDvktHocLaiService::danhDauTreo
 * phat hien. Gia dinh CHI MOT worker tren hang doi JobXuatTepXml3176 (hai luot hoc khong bao gio
 * chay dong thoi); them worker thi phai them khoa. KHONG type-hint service tren handle(): lay bang app() (bay tiem container da gap).
 */
class HocVatPhamKemDvktJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Hong thi ghi loi, khong tu chay lai mot viec ton vai phut. */
    public $tries = 1;

    /** @var int */
    public $lanHocId;

    public function __construct($lanHocId)
    {
        $this->lanHocId = (int) $lanHocId;
    }

    public function handle()
    {
        $lan = Xml3176VpkdLanHoc::find($this->lanHocId);
        if ($lan === null || $lan->trang_thai !== Xml3176VpkdLanHoc::CHO) {
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        app(VatPhamKemDvktHocLaiService::class)->thucHien(
            $lan,
            (float) config('xml3176.vat_pham_kem_dvkt.nguong', 0.8),
            (int) config('xml3176.vat_pham_kem_dvkt.toi_thieu', 5)
        );
    }
}
