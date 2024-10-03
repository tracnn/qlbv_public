<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class JobInpatient implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;

    protected $username;
    protected $password;
    // protected $login_url;
    protected $check_card_url;

    protected $access_token;
    protected $id_token;
    // protected $token_type;  
    // protected $expires_in;

    protected $index;
    protected $count;
    protected $channel;

    protected $ngay;
    protected $tenkhp;
    protected $act;
    protected $madoituong;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    //,$ngay,$tenkhp,$act,$madoituong
    public function __construct($index,$count,
        $params,$access_token,$id_token,
        $channel,$ngay,$tenkhp,$act,$madoituong)
    {
        $this->channel = $channel;
        $this->index = $index;
        $this->count = $count;
        $this->params = $params;

        $this->username = config('organization.BHYT.username');
        $this->password = config('organization.BHYT.password');
        $this->access_token = $access_token;
        $this->id_token = $id_token;
        $this->check_card_url = config('organization.BHYT.check_card_url');

        $this->ngay = $ngay;
        $this->tenkhp = $tenkhp;
        $this->act = $act;
        $this->madoituong = $madoituong;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $result_insurance = $this->lichSuKCB($this->params,
            $this->access_token,
            $this->id_token);

        $result_check = $this->checkInsuranceCard($this->params['maThe'],
            $this->params['hoTen'],
            $this->params['ngaySinh']);

        if($result_insurance){
            $sothe = $this->params['maThe'] ? $this->params['maThe'] : '';
            $macskcb = $this->params['maCSKCB'] ? $this->params['maCSKCB'] : '';
            $thoihantu = $this->params['ngayBD'] ? $this->params['ngayBD'] : '';
            $thoihanden = $this->params['ngayKT'] ? $this->params['ngayKT'] : '';
            $ngay = $this->ngay;

            event(new \App\Events\CheckInpatientEvent($this->index, $this->count, 
                $this->params['hoTen'], $sothe, $this->params['ngaySinh'], 
                $macskcb, $thoihantu, $thoihanden, $ngay, $this->tenkhp, config('__tech.trangthai_noitru')[$this->act], config('__tech.duoc_doituong')[$this->madoituong], config('__tech.insurance_error_code')[$result_check['maKetQua']], config('__tech.check_insurance_code')[$result_insurance['maKetQua']], $this->channel));
        }
    }

    private function lichSuKCB($params,$access_token,$id_token)
    {
        $params = json_encode($params);

        $username = config('organization.BHYT.username');
        $password = config('organization.BHYT.password'); 
        $url = "https://egw.baohiemxahoi.gov.vn/api/egw/nhanLichSuKCB?token=$access_token&id_token=$id_token&username=$username&password=$password";

        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch); 
        return json_decode($result, true);
    }

    private function checkInsuranceCard($number, $name, $birthday)
    {
        $card = array('maThe' => $number, 'hoTen' => $name, 'ngaySinh' => $birthday);
        $params = json_encode($card);
        $url = "$this->check_card_url?token=$this->access_token&id_token=$this->id_token&username=$this->username&password=$this->password";
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $result = curl_exec($ch);
        curl_close($ch); 
        return json_decode($result, true);
    }

}
