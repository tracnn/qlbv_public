<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use App\SendSmsXN;

class EMRRSVanBanChoKy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vanbanchoky:hour';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Văn bản chờ ký';

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
        $from_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000';
        $to_date = date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959';
        //$this->info($to_date);

        /* Văn bản chờ ký */
        $model_documents = DB::connection('EMR_RS')
            ->table('emr_document')
            ->select('next_signer')
            ->whereNotNull('next_signer')
            ->whereNull('rejecter')
            ->where('is_delete', 0)
            ->where('create_date', '>=', $from_date)
            ->where('create_date', '<=', $to_date)
            ->get();
        //dd($model_documents);

        $model_user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->select('loginname', 'email', 'mobile')
            ->whereNotNull('mobile')
            ->whereIn('loginname', $model_documents->pluck('next_signer'))
            ->get();
            $content = 'Hệ thống EMR thông báo: Có văn bản đang chờ ký. TTTH - BVDKNN gui ' .now();
            //$phone = '0988795445';
            //SendSmsXN::sendSmsXN($phone, $content);
        foreach ($model_user as $key => $value) {
           $phone = $value->mobile;
           $this->info($phone);
           //SendSmsXN::sendSmsXN($phone, $content);
        }

        $this->info($this->description);

    }
}
