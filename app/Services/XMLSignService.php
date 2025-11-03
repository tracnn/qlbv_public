<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class XMLSignService
{
    private $httpClient;
    private $config;
    private $acsLoginService;

    public function __construct(ACSLoginService $acsLoginService = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.xml_sign', []);
        $this->acsLoginService = $acsLoginService ?: new ACSLoginService();
    }

    /**
     * Ký số XML
     */
    public function signXml($xmlContent)
    {

        if (!$this->config['enabled']) {
            Log::info('XML signing is disabled');
            return ['isSigned' => false, 'data' => $xmlContent];
        }

        $xmlBase64 = base64_encode($xmlContent);

        $data = [
            'ApiData' => [
                'XmlBase64' => $xmlBase64,
                'TagStoreSignatureValue' => $this->config['tag_store_signature_value'],
                'ConfigData' => [
                    'HsmType' => $this->config['hsm_type'],
                    'HsmUserCode' => $this->config['hsm_user_code'],
                    'Password' => $this->config['password'],
                    'SecretKey' => $this->config['secret_key'],
                    'IdentityNumber' => $this->config['identity_number'],
                    'HsmSerialNumber' => $this->config['hsm_serial_number']
                ]
            ]
        ];

        try {
            // Lấy token từ ACS
            $tokenCode = $this->acsLoginService->getToken();
            
            $response = $this->httpClient->post($this->config['endpoint'], [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'TokenCode' => $tokenCode,
                    'ApplicationCode' => $this->config['application_code']
                ],
                'json' => $data
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!$result['Success']) {
                $errorMessage = 'XML signing failed';
                if (!empty($result['Param']['Messages'])) {
                    $errorMessage .= ': ' . implode(', ', $result['Param']['Messages']);
                }
                Log::error('XML signing failed: ' . $errorMessage);
                return ['isSigned' => false, 'data' => $xmlBase64];
            }

            return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

        } catch (GuzzleException $e) {
            Log::error('XML Sign API Error: ' . $e->getMessage());
            return ['isSigned' => false, 'data' => $xmlBase64];
        }
    }

    /**
     * Kiểm tra xem service có được bật không
     */
    public function isEnabled()
    {
        return $this->config['enabled'] ?? false;
    }
}
