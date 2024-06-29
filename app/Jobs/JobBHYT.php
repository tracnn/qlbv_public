<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\CheckBHYT\check_insurance;

class JobBHYT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $searchParams;

    protected $the;

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
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($index, $count, $the, $access_token, $id_token, $channel)
    {
        $this->channel = $channel;
        $this->index = $index;
        $this->count = $count;
        $this->the = $the;

        $this->username = config('__tech.BHYT.username');
        $this->password = config('__tech.BHYT.password');
        $this->access_token = $access_token;
        $this->id_token = $id_token;
        // $this->login_url = config('__tech.BHYT.login_url');
        $this->check_card_url = config('__tech.BHYT.check_card_url');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $params = array('maThe' => $this->the['maThe'], 
            'hoTen' => $this->the['hoTen'], 
            'ngaySinh' => $this->the['ngaySinh'], 
            'gioiTinh' =>  $this->the['gioiTinh'], 
            'maCSKCB' => $this->the['maCSKCB'], 
            'ngayBD' => $this->the['ngayBD'], 
            'ngayKT' => $this->the['ngayKT']);

        $result_check = $this->lichSuKCB($params);

        $result_insurance = $this->checkInsuranceCard($this->the['maThe'], 
            $this->the['hoTen'], 
            $this->the['ngaySinh']);
        if($result_insurance){
            $check_insurance = new check_insurance;
            $check_insurance->examine_number = $this->the['sophieu'];
            $check_insurance->insurance_number = $this->the['maThe'];
            $check_insurance->patient_code = $this->the['mabn'];
            $check_insurance->patient_name = $this->the['hoTen'];
            $check_insurance->birthday = $this->the['ngaySinh'];
            $check_insurance->date_examine = $this->the['ngaykham'];
            $check_insurance->clinic_code = $this->the['mapk'];
            $check_insurance->result_code = $result_insurance['maKetQua'];
            $check_insurance->check_code = $result_check['maKetQua'];
            $check_insurance->note = $result_insurance['ghiChu'];
            $check_insurance->save();

            // $redis = Redis::connection();
            // $redis->publish('5','('.$this->index.'/'.$this->count.')'.$result_insurance['ghiChu']);
            event(new \App\Events\DemoPusherEvent('('.$this->index.'/'.$this->count.')'.$result_insurance['ghiChu'], $this->channel));
        }
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

    private function lichSuKCB($params)
    {
        $params = json_encode($params);

        $url = "https://egw.baohiemxahoi.gov.vn/api/egw/nhanLichSuKCB?token=$this->access_token&id_token=$this->id_token&username=$this->username&password=$this->password";

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
