<?php

namespace App\Jobs;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176CompleteChecker;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Buoc kiem CUOI CUNG cua chuoi: kiem tong the roi dong dau checked_at.
 *
 * Job nay luon co mat trong chuoi, ke ca khi xml_3176_not_check bat: co do chi tat
 * Xml3176CompleteChecker. Job la moc "buoc kiem da xong" - khong co no thi khong co gi
 * dung giua buoc kiem va buoc xuat.
 */
class CheckCompleteXml3176RecordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Thu lai huu han - xem chu thich o CheckXml3176TypeJob. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 240;

    protected $ma_lk;

    public function __construct($ma_lk, $chainToken = null)
    {
        $this->ma_lk = $ma_lk;
        $this->chainToken = $chainToken;
    }

    public function handle(Xml3176CompleteChecker $xmlCompleteChecker)
    {
        // Dau cua chuoi cu se cho chuoi moi xuat khi chua kiem: thoi, khong dong dau.
        if (!$this->laJobCu() && !$this->conHieuLuc($this->ma_lk)) {
            $this->catChuoi();
            return;
        }

        if (!config('organization.xml_3176_not_check', false)) {
            $xmlCompleteChecker->checkErrors($this->ma_lk);
        }

        Xml3176Information::where('ma_lk', $this->ma_lk)
            ->update(['checked_at' => now()]);
    }

    public function failed(\Throwable $e)
    {
        \Log::error('CheckCompleteXml3176RecordJob that bai: ' . $e->getMessage(), ['ma_lk' => $this->ma_lk]);

        $this->ghiNeuConHieuLuc($this->ma_lk, [
            'export_error' => 'Không xuất: bước kiểm tổng thể lỗi — ' . $e->getMessage(),
        ]);
    }
}
