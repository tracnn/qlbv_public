<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\CheckBHYT\check_hein_card;

class jobKtTheBHYT implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $params;
    protected $username;
    protected $password;
    protected $check_card_url;
    protected $login_url;

    protected $checkOldValue;

    public function __construct($params, $checkOldValue = true)
    {
        $this->params = $params;
        $this->checkOldValue = $checkOldValue;

        // Lưu các giá trị cấu hình trong các biến đơn giản để tránh vấn đề tuần tự hóa
        $this->username = config('__tech.BHYT.username');
        $this->password = config('__tech.BHYT.password');
        $this->check_card_url = config('__tech.BHYT.check_card_url_2024');
        $this->login_url = config('__tech.BHYT.login_url');
    }

    public function handle()
    {
        if (!config('__tech.BHYT.enableCheck')) {
            return;
        }

        // Cấu hình kiểm tra từ kết quả tra cứu cũ. Mặc định là true
        if ($this->checkOldValue) {
            $existingCardCheck = check_hein_card::where('ma_lk', $this->params['ma_lk'])
            ->whereNotIn('ma_kiemtra', config('qd130xml.hein_card_invalid.check_code'))
            ->whereNotIn('ma_tracuu', config('qd130xml.hein_card_invalid.result_code'))
            ->first();

            if ($existingCardCheck) {
                return;
            }
        }

        $client = new Client();

        // Kiểm tra token trong cache
        $tokens = Cache::get('bhyt_tokens');

        if (!$tokens || strtotime($tokens['expires_in']) < time()) {
            // Đăng nhập để lấy token mới
            try {
                $loginResponse = $client->post($this->login_url, [
                    'form_params' => [
                        'username' => $this->username,
                        'password' => $this->password,
                    ]
                ]);

                $loginData = json_decode($loginResponse->getBody(), true);

                if (!isset($loginData['APIKey'])) {
                    Log::error('Login failed: APIKey not found in response');
                    return;
                }

                $tokens = [
                    'access_token' => $loginData['APIKey']['access_token'],
                    'id_token' => $loginData['APIKey']['id_token'],
                    'expires_in' => strtotime($loginData['APIKey']['expires_in']),
                ];

                // Lưu token vào cache
                Cache::put('bhyt_tokens', $tokens, now()->addSeconds($tokens['expires_in'] - time()));
            } catch (\Exception $e) {
                Log::error('Error in jobKtTheBHYT: ' . $e->getMessage());
                return;
            }
        }

        // Thực hiện kiểm tra thẻ bảo hiểm y tế
        $this->checkInsuranceCard($client, $tokens['access_token'], $tokens['id_token'], $this->params);
    }

    private function checkInsuranceCard($client, $access_token, $id_token, $params)
    {
        $result_check = null;
        $error_message = null;

        try {
            $response = $client->post($this->check_card_url, [
                'form_params' => [
                    'maThe' => $params['maThe'],
                    'hoTen' => $params['hoTen'],
                    'ngaySinh' => $params['ngaySinh'],
                    'hoTenCb' => config('__tech.BHYT.hoTenCb'),
                    'cccdCb' => config('__tech.BHYT.cccdCb'),
                ],
                'query' => [
                    'token' => $access_token,
                    'id_token' => $id_token,
                    'username' => $this->username,
                    'password' => $this->password,
                ]
            ]);

            $result_check = json_decode($response->getBody(), true);

            // $result_insurance_code = $this->lichSuKCB($params, $result_check['maDKBD'], $result_check['gioiTinh'], 
            //     $result_check['gtTheTu'], $result_check['gtTheDen']);
            // $this->addCheckHeinCard($params['ma_lk'], $result_check['maKetQua'], $result_insurance_code, $result_check);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            Log::error('Error during checkInsuranceCard: ' . $responseBody);

            // Parse the error response
            $result_check = json_decode($responseBody, true);
        } catch (\Exception $e) {
            Log::error('Unexpected error during checkInsuranceCard: ' . $e->getMessage());
            $error_message = $e->getMessage();
        } finally {
            // Ensure addCheckHeinCard is called regardless of error
            $result_insurance_code = $this->lichSuKCB(
                $params, 
                $result_check['maDKBD'] ?? null, 
                $result_check['gioiTinh'] ?? null, 
                $result_check['gtTheTu'] ?? null, 
                $result_check['gtTheDen'] ?? null
            );
            $this->addCheckHeinCard(
                $params['ma_lk'], 
                $result_check['maKetQua'], 
                $result_insurance_code, 
                $result_check
            );
        }
    }

    private function lichSuKCB($params, $maDKBD, $gioiTinh, $gtTheTu, $gtTheDen)
    {
        if (!$gioiTinh || !$maDKBD || !$gtTheTu || !$gtTheDen) {
            return '11';
        }
        if ($params['maCSKCB'] != $maDKBD) {
            return '09';
        }
        if (($params['gioiTinh'] == 1 && $gioiTinh == 'Nữ') || ($params['gioiTinh'] == 2 && $gioiTinh == 'Nam')) {
            return '08';
        }
        return '00';
    }

    private function addCheckHeinCard($ma_lk, $ma_tracuu, $ma_kiemtra, $result_check)
    {
        $checkHeinCard = check_hein_card::updateOrCreate(
            [
                'ma_lk' => $ma_lk,
            ],
            [
                'ma_tracuu' => $ma_tracuu,
                'ma_kiemtra' => $ma_kiemtra,
                'ma_ketqua' => $result_check['maKetQua'],
                'ghi_chu' => $result_check['ghiChu'],
                'ma_the' => $result_check['maThe'],
                'ho_ten' => $result_check['hoTen'],
                'ngay_sinh' => $result_check['ngaySinh'],
                'dia_chi' => $result_check['diaChi'],
                'ma_the_cu' => $result_check['maTheCu'],
                'ma_the_moi' => $result_check['maTheMoi'],
                'ma_dkbd' => $result_check['maDKBD'],
                'cq_bhxh' => $result_check['cqBHXH'],
                'gioi_tinh' => $result_check['gioiTinh'],
                'gt_the_tu' => $result_check['gtTheTu'],
                'gt_the_den' => $result_check['gtTheDen'],
                'ma_kv' => $result_check['maKV'],
                'ngay_du5nam' => $result_check['ngayDu5Nam'],
                'maso_bhxh' => $result_check['maSoBHXH'],
                'gt_the_tumoi' => $result_check['gtTheTuMoi'],
                'gt_the_denmoi' => $result_check['gtTheDenMoi'],
                'ma_dkbd_moi' => $result_check['maDKBDMoi'],
                'ten_dkbd_moi' => $result_check['tenDKBDMoi'],
            ]
        );
        $checkHeinCard->touch();
    }
}