<?php

namespace App;

use GuzzleHttp;

class SendSms
{

    public static function sendSms($Med_Reg)
    {
    	if(!env('SMS_SEND',false))
    	{
    		return;
    	}
		
		$ApiKey = 'E283EF62309BA0DD140AD86C9D0037';
		$SecretKey = '155635118D0194C6A18D9CDDE57161';
		$SmsType = '2';
		$Brandname = 'QCAO_ONLINE';
		$ROOT_URL = 'http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post';
		$RequestType = 'POST';
    	$content = 'BVDKNN cam on quy khach da su dung dich vu KCB cua chung toi';

    	$client = new GuzzleHttp\Client();
        $res = $client->request($RequestType, $ROOT_URL,[
            'form_params' => [
                'ApiKey' => $ApiKey,
                'SecretKey' => $SecretKey,
                'SmsType' => $SmsType,
                'Phone' => $Med_Reg->phone,
                'Content' => $content,
                'Brandname' => $Brandname,               
            ],
        ]);
        return $res;
    }
}