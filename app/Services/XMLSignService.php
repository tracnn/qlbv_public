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
        // USB Token mode takes priority over HSM
        $usbConfig = Config::get('organization.usb_token_sign', []);
        if (!empty($usbConfig['enabled'])) {
            return $this->signWithUsbToken($xmlContent, $usbConfig);
        }

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
                return ['isSigned' => false, 'data' => $xmlContent, 'error' => $errorMessage];
            }

            return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

        } catch (GuzzleException $e) {
            Log::error('XML Sign API Error: ' . $e->getMessage());
            return ['isSigned' => false, 'data' => $xmlContent, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kiểm tra xem service có được bật không
     */
    public function isEnabled(): bool
    {
        $hsmEnabled = !empty($this->config['enabled']);
        $usbEnabled = !empty(Config::get('organization.usb_token_sign.enabled'));
        return $hsmEnabled || $usbEnabled;
    }

    /**
     * Ký số XML bằng USB Token (local service)
     */
    private function signWithUsbToken(string $xmlContent, array $usbConfig): array
    {
        try {
            $response = $this->httpClient->post($usbConfig['endpoint'], [
                'headers' => [
                    'Content-Type'    => 'application/json',
                    'X-Service-Token' => $usbConfig['service_token'] ?? '',
                ],
                'json' => [
                    'ApiData' => [
                        'XmlBase64'              => base64_encode($xmlContent),
                        'TagStoreSignatureValue' => $usbConfig['tag_store_signature_value'] ?? 'CHUKYDONVI',
                        'ConfigData'             => new \stdClass(),
                    ],
                ],
                'timeout' => $usbConfig['timeout'] ?? 30,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!$result['Success']) {
                $error = implode(', ', $result['Param']['Messages'] ?? ['Unknown error']);
                Log::error('USB Token signing failed: ' . $error);
                return ['isSigned' => false, 'data' => $xmlContent, 'error' => $error];
            }

            return ['isSigned' => true, 'data' => base64_decode($result['Data'])];

        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            Log::error('USB Token Sign Service Error: ' . $e->getMessage());
            return ['isSigned' => false, 'data' => $xmlContent, 'error' => $e->getMessage()];
        }
    }
}
