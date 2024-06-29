<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp;

class checkSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checkMOS:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra kết nối hệ thống MOS';

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
        $host = 'http://10.0.0.27'; 
        $port_mos = 1408;
        $port_mospacs = 1428;
        $port_moslist = 1429;
        $waitTimeoutInSeconds = 10;
        $to_email = 'tracnn20021979@gmail.com';
        $cc_email = ['hoanghungnh@gmail.com'];
        $sbj = 'Can not connect site ';
        $waitTimeReconnect = 180;

        do {
            /* Check MOS */
            try {
                $url = $host .':' .$port_mos;
                $client = new GuzzleHttp\Client();
                $start = microtime(true);
                $res = $client->request('GET', $url, 
                    ['timeout' => $waitTimeoutInSeconds]
                );
                $stop = microtime(true);
                $this->info('Response from ' .$url .': ' .(int)(($stop-$start)*1000) .'ms');
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                try {
                    Mail::raw($e->getMessage(), function ($mail) use($to_email, $cc_email, $sbj) {
                        $mail->to($to_email)
                        ->cc($cc_email)
                        ->subject($sbj . ' MOS!');
                    });
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
                sleep($waitTimeReconnect);
            }
            /* /Check MOS */
            /* Check MOS-PACS */
            try {
                $url = $host .':' .$port_mospacs;
                $client = new GuzzleHttp\Client();
                $start = microtime(true);
                $res = $client->request('GET', $url, 
                    ['timeout' => $waitTimeoutInSeconds]
                );
                $stop = microtime(true);
                $this->info('Response from ' .$url .': ' .(int)(($stop-$start)*1000) .'ms');
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                try {
                    Mail::raw($e->getMessage(), function ($mail) use($to_email, $cc_email, $sbj) {
                        $mail->to($to_email)
                        ->cc($cc_email)
                        ->subject($sbj . ' MOS-PACS!');
                    });
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
                sleep($waitTimeReconnect);
            }
            /* /Check MOS-PACS */
            /* Check MOS-LIS */
            try {
                $url = $host .':' .$port_moslist;
                $client = new GuzzleHttp\Client();
                $start = microtime(true);
                $res = $client->request('GET', $url, 
                    ['timeout' => $waitTimeoutInSeconds]
                );
                $stop = microtime(true);
                $this->info('Response from ' .$url .': ' .(int)(($stop-$start)*1000) .'ms');
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                try {
                    Mail::raw($e->getMessage(), function ($mail) use($to_email, $cc_email, $sbj) {
                        $mail->to($to_email)
                        ->cc($cc_email)
                        ->subject($sbj . ' MOS-LIS!');
                    });
                } catch (\Exception $e) {
                    $this->info($e->getMessage());
                }
                sleep($waitTimeReconnect);
            }
            /* /Check MOS-LIS */
            sleep(1);
        } while(true);
    }
}
