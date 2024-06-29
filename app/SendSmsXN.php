<?php

namespace App;

use GuzzleHttp;

class SendSmsXN
{

    public static function sendSmsXN($Phone, $Content)
    {
    	if(!env('SMS_SEND',false))
    	{
    		return;
    	}

        $headr[] = 'Content-Type: application/json';
        $headr[] = 'Authorization: Basic YnZka25uOnNkbmZWS0tx';

        $from = 'BV_DKNN';
        $to = $Phone;
        $text = $Content;
        $url = 'http://api-02.worldsms.vn/webapi/sendSMS';

        $param = array('from' => $from,
            'to' => $to,
            'text' => $text);
        $param = json_encode($param);
        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
        
        /* eSms GuzzleHttp */
		// $ApiKey = 'E283EF62309BA0DD140AD86C9D0037';
		// $SecretKey = '155635118D0194C6A18D9CDDE57161';
		// $SmsType = '8';
		// $Brandname = ''; //'QCAO_ONLINE';
		// $ROOT_URL = 'http://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_post';
		// $RequestType = 'POST';

  //   	$client = new GuzzleHttp\Client();
  //       $res = $client->request($RequestType, $ROOT_URL,[
  //           'form_params' => [
  //               'ApiKey' => $ApiKey,
  //               'SecretKey' => $SecretKey,
  //               'SmsType' => $SmsType,
  //               'Phone' => $Phone,
  //               'Content' => $Content,
  //               'Brandname' => $Brandname,               
  //           ],
  //       ]);
  //       return $res;
        /* End eSms */
    }
}