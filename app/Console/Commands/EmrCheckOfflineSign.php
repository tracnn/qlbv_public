<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use DB;

class EmrCheckOfflineSign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkofflinesign:minute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra hệ thống ký số';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $timeRepeat = 600;
        $to_email = 'tracnn20021979@gmail.com';
        $sbj = 'Hệ thống ký số không hoạt động ! ';

        do {
            /* Begin Check */
            $from_date = date("Ymd000000", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            $to_date = date("Ymd235959", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
            try {
                $countError = DB::connection('EMR_RS')
                ->table('emr_offline_sign')
				->join('emr_document', 'emr_document.id', '=', 'emr_offline_sign.tdl_document_id')
                ->where('emr_offline_sign.create_time', '>=', $from_date)
                ->where('emr_offline_sign.create_time', '<=', $to_date)
				->where('emr_document.is_delete', 0)
				->whereNull('emr_document.count_resign_failed')
                ->whereIn('emr_offline_sign.sign_stt_id', [2,3])
                ->count();
                if ($countError) {
                    $this->info($countError);
                    try {
                        Mail::raw($countError, function ($mail) use($to_email, $sbj) {
                            $mail->to($to_email)
                            ->subject($sbj);
                        });
                    } catch (\Exception $e) {
                        $this->info($e->getMessage());
                    }
                } else {
                    $this->info("Error not found!");
                }
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                try {
                    Mail::raw($e->getMessage(), function ($mail) use($to_email, $sbj) {
                        $mail->to($to_email)
                        ->cc($cc_email)
                        ->subject($sbj);
                    });
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
            }
            /* /End check */
            sleep($timeRepeat);
        } while(true);
    }
}
