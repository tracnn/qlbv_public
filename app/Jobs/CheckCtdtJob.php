<?php

namespace App\Jobs;

use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\Kiem\CtdtChecker;

/**
 * Kiem noi dung MOT ho so chung tu dien tu.
 *
 * Nhan MA HO SO chu khong nhan model: job co the nam cho trong hang doi rat lau, va mot
 * model serialize san se mang theo du lieu da cu.
 *
 * TU IDEMPOTENT: xoa het loi cu cua ho so roi ghi lai tu dau, nen chay bao nhieu lan cung
 * ra mot ket qua - hang doi giao lai sau khi that bai giua chung khong lam nhan doi loi.
 */
class CheckCtdtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 120;

    /** @var string */
    protected $maHoSo;

    public function __construct($maHoSo)
    {
        $this->maHoSo = $maHoSo;
    }

    public function handle()
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $this->maHoSo)->first();

        if ($hoSo === null) {
            // Ho so co the da bi xoa trong luc job cho trong hang doi. Nem o day chi lam
            // job that bai va thu lai ba lan cho cung mot ket qua.
            \Log::info('CheckCtdtJob: khong tim thay ho so ' . $this->maHoSo);

            return;
        }

        $loi = [];

        foreach ($hoSo->chungTu as $chungTu) {
            foreach ($this->kiemMotChungTu($hoSo, $chungTu) as $mot) {
                $mot['ho_so_id'] = $hoSo->id;
                $mot['chung_tu_id'] = $chungTu->id;
                $loi[] = $mot;
            }
        }

        $soChan = 0;

        foreach ($loi as $mot) {
            if ($mot['muc_do'] === 'chan') {
                $soChan++;
            }
        }

        // MOT transaction cho ca ba viec. so_loi, cac ban ghi ctdt_loi va checked_at la ba
        // cach dien dat cung mot ket qua kiem; ghi roi ra thi mot lan hong giua chung se
        // de man danh sach, bo loc "chi ho so con loi" va tab Loi noi ba dieu khac nhau.
        DB::transaction(function () use ($hoSo, $loi, $soChan) {
            CtdtLoi::where('ho_so_id', $hoSo->id)->delete();

            foreach ($loi as $mot) {
                CtdtLoi::create($mot);
            }

            $hoSo->update([
                'so_loi'     => $soChan,
                'checked_at' => now(),
            ]);
        });
    }

    /**
     * @return array Cac loi cua mot chung tu, chua gan ho_so_id/chung_tu_id
     */
    private function kiemMotChungTu(CtdtHoSo $hoSo, $chungTu)
    {
        if (!CtdtLoaiRegistry::co($chungTu->loai_ho_so)) {
            // Registry co the bi thu hep sau khi du lieu da duoc ghi. Bo qua thay vi nem:
            // nem thi mot loai da go se lam moi lan kiem cua moi ho so cu deu that bai.
            \Log::warning('CheckCtdtJob: loai la trong CSDL - ' . $chungTu->loai_ho_so);

            return [];
        }

        $lop = CtdtLoaiRegistry::cho($chungTu->loai_ho_so);
        $tenModel = $lop::model();
        $chiTiet = $tenModel::where('chung_tu_id', $chungTu->id)->first();

        if ($chiTiet === null) {
            return [];
        }

        // Dung lai mang TEN THE => gia tri de bo kiem khong phai biet ten cot.
        $duLieu = [];

        foreach ($lop::truong() as $the => $cot) {
            $duLieu[$the] = $chiTiet->{$cot};
        }

        return CtdtChecker::kiem($chungTu->loai_ho_so, $duLieu, $hoSo->macskcb);
    }
}
