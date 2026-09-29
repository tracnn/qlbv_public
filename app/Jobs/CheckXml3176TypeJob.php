<?php

namespace App\Jobs;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\Xml3176ErrorService;
use App\Services\Xml3176\Xml3176CheckTypes;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Kiem loi MOT loai XML cua MOT ho so.
 *
 * Thay cho CheckXml3176ErrorsJob (mot job moi DONG): ho so 600 dong sinh 600 job, moi
 * job serialize ca mot model.
 *
 * Job TU XOA loi cua rieng loai minh truoc khi ghi, nen tu idempotent: chay lai bao
 * nhieu lan cung ra mot ket qua, khong phu thuoc thu tu hang doi hay retry.
 */
class CheckXml3176TypeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ThuocChuoiXml3176;

    /** So dong nap moi lan, tranh giu ca bang trong bo nho. */
    const CO_LO = 500;

    /**
     * Thu lai HUU HAN. queue:work mac dinh --tries=0 la thu lai VO HAN, va moi hang doi chi co
     * mot worker: mot ho so doc se chan dung moi ho so xep sau no.
     */
    public $tries = 2;

    /** Phai nho hon retry_after (300) cua ket noi database. */
    public $timeout = 240;

    protected $maLk;
    protected $xmlType;

    public function __construct($maLk, $xmlType, $chainToken = null)
    {
        $this->maLk = $maLk;
        $this->xmlType = $xmlType;
        $this->chainToken = $chainToken;
    }

    public function handle()
    {
        // Ho so da co chuoi moi hon (nap lai / lenh cuu): thoi, KHONG xoa hay ghi loi - ket qua
        // kiem la cua chuoi moi. Job cu (khong mang ma) van kiem nhu thuong.
        if (!$this->laJobCu() && !$this->conHieuLuc($this->maLk)) {
            $this->catChuoi();
            return;
        }

        $cauHinh = Xml3176CheckTypes::cauHinh($this->xmlType);
        $model   = $cauHinh['model'];

        // Xoa loi CUA RIENG LOAI NAY. Khong dung deleteErrors() vi ham do xoa TOAN BO
        // loi cua ho so - dung no o day thi job nay se xoa mat ket qua cua 11 job kia.
        Xml3176ErrorResult::where('ma_lk', $this->maLk)
            ->where('xml', $this->xmlType)
            ->delete();

        $checker = app($cauHinh['checker']);
        $loi     = app(Xml3176ErrorService::class);

        $loi->batDauGom();

        try {
            $model::where('ma_lk', $this->maLk)
                ->chunk(self::CO_LO, function ($dong) use ($checker) {
                    foreach ($dong as $d) {
                        $checker->checkErrors($d);
                    }
                });
        } finally {
            // Hong giua chung thi phan da tim duoc van ghi, va bo dem khong ro sang job sau.
            $loi->ketThucGom();
        }
    }

    /**
     * Het luot thu. Laravel tu bo phan con lai cua chuoi, nen ho so se khong duoc xuat - ghi
     * ly do vao cot XUAT vi do la hau qua nguoi van hanh nhin thay.
     */
    public function failed(\Throwable $e)
    {
        \Log::error('CheckXml3176TypeJob that bai: ' . $e->getMessage(), [
            'ma_lk' => $this->maLk, 'xml' => $this->xmlType,
        ]);

        $this->ghiNeuConHieuLuc($this->maLk, [
            'export_error' => 'Không xuất: bước kiểm ' . $this->xmlType . ' lỗi — ' . $e->getMessage(),
        ]);
    }
}
