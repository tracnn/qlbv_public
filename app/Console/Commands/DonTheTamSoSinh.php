<?php

namespace App\Console\Commands;

use App\Models\CheckBHYT\check_hein_card;
use App\Services\Xml3176\Support\TheTamSoSinh;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Xoa ket qua tra LOI cu cua the tam tre so sinh (TE1 + noi DKBD XX000).
 *
 * jobKtTheBHYT chi xoa khi ho so duoc tra lai, ma lenh quet hang ngay chi quet BN dang nam -
 * dong cua BN da ra vien nam mai. Dung DUNG dieu kien "loi" cua job (hein_card_invalid) de
 * hai noi xoa cung mot tap. Mac dinh chi dem - co --ghi moi xoa.
 */
class DonTheTamSoSinh extends Command
{
    protected $signature = 'the-bhyt:don-the-tam
        {--ghi : Xoa that; khong co thi chi dem}';

    protected $description = 'Xoa ket qua tra the loi cu cua the tam tre so sinh (TE1 + DKBD XX000)';

    /** Oracle gioi han 1000 phan tu trong IN (...). */
    const LO = 900;

    public function handle()
    {
        $ghi = (bool) $this->option('ghi');
        $xet = 0;
        $tam = 0;
        $khongThay = 0;
        $daXoa = 0;

        $q = check_hein_card::query()->where(function ($w) {
            $w->whereIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
              ->orWhereIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'));
        });

        try {
            $q->select('id', 'ma_lk', 'ma_the_gui', 'ma_dkbd_gui')
              ->chunkById(self::LO, function ($lo) use ($ghi, &$xet, &$tam, &$khongThay, &$daXoa) {
                $xet += $lo->count();

                // Uu tien gia tri da gui; chi hoi HIS cho dong chua co.
                $canHis = $lo->filter(function ($r) {
                    return trim((string) $r->ma_the_gui) === '';
                })->pluck('ma_lk')->unique()->values()->all();

                $his = empty($canHis) ? collect() : DB::connection('HISPro')->table('his_treatment')
                    ->whereIn('treatment_code', $canHis)
                    ->get(['treatment_code', 'tdl_hein_card_number', 'tdl_hein_medi_org_code'])
                    ->keyBy('treatment_code');

                $xoa = [];

                foreach ($lo as $r) {
                    if (trim((string) $r->ma_the_gui) !== '') {
                        $the = $r->ma_the_gui;
                        $dkbd = $r->ma_dkbd_gui;
                    } elseif ($h = $his->get($r->ma_lk)) {
                        $the = $h->tdl_hein_card_number;
                        $dkbd = $h->tdl_hein_medi_org_code;
                    } else {
                        $khongThay++;
                        continue;
                    }

                    if (TheTamSoSinh::la($the, $dkbd)) {
                        $tam++;
                        $xoa[] = $r->id;
                    }
                }

                if ($ghi && $xoa) {
                    $daXoa += DB::table((new check_hein_card)->getTable())->whereIn('id', $xoa)->delete();
                }
            });
        } catch (\Exception $e) {
            Log::error('the-bhyt:don-the-tam loi', ['loi' => $e->getMessage()]);
            $this->error('Loi: ' . $e->getMessage() . ' (lo da xoa van giu, chay lai duoc)');

            return 1;
        }

        $this->info('Xet: ' . $xet . ' | The tam: ' . $tam . ' | Khong thay tren HIS: ' . $khongThay);

        if ($ghi) {
            $this->info('Da xoa: ' . $daXoa);
        }

        return 0;
    }
}
