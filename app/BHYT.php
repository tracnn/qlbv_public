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
        $username = config('__tech.BHYT.username');
        $password = config('__tech.BHYT.password');
        $login_url = config('__tech.BHYT.login_url');

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

    public static function checkInsuranceCard($number, $name, $birthday,$access_token,$id_token)
    {
        $check_card_url = config('__tech.BHYT.check_card_url_2024');
        $username = config('__tech.BHYT.username');
        $password = config('__tech.BHYT.password');

        if (config('__tech.BHYT.check_by_user')) {
            $user = \Auth::user();
            $hoTenCb =  $user->username;
            $cccdCb = $user->his_employee->identification_number;
        } else {
            $hoTenCb =  config('__tech.BHYT.hoTenCb');
            $cccdCb = config('__tech.BHYT.cccdCb');
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

    public static function nhanChiTietHSNgay4210($params)
    {
        $params = json_encode($params);

        // $username = config('__tech.BHYT.username');
        // $password = config('__tech.BHYT.password'); 
        $url = "https://egw.baohiemxahoi.gov.vn/api/egw/nhanChiTietHSNgay4210";

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