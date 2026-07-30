<?php

namespace App;

use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;

class BHYT
{
    /* 
        function: Đăng nhập cổng BHXH
    */

    public static function loginBHYT()
    {
        $username = config('organization.BHYT.username');
        $password = config('organization.BHYT.password');
        $login_url = config('organization.BHYT.login_url');

        $client = new GuzzleHttp\Client();

        $res = $client->request('POST', $login_url,
            [
            'form_params' => [
                'username' => $username,
                'password' => $password       
            ]
        ]);

        return json_decode($res->getBody(), true);
    }

    /**
     * @param \App\Services\BHYTLoginService|null $loginService tai khoan CUA CO SO duoc chon.
     *        Co truyen thi lay username/password tu no; khong truyen thi giu nguyen hanh vi cu
     *        (khoi BHYT chot cung). De tuy chon nen 5 noi goi con lai khong phai doi.
     */
    public static function checkInsuranceCard($number, $name, $birthday,$access_token,$id_token,
        $loginService = null)
    {
        $check_card_url = config('organization.BHYT.check_card_url_2024');

        if ($loginService !== null) {
            $username = $loginService->username();
            $password = $loginService->password();
        } else {
            $username = config('organization.BHYT.username');
            $password = config('organization.BHYT.password');
        }

        if (config('organization.BHYT.check_by_user')) {
            $user = \Auth::user();
            $hoTenCb =  $user->username;
            $cccdCb = $user->his_employee->identification_number;
        } elseif ($loginService !== null) {
            // Can bo tra cuu cua CHINH CO SO duoc chon. Truoc day nhanh nay doc khoi BHYT cu
            // (chot cung, va dang de rong), nen cong tra ve "Null hoTenCb" du da chon co so.
            $hoTenCb = $loginService->hoTenCb();
            $cccdCb = $loginService->cccdCb();
        } else {
            // TAM: loginBHYT() la method tinh khong co tham so co so, duoc goi tu nhieu
            // controller. Nhung noi chua truyen $loginService van doc khoi BHYT cu; spec thu
            // hai se chuyen not.
            $hoTenCb =  config('organization.BHYT.hoTenCb');
            $cccdCb = config('organization.BHYT.cccdCb');
        }

        $client = new GuzzleHttp\Client();
        try {
            $res = $client->request('POST', $check_card_url,
                [
                'form_params' => [
                    'maThe' => $number,
                    'hoTen' => $name,
                    'ngaySinh' => $birthday,
                    'hoTenCb' => $hoTenCb,
                    'cccdCb' => $cccdCb,
                ],
                'query' => [
                    'token' => $access_token,
                    'id_token' => $id_token,
                    'username' => $username,
                    'password' => $password,
                ]
            ]);
            return json_decode($res->getBody(), true);
        } catch (ClientException $e) {
            // Catching the exception and extracting the response body
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        }
    }

    public static function lichSuKCB($params,
        $maDKBD,
        $gioiTinh,
        $gtTheTu,
        $gtTheDen)
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

    public static function nhanThongTinCSKCB($params)
    {
        $params = json_encode($params);

        $url = "http://egw.baohiemxahoi.gov.vn/api/egw/NhanThongTinCSKCB";

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