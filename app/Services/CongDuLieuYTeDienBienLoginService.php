<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class CongDuLieuYTeDienBienLoginService
{
    private Client $httpClient;
    private array $config;
    private string $cacheKey = 'cong_du_lieu_y_te_dien_bien_token';

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.cong_du_lieu_y_te_dien_bien', []);
    }

    /**
     * Đăng nhập và lấy access token
     */
    public function login(): array
    {
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $loginUrl = $this->config['login_url'] ?? 'http://api.congdulieuytedienbien.vn/api/token';

        if (empty($username) || empty($password) || empty($loginUrl)) {
            Log::error('Cong Du Lieu Y Te Dien Bien Login: Missing configuration', []);
            throw new \Exception('Cong Du Lieu Y Te Dien Bien login configuration is missing');
        }

        // Mật khẩu phải được hash MD5
        $passwordMd5 = md5($password);

        try {
            // Sử dụng multipart/form-data
            $response = $this->httpClient->post($loginUrl, [
                'multipart' => [
                    [
                        'name' => 'username',
                        'contents' => $username,
                    ],
                    [
                        'name' => 'password',
                        'contents' => $passwordMd5,
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);

            // Kiểm tra lỗi (status 400)
            if ($statusCode === 400) {
                $errorMessage = $result['message'] ?? 'Unknown error';
                Log::error('Cong Du Lieu Y Te Dien Bien Login failed', [
                    'status_code' => $statusCode,
                    'message' => $errorMessage,
                    'response' => $result,
                ]);
                throw new \Exception('Cong Du Lieu Y Te Dien Bien login failed: ' . $errorMessage);
            }

            // Kiểm tra thành công (status 200)
            if ($statusCode !== 200) {
                Log::error('Cong Du Lieu Y Te Dien Bien Login failed: Unexpected status code', [
                    'status_code' => $statusCode,
                    'response' => $result,
                ]);
                throw new \Exception('Cong Du Lieu Y Te Dien Bien login failed: Unexpected status code ' . $statusCode);
            }

            if (!isset($result['access_token'])) {
                Log::error('Cong Du Lieu Y Te Dien Bien Login failed: access_token not found in response', [
                    'response' => $result,
                ]);
                throw new \Exception('Cong Du Lieu Y Te Dien Bien login failed: access_token not found');
            }

            $tokens = [
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'] ?? 'Bearer',
                'username' => $result['username'] ?? $username,
            ];

            // Lưu token vào cache (mặc định 1 giờ, vì API không trả về expires_in)
            Cache::put($this->getCacheKey(), $tokens, 3600);

            Log::info('Cong Du Lieu Y Te Dien Bien Login successful', [
                'username' => $tokens['username'],
            ]);

            return $tokens;
        } catch (GuzzleException $e) {
            Log::error('Cong Du Lieu Y Te Dien Bien Login API Error: ' . $e->getMessage(), [
                'url' => $loginUrl,
            ]);
            throw new \Exception('Failed to call Cong Du Lieu Y Te Dien Bien login API: ' . $e->getMessage());
        }
    }

    /**
     * Lấy access token hiện tại (tự động đăng nhập lại nếu hết hạn)
     */
    public function getAccessToken(): string
    {
        $tokens = $this->getTokens();
        return $tokens['access_token'];
    }

    /**
     * Lấy toàn bộ thông tin token (tự động đăng nhập lại nếu hết hạn)
     */
    public function getTokens(): array
    {
        $tokens = Cache::get($this->getCacheKey());

        // Nếu không có token thì đăng nhập lại
        if (!$tokens) {
            try {
                $tokens = $this->login();
            } catch (\Exception $e) {
                // Nếu đăng nhập thất bại, thử lấy lại từ cache (có thể process khác đã đăng nhập)
                $tokens = Cache::get($this->getCacheKey());
                if (!$tokens) {
                    // Nếu vẫn không có token, throw exception
                    throw $e;
                }
            }
        }

        return $tokens;
    }

    /**
     * Xóa token khỏi cache
     */
    public function logout(): void
    {
        Cache::forget($this->getCacheKey());
        Log::info('Cong Du Lieu Y Te Dien Bien Logout successful');
    }

    /**
     * Kiểm tra xem có đăng nhập không
     */
    public function isLoggedIn(): bool
    {
        $tokens = Cache::get($this->getCacheKey());
        return $tokens !== null;
    }

    /**
     * Lấy cache key
     */
    private function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}

