<?php

namespace App\Jobs;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176Information;
use App\Services\Xml3176\KiemHoSoXml3176;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Buoc kiem cua chuoi XML3176: MOT job cho ca ho so - moi loai XML co checker roi kiem tong
 * the, cuoi cung dong dau checked_at. Thay chuoi "CheckXml3176TypeJob x N ->
 * CheckCompleteXml3176RecordJob" (hai lop do giu lai cho job da xep truoc khi nang cap).
 *
 * Mot job bao trum 12 loai + kiem tong the nen ke ho giua lan so token dau job va lan ghi cuoi
 * dai hon chuoi cu: so token TRUOC MOI LAN xoa/ghi loi (KiemHoSoXml3176 goi nguoi canh), lech
 * -> cat chuoi, khong ghi tiep; dong dau checked_at co dieu kien token.
 *
 * Job luon co mat trong chuoi, ke ca khi danh sach loai rong hoac tat kiem tong the: no la moc
 * "kiem xong" dung giua buoc kiem va buoc xuat.
 */
class KiemHoSoXml3176Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** Thu lai huu han - xem chu thich o CheckXml3176TypeJob. */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 240;

    protected $maLk;

    /** @var string[] cac loai XML can kiem (co checker) */
    protected $loaiXml;

    public function __construct($maLk, array $loaiXml, $chainToken = null)
    {
        $this->maLk = $maLk;
        $this->loaiXml = array_values($loaiXml);
        $this->chainToken = $chainToken;
    }

    public function handle()
    {
        // Ho so da co chuoi moi hon: thoi, khong xoa/ghi loi, khong dong dau.
        if (!$this->laJobCu() && !$this->conHieuLuc($this->maLk)) {
            $this->catChuoi();
            return;
        }

        // Job khong ma ma ho so DA co ma phien = ho so thuoc ve chuoi moi (quy tac F1 cua
        // CheckCompleteXml3176RecordJob): thoi, khong kiem, khong dong dau.
        if ($this->laJobCu() && Xml3176Information::where('ma_lk', $this->maLk)->whereNotNull('chain_token')->exists()) {
            return;
        }

        // Token vua so o tren: chay() bo lan so dau tien, so lai truoc moi lan xoa/ghi sau do.
        $xong = app(KiemHoSoXml3176::class)->chay($this->maLk, $this->loaiXml, function () {
            return $this->laJobCu() || $this->conHieuLuc($this->maLk);
        });

        if (!$xong) {
            $this->catChuoi();
            return;
        }

        if ($this->laJobCu()) {
            Xml3176Information::where('ma_lk', $this->maLk)
                ->update(['checked_at' => now()]);
            return;
        }

        // Mot cau lenh co dieu kien token: khong dong dau du lieu cua chuoi moi chua kiem. Ho so
        // vua doi token (sau lan so cuoi) thi khong dong dau duoc -> cat chuoi luon.
        // MySQL dem dong DA DOI (khong bat FOUND_ROWS): checked_at trung dung giay hien tai thi
        // tra 0 va chuoi bi cat nham - gan nhu khong the (moi lan nap/chay lai deu sinh token moi).
        if (!$this->ghiNeuConHieuLuc($this->maLk, ['checked_at' => now()])) {
            $this->catChuoi();
        }
    }

    /**
     * Het luot thu. Laravel tu bo phan con lai cua chuoi, nen ho so se khong duoc xuat - ghi
     * ly do vao cot XUAT vi do la hau qua nguoi van hanh nhin thay.
     */
    public function failed(\Throwable $e)
    {
        \Log::error('KiemHoSoXml3176Job that bai: ' . $e->getMessage(), [
            'ma_lk' => $this->maLk, 'xml' => $this->loaiXml,
        ]);

        $this->ghiNeuConHieuLuc($this->maLk, [
            'export_error' => 'Không xuất: bước kiểm hồ sơ lỗi — ' . $e->getMessage(),
        ]);
    }
}
