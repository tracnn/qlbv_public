<?php

namespace App\Console\Commands;

use App\Models\CheckBHYT\check_hein_card;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bu gia tri DA GUI (so the, ho ten, ngay sinh, noi DKBD) cho ket qua tra the cu, lay tu HIS.
 *
 * Tu Task 2 job tu ghi cac cot nay; lenh chi can chay MOT lan cho du lieu truoc do. Mac dinh
 * chi dem - co --ghi moi ghi.
 */
class BuThongTinGuiTheBhyt extends Command
{
    protected $signature = 'the-bhyt:bu-thong-tin-gui
        {--ghi : Ghi that; khong co thi chi dem}
        {--tat-ca : Xet ca dong hop le, khong chi dong loi}';

    protected $description = 'Bu so the/ho ten/ngay sinh/noi DKBD da gui cho ket qua tra the cu (lay tu HIS)';

    /** Oracle gioi han 1000 phan tu trong IN (...). */
    const LO = 900;

    public function handle()
    {
        $ghi = (bool) $this->option('ghi');
        $bang = (new check_hein_card)->getTable();

        $q = check_hein_card::query()
            ->where(function ($w) {
                $w->whereNull('ma_the_gui')->orWhere('ma_the_gui', '');
            });

        if (!$this->option('tat-ca')) {
            $q->chiLoi();
        }

        $thay = 0;
        $khongThay = 0;

        try {
            // chunkById (khong phai chunk): dong vua ghi roi khoi dieu kien loc, chunk theo
            // offset se nhay coc bo sot dong.
            $q->select('id', 'ma_lk')->chunkById(self::LO, function ($lo) use ($ghi, $bang, &$thay, &$khongThay) {
                $his = DB::connection('HISPro')->table('his_treatment')
                    ->whereIn('treatment_code', $lo->pluck('ma_lk')->unique()->values()->all())
                    ->get(['treatment_code', 'tdl_hein_card_number', 'tdl_patient_name',
                           'tdl_patient_dob', 'tdl_hein_medi_org_code'])
                    ->keyBy('treatment_code');

                foreach ($lo as $r) {
                    $h = $his->get($r->ma_lk);

                    if (!$h) {
                        $khongThay++;
                        continue;
                    }

                    $thay++;

                    if ($ghi) {
                        // DB::table: KHONG cham updated_at - do la "thoi gian tra cuu" tren man.
                        DB::table($bang)->where('id', $r->id)->update([
                            'ma_the_gui'    => $h->tdl_hein_card_number,
                            'ho_ten_gui'    => $h->tdl_patient_name,
                            // Cung ham dob() lenh quet dung: du lieu bu giong du lieu ghi moi.
                            'ngay_sinh_gui' => $h->tdl_patient_dob ? dob($h->tdl_patient_dob) : null,
                            'ma_dkbd_gui'   => $h->tdl_hein_medi_org_code,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('the-bhyt:bu-thong-tin-gui loi', ['loi' => $e->getMessage()]);
            $this->error('Loi: ' . $e->getMessage() . ' (lo da ghi van giu, chay lai duoc)');

            return 1;
        }

        $this->info(($ghi ? 'Da bu: ' : 'Se bu: ') . $thay . ' | Khong thay tren HIS: ' . $khongThay);

        return 0;
    }
}
