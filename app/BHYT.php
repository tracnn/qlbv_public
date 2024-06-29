<?php

namespace App;

use GuzzleHttp;

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

        // /* Lấy thống tin lần đăng nhập trước */
        // $result = array("maKetQua" => session()->get("maKetQua"),
        //     "APIKey" => array("access_token" => session()->get("access_token"),
        //         "id_token" => session()->get("id_token"),
        //         "token_type" => session()->get("token_type"),
        //         "username" => session()->get("username"),
        //         "expires_in" => session()->get("expires_in")
        //     )
        // );
        
        // /* Kiểm tra nếu lần đăng nhập trước vẫn còn hiệu lực thì không cần đăng nhập lại nữa */
        // if ( ($result["APIKey"]["expires_in"]) && (now() < $result["APIKey"]["expires_in"]) 
        // && $result["APIKey"]["username"]
        // && $result["APIKey"]["token_type"] && $result["APIKey"]["id_token"]
        // && $result["APIKey"]["access_token"] && ($result["maKetQua"]=="200"))
        // {
        //     return $result;
        // }

        $client = new GuzzleHttp\Client();

        $res = $client->request('POST', $login_url,
            [
            'form_params' => [
                'username' => $username,
                'password' => $password       
            ]
        ]);

        // if ($res->getBody()) {
        //     $json = json_decode($res->getBody(), true);
        //     session()->put("maKetQua", $json["maKetQua"]);
        //     session()->put("access_token", $json["APIKey"]["access_token"]);
        //     session()->put("id_token", $json["APIKey"]["id_token"]);
        //     session()->put("token_type", $json["APIKey"]["token_type"]);
        //     session()->put("username", $json["APIKey"]["username"]);
        //     session()->put("expires_in", $json["APIKey"]["expires_in"]);
        // }
        return json_decode($res->getBody(), true);
    }

    public static function checkInsuranceCard($number, $name, $birthday,$access_token,$id_token)
    {
        $check_card_url = config('__tech.BHYT.check_card_url');
        $username = config('__tech.BHYT.username');
        $password = config('__tech.BHYT.password');
        // $card = array('maThe' => $number, 'hoTen' => $name, 'ngaySinh' => $birthday);
        // $params = json_encode($card);
        // $url = "$check_card_url?token=$access_token&id_token=$id_token&username=$username&password=$password";
        // $ch=curl_init($url);
        // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        // $result = curl_exec($ch);
        // curl_close($ch); 
        // return json_decode($result, true);

        $client = new GuzzleHttp\Client();

        $res = $client->request('POST', $check_card_url,
            [
            'form_params' => [
                'maThe' => $number,
                'hoTen' => $name,
                'ngaySinh' => $birthday,       
            ],
            'query' => [
                'token' => $access_token,
                'id_token' => $id_token,
                'username' => $username,
                'password' => $password,
            ]
        ]);
        return json_decode($res->getBody(), true);

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