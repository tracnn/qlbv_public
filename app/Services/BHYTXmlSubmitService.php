<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BHYTXmlSubmitService
{
    private Client $httpClient;
    private array $config;
    private BHYTLoginService $loginService;

    public function __construct(?BHYTLoginService $loginService = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.BHYT', []);
        $this->loginService = $loginService ?? new BHYTLoginService();
    }

    /**
     * Gửi hồ sơ XML theo QD4750
     *
     * @param string $xmlContent Nội dung XML cần gửi
     * @param string|null $maTinh Mã tỉnh (nếu null sẽ lấy từ config)
     * @param string|null $maCSKCB Mã cơ sở khám chữa bệnh (nếu null sẽ lấy từ config)
     * @param string $loaiHoSo Loại hồ sơ (mặc định: 130)
     * @return array Kết quả trả về từ API
     */
    public function submitXml(
        string $xmlContent,
        ?string $maTinh = null,
        ?string $maCSKCB = null,
        string $loaiHoSo = '130'
    ): array {
        $submitUrl = $this->config['submit_xml_url'] ?? '';
        if (empty($submitUrl)) {
            Log::error('BHYT XML Submit: submit_xml_url is not configured');
            throw new \Exception('BHYT XML submit URL is not configured');
        }

        // Lấy mã tỉnh và mã CSKCB từ config nếu không được cung cấp
        if ($maTinh === null) {
            $maTinh = $this->getMaTinhFromConfig();
        }
        if ($maCSKCB === null) {
            $maCSKCB = $this->getMaCSKCBFromConfig();
        }

        // Lấy thông tin đăng nhập
        $accessToken = $this->loginService->getAccessToken();
        $idToken = $this->loginService->getIdToken();
        $passwordHash = $this->config['password'] ?? ''; //Bản thân password đã được mã hóa MD5
        $username = $this->config['username'] ?? '';

        // Mã hóa XML thành base64
        $fileHSBase64 = base64_encode($xmlContent);

        // Chuẩn bị body
        $body = [
            'username' => $username,
            'loaiHoSo' => $loaiHoSo,
            'maTinh' => $maTinh,
            'maCSKCB' => $maCSKCB,
            'fileHSBase64' => $fileHSBase64,
        ];

        // Chuẩn bị headers
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'accessToken' => $accessToken,
            'tokenId' => $idToken,
            'passwordHash' => $passwordHash,
        ];

        try {
            $response = $this->httpClient->post($submitUrl, [
                'headers' => $headers,
                'form_params' => $body,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $result;
        } catch (GuzzleException $e) {
            $errorMessage = 'BHYT XML Submit API Error: ' . $e->getMessage();
            Log::error($errorMessage, [
                'url' => $submitUrl,
                'maTinh' => $maTinh,
                'maCSKCB' => $maCSKCB,
            ]);

            // Nếu có response, lấy thông tin lỗi
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                $errorResult = json_decode($responseBody, true);
                if ($errorResult) {
                    return $errorResult;
                }
            }

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Lấy mã tỉnh từ config
     * Mã tỉnh là 2 ký tự đầu của mã CSKCB
     */
    private function getMaTinhFromConfig(): string
    {
        $maCSKCB = $this->getMaCSKCBFromConfig();
        if (strlen($maCSKCB) >= 2) {
            return substr($maCSKCB, 0, 2);
        }

        // Fallback: lấy từ config nếu có
        $maTinh = $this->config['ma_tinh'] ?? '';
        if (!empty($maTinh)) {
            return $maTinh;
        }

        throw new \Exception('Mã tỉnh không được cấu hình');
    }

    /**
     * Lấy mã CSKCB từ config
     */
    private function getMaCSKCBFromConfig(): string
    {
        // Lấy từ correct_facility_code (mảng, lấy phần tử đầu tiên)
        $facilityCodes = Config::get('organization.correct_facility_code', []);
        if (!empty($facilityCodes) && is_array($facilityCodes)) {
            return (string) $facilityCodes[0];
        }

        // Fallback: lấy từ config BHYT nếu có
        $maCSKCB = $this->config['ma_cskcb'] ?? '';
        if (!empty($maCSKCB)) {
            return $maCSKCB;
        }

        throw new \Exception('Mã CSKCB không được cấu hình');
    }
}
