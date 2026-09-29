<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Services\Xml3176Service;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Information;
use App\Models\BHYT\Xml3176Xml1;

/**
 * Buoc XUAT cua chuoi kiem -> xuat -> ky -> gui: mot cua kiem roi ghi tep cho ky.
 *
 * Job chi chay SAU buoc kiem tong the vi no nam sau buoc do trong cung mot chuoi
 * (Xml3176ChuoiXuLy). Truoc 29/09/2026 job chay song song voi buoc kiem va phai cho theo thoi
 * gian (15s x 10); ngay nap lo 29/09 hang doi kiem ton toi 90 phut, 1.934 ho so sach het luot
 * cho va khong len cong.
 *
 * Moi lan DUNG co chu dich deu ghi ly do vao export_error roi cat chuoi - khong nem, vi nem
 * la ton luot thu cho mot viec khong he hong.
 */
class ExportXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Thu lai huu han - queue:work mac dinh thu lai vo han. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 120;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176Service $xmlService)
    {
        if ($this->laJobCu()) {
            // Job dang cu (vong cho 15s x 10) con nam trong hang doi luc nang cap. Phia sau no
            // khong con buoc nao; lenh xml3176:chay-lai-tu-xuat gom ho so nay lai.
            Log::info('ExportXml3176Job: job dang cu, bo qua ma_lk ' . $this->ma_lk);
            return;
        }

        if (!$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        $thongTin = Xml3176Information::where('ma_lk', $this->ma_lk)->first();

        if (empty($thongTin->checked_at)) {
            $this->dung('Không xuất: hồ sơ chưa kiểm xong');
            return;
        }

        $xml1 = Xml3176Xml1::where('ma_lk', $this->ma_lk)->first();

        if ($xml1 && $this->ngayRaOTuongLai($xml1->ngay_ra)) {
            $this->dung('Không xuất: ngày ra (' . $xml1->ngay_ra . ') sau thời điểm xuất');
            return;
        }

        if (!config('organization.export_xml_not_check')) {
            $soLoi = Xml3176ErrorResult::where('ma_lk', $this->ma_lk)
                ->where('critical_error', true)
                ->count();

            if ($soLoi > 0) {
                $this->dung('Không xuất: còn ' . $soLoi . ' lỗi nghiêm trọng');
                return;
            }
        }

        if (!$xmlService->xuatTepChoKy($this->ma_lk)) {
            $this->dung('Xuất lỗi — không dựng được dữ liệu XML của hồ sơ');
        }
    }

    public function failed(\Throwable $e)
    {
        Log::error('ExportXml3176Job that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, ['export_error' => 'Xuất lỗi — ' . $e->getMessage()]);
    }

    private function dung($lyDo)
    {
        Xml3176Information::where('ma_lk', $this->ma_lk)->update(['export_error' => $lyDo]);
        $this->catChuoi();
    }

    /** ngay_ra dang YmdHi (12 ky tu). Sai dang thi coi nhu khong o tuong lai, nhu truoc day. */
    private function ngayRaOTuongLai($ngayRa)
    {
        if (!$ngayRa || strlen($ngayRa) != 12) {
            return false;
        }

        try {
            return Carbon::createFromFormat('YmdHi', $ngayRa)->gt(Carbon::now());
        } catch (\Exception $e) {
            Log::warning('Invalid date format for ngay_ra: ' . $ngayRa);
            return false;
        }
    }
}
