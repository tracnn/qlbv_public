<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use GuzzleHttp;

class XetNghiemTiemChung extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xetnghiemtiemchung:day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $ROOT_URL = 'https://xetnghiem.aita.gov.vn/services/AddTiemChung.ashx';
        $RequestType = 'POST';

        $params = array(
            'HoVaTen' => 'Nguyễn Ngọc Trác',
            'NamSinh' => '1979',
            'SDT' => '0988795445',
            'NgayLayMau' => '22/02/2022 07:00',
            'NgayXetNghiem' => '22/02/2022 07:30',
            'PhuongPhapXetNghiem' => '2',
            'KetQuaXetNghiem' => '0',
            'PhongXetNghiemID' => '181',
            'Authorization' => 'a4ba5487d8add87e6c12ac266f020542',

        );
        $client = new GuzzleHttp\Client();
        $result = $client->request($RequestType, $ROOT_URL,[
            'form_params' => $params,
        ]);
        return $result;
    }
}
