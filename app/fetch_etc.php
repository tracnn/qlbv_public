<?php

namespace App;

use GuzzleHttp;

class fetch_etc
{

    public static function get_covid()
    {
		$ROOT_URL = 'https://disease.sh/v3/covid-19/countries/vn';
		$RequestType = 'GET';
    	$strict = 'true';
    	$client = new GuzzleHttp\Client();
        $res = $client->request($RequestType, $ROOT_URL,[
            'form_params' => [
                'strict' => $strict,             
            ],
        ]);
        return json_decode($res->getBody(), true);
    }
}