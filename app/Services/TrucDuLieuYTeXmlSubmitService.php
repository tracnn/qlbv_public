<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TrucDuLieuYTeXmlSubmitService
{
    private Client $httpClient;
    private array $config;
    private TrucDuLieuYTeLoginService $loginService;
    private string $environment;

    public function __construct(?TrucDuLieuYTeLoginService $loginService = null, ?string $environment = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.truc_du_lieu_y_te', []);
        $this->environment = $environment ?? ($this->config['environment'] ?? 'sandbox');
        $this->loginService = $loginService ?? new TrucDuLieuYTeLoginService($this->environment);
    }

    /**
     * Gửi hồ sơ XML theo chuẩn 3176 lên Trục dữ liệu Y Tế
     *
     * @param string $xmlContent Nội dung XML cần gửi
     * @param string|null $loaiHoSo Loại hồ sơ (mặc định: 130)
     * @param string|null $maTinh Mã tỉnh (mặc định: HN)
     * @param string|null $maCSKCB Mã cơ sở KCB (lấy từ config nếu không truyền)
     * @return array Kết quả trả về từ API (bao gồm maGiaoDich nếu thành công)
     */
    public function submitXml(
        string $xmlContent,
        ?string $loaiHoSo = null,
        ?string $maTinh = null,
        ?string $maCSKCB = null
    ): array {
        $submitUrl = $this->getSubmitUrl();
        
        if (empty($submitUrl)) {
            Log::error('Truc Du Lieu Y Te XML Submit: submit_url is not configured', [
                'environment' => $this->environment,
            ]);
            throw new \Exception('Truc Du Lieu Y Te XML submit URL is not configured');
        }

        // Lấy các giá trị từ config nếu không truyền
        $loaiHoSo = $loaiHoSo ?? $this->config['loai_ho_so'] ?? '130';
        $maTinh = $maTinh ?? $this->config['ma_tinh'] ?? 'HN';
        $maCSKCB = $maCSKCB ?? $this->config['code'] ?? '';

        if (empty($maCSKCB)) {
            Log::error('Truc Du Lieu Y Te XML Submit: maCSKCB is not configured', [
                'environment' => $this->environment,
            ]);
            throw new \Exception('Truc Du Lieu Y Te XML submit: maCSKCB is not configured');
        }

        // Lấy access token
        $accessToken = $this->loginService->getAccessToken();

        // Encode nội dung XML sang base64
        $xmlBase64 = base64_encode($xmlContent);

        // Chuẩn bị headers
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        // Chuẩn bị body
        $body = [
            'loaiHoSo' => $loaiHoSo,
            'maTinh' => $maTinh,
            'maCSKCB' => $maCSKCB,
            'files' => [$xmlBase64],
        ];

        try {
            $response = $this->httpClient->post($submitUrl, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            // Nếu response không phải JSON, trả về raw response
            if ($result === null) {
                $result = [
                    'statusCode' => $statusCode,
                    'body' => $responseBody,
                ];
            }

            // Lấy maGiaoDich từ fileResults nếu có
            $maGiaoDich = null;
            if (isset($result['fileResults']) && !empty($result['fileResults'])) {
                $maGiaoDich = $result['fileResults'][0]['maGiaoDich'] ?? null;
            }

            // Kiểm tra kết quả thành công
            $isSuccess = ($result['maKetQua'] ?? '') === '200' || 
                         ($result['totalSuccess'] ?? 0) > 0;

            Log::info('Truc Du Lieu Y Te XML Submit response', [
                'environment' => $this->environment,
                'status_code' => $statusCode,
                'ma_ket_qua' => $result['maKetQua'] ?? null,
                'batch_id' => $result['batchId'] ?? null,
                'ma_giao_dich' => $maGiaoDich,
                'total_success' => $result['totalSuccess'] ?? 0,
                'total_failed' => $result['totalFailed'] ?? 0,
                'is_success' => $isSuccess,
            ]);

            // Thêm các trường tiện ích vào result
            $result['maGiaoDich'] = $maGiaoDich;
            $result['success'] = $isSuccess;

            return $result;
        } catch (GuzzleException $e) {
            $errorMessage = 'Truc Du Lieu Y Te XML Submit API Error: ' . $e->getMessage();
            Log::error($errorMessage, [
                'url' => $submitUrl,
                'environment' => $this->environment,
                'maCSKCB' => $maCSKCB,
            ]);

            // Nếu có response, lấy thông tin lỗi
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                $errorResult = json_decode($responseBody, true);
                if ($errorResult) {
                    $errorResult['success'] = false;
                    return $errorResult;
                }
                
                // Nếu không parse được JSON, trả về raw response
                return [
                    'statusCode' => $response->getStatusCode(),
                    'error' => $errorMessage,
                    'body' => $responseBody,
                    'success' => false,
                ];
            }

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Tra cứu kết quả tiếp nhận hồ sơ
     *
     * @param string $maGiaoDich Mã giao dịch từ kết quả submitXml
     * @return array Kết quả tra cứu trạng thái hồ sơ
     */
    public function checkStatus(string $maGiaoDich): array
    {
        $checkUrl = $this->getCheckStatusUrl($maGiaoDich);
        
        if (empty($checkUrl)) {
            Log::error('Truc Du Lieu Y Te Check Status: check_url is not configured', [
                'environment' => $this->environment,
                'ma_giao_dich' => $maGiaoDich,
            ]);
            throw new \Exception('Truc Du Lieu Y Te check status URL is not configured');
        }

        // Lấy access token
        $accessToken = $this->loginService->getAccessToken();

        // Chuẩn bị headers
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            $response = $this->httpClient->get($checkUrl, [
                'headers' => $headers,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            // Nếu response không phải JSON, trả về raw response
            if ($result === null) {
                $result = [
                    'statusCode' => $statusCode,
                    'body' => $responseBody,
                ];
            }

            Log::info('Truc Du Lieu Y Te Check Status successful', [
                'environment' => $this->environment,
                'ma_giao_dich' => $maGiaoDich,
                'status_code' => $statusCode,
            ]);

            return $result;
        } catch (GuzzleException $e) {
            $errorMessage = 'Truc Du Lieu Y Te Check Status API Error: ' . $e->getMessage();
            Log::error($errorMessage, [
                'url' => $checkUrl,
                'environment' => $this->environment,
                'ma_giao_dich' => $maGiaoDich,
            ]);

            // Nếu có response, lấy thông tin lỗi
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                $errorResult = json_decode($responseBody, true);
                if ($errorResult) {
                    return $errorResult;
                }
                
                // Nếu không parse được JSON, trả về raw response
                return [
                    'statusCode' => $response->getStatusCode(),
                    'error' => $errorMessage,
                    'body' => $responseBody,
                ];
            }

            throw new \Exception($errorMessage);
        }
    }

    /**
     * Lấy URL gửi XML dựa trên environment
     */
    private function getSubmitUrl(): string
    {
        if ($this->environment === 'production' || $this->environment === 'poc') {
            return $this->config['submit_xml_url_production'] ?? 'https://axis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml';
        }
        
        return $this->config['submit_xml_url_sandbox'] ?? 'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/xml/qd3176/guiHoSoXml';
    }

    /**
     * Lấy URL tra cứu trạng thái dựa trên environment
     */
    private function getCheckStatusUrl(string $maGiaoDich): string
    {
        $baseUrl = '';
        if ($this->environment === 'production' || $this->environment === 'poc') {
            $baseUrl = $this->config['check_status_url_production'] ?? 'https://axis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai';
        } else {
            $baseUrl = $this->config['check_status_url_sandbox'] ?? 'https://sbaxis-soyt.hanoi.gov.vn/api/kcb/tra-cuu-trang-thai';
        }
        
        return rtrim($baseUrl, '/') . '/' . $maGiaoDich;
    }
}

