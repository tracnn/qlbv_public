<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CongDuLieuYTeDienBienXmlSubmitService
{
    private Client $httpClient;
    private array $config;
    private CongDuLieuYTeDienBienLoginService $loginService;

    public function __construct(?CongDuLieuYTeDienBienLoginService $loginService = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.cong_du_lieu_y_te_dien_bien', []);
        $this->loginService = $loginService ?? new CongDuLieuYTeDienBienLoginService();
    }

    /**
     * Gửi hồ sơ XML 130 lên Cổng dữ liệu Y tế tỉnh Điện Biên (CheckIn)
     *
     * @param string $xmlContent Nội dung XML cần gửi
     * @return array Kết quả trả về từ API (bao gồm maGiaoDich, trangThai, maLoi)
     */
    public function submitXml(string $xmlContent): array
    {
        $submitUrl = $this->config['submit_xml_url'] ?? 'https://api.congdulieuytedienbien.vn/api/Cong130/CheckIn';
        
        if (empty($submitUrl)) {
            Log::error('Cong Du Lieu Y Te Dien Bien XML Submit: submit_url is not configured', []);
            throw new \Exception('Cong Du Lieu Y Te Dien Bien XML submit URL is not configured');
        }

        // Lấy access token
        $accessToken = $this->loginService->getAccessToken();

        // Tạo file tạm từ nội dung XML
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        file_put_contents($tempPath, $xmlContent);

        try {
            // Sử dụng multipart/form-data với file
            $response = $this->httpClient->post($submitUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'multipart' => [
                    [
                        'name' => 'files',
                        'contents' => fopen($tempPath, 'r'),
                        'filename' => 'hoso.xml',
                    ],
                ],
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

            // Xử lý các trường hợp response
            $isSuccess = false;
            $maGiaoDich = $result['maGiaoDich'] ?? null;
            $trangThai = $result['trangThai'] ?? null;
            $maLoi = $result['maLoi'] ?? null;

            // Status 200: Hồ sơ đúng (trangThai = 1)
            if ($statusCode === 200 && $trangThai === 1) {
                $isSuccess = true;
            }
            // Status 201: Hồ sơ lỗi (trangThai = 2)
            elseif ($statusCode === 201 && $trangThai === 2) {
                $isSuccess = false;
            }
            // Status 400, 401, 500: Lỗi hệ thống
            elseif (in_array($statusCode, [400, 401, 500])) {
                $isSuccess = false;
            }

            Log::info('Cong Du Lieu Y Te Dien Bien XML Submit response', [
                'status_code' => $statusCode,
                'ma_giao_dich' => $maGiaoDich,
                'trang_thai' => $trangThai,
                'ma_loi' => $maLoi,
                'is_success' => $isSuccess,
            ]);

            // Thêm các trường tiện ích vào result
            $result['success'] = $isSuccess;
            $result['statusCode'] = $statusCode;

            return $result;
        } catch (GuzzleException $e) {
            $errorMessage = 'Cong Du Lieu Y Te Dien Bien XML Submit API Error: ' . $e->getMessage();
            Log::error($errorMessage, [
                'url' => $submitUrl,
            ]);

            // Nếu có response, lấy thông tin lỗi
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $responseBody = $response->getBody()->getContents();
                $errorResult = json_decode($responseBody, true);
                if ($errorResult) {
                    $errorResult['success'] = false;
                    $errorResult['statusCode'] = $response->getStatusCode();
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
        } finally {
            // Đóng và xóa file tạm
            if (is_resource($tempFile)) {
                fclose($tempFile);
            }
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Lấy URL gửi XML
     */
    private function getSubmitUrl(): string
    {
        return $this->config['submit_xml_url'] ?? 'https://api.congdulieuytedienbien.vn/api/Cong130/CheckIn';
    }
}

