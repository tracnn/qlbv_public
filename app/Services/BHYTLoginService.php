<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BHYTLoginService
{
    private Client $httpClient;
    private array $config;
    private string $cacheKey = 'bhyt_tokens';

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.BHYT', []);
    }

    /**
     * Đăng nhập BHYT và lấy token
     */
    public function login(): array
    {
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $loginUrl = $this->config['login_url'] ?? '';

        if (empty($username) || empty($password) || empty($loginUrl)) {
            Log::error('BHYT Login: Missing configuration');
            throw new \Exception('BHYT login configuration is missing');
        }

        try {
            $response = $this->httpClient->post($loginUrl, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'username' => $username,
                    'password' => $password,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['APIKey'])) {
                Log::error('BHYT Login failed: APIKey not found in response', ['response' => $result]);
                throw new \Exception('BHYT login failed: APIKey not found');
            }

            $apiKey = $result['APIKey'];
            
            // Tính thời gian hết hạn
            $expiresAt = null;
            if (isset($apiKey['expires_in'])) {
                $expiresAt = $this->parseExpiresIn($apiKey['expires_in']);
            }

            $tokens = [
                'access_token' => $apiKey['access_token'],
                'id_token' => $apiKey['id_token'],
                'token_type' => $apiKey['token_type'] ?? null,
                'username' => $apiKey['username'] ?? $username,
                'expires_in' => $expiresAt ?? null,
            ];

            // Lưu token vào cache
            if ($expiresAt) {
                $cacheSeconds = max(60, $expiresAt - time() - 60); // Trừ 60 giây để tránh hết hạn sớm
                Cache::put($this->cacheKey, $tokens, $cacheSeconds);
            } else {
                // Nếu không có thời gian hết hạn, cache 1 giờ
                Cache::put($this->cacheKey, $tokens, 3600);
            }

            Log::info('BHYT Login successful', [
                'username' => $username,
                'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'unknown',
            ]);

            return $tokens;
        } catch (GuzzleException $e) {
            Log::error('BHYT Login API Error: ' . $e->getMessage());
            throw new \Exception('Failed to call BHYT login API: ' . $e->getMessage());
        }
    }

    /**
     * Parse expires_in từ nhiều định dạng khác nhau
     * 
     * @param mixed $expiresIn Có thể là datetime string, timestamp, hoặc số giây
     * @return int|null Timestamp hoặc null nếu không parse được
     */
    private function parseExpiresIn($expiresIn): ?int
    {
        if ($expiresIn === null) {
            return null;
        }

        // Nếu là số, kiểm tra xem là timestamp hay số giây
        if (is_numeric($expiresIn)) {
            $numericValue = (int) $expiresIn;
            // Nếu là timestamp (số lớn hơn 1000000000)
            if ($numericValue > 1000000000) {
                return $numericValue;
            } else {
                // Nếu là số giây, tính từ thời điểm hiện tại
                return time() + $numericValue;
            }
        }

        // Nếu là chuỗi datetime, thử parse
        if (is_string($expiresIn)) {
            // Thử parse với Carbon hoặc strtotime
            try {
                $timestamp = strtotime($expiresIn);
                if ($timestamp !== false) {
                    return $timestamp;
                }
            } catch (\Exception $e) {
                Log::warning('BHYT Login: Failed to parse expires_in', [
                    'expires_in' => $expiresIn,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
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
     * Lấy id token hiện tại (tự động đăng nhập lại nếu hết hạn)
     */
    public function getIdToken(): string
    {
        $tokens = $this->getTokens();
        return $tokens['id_token'];
    }

    /**
     * Lấy toàn bộ thông tin token (tự động đăng nhập lại nếu hết hạn)
     * 
     * Lưu ý: Với Laravel 5.5 không hỗ trợ Cache::lock(), 
     * có thể xảy ra race condition nếu nhiều process cùng đăng nhập,
     * nhưng ảnh hưởng không đáng kể
     */
    public function getTokens(): array
    {
        $tokens = Cache::get($this->cacheKey);

        // Nếu không có token hoặc token hết hạn thì đăng nhập lại
        if (!$tokens || $this->isTokenExpired($tokens)) {
            try {
                $tokens = $this->login();
            } catch (\Exception $e) {
                // Nếu đăng nhập thất bại, thử lấy lại từ cache (có thể process khác đã đăng nhập)
                $tokens = Cache::get($this->cacheKey);
                if (!$tokens || $this->isTokenExpired($tokens)) {
                    // Nếu vẫn không có token, throw exception
                    throw $e;
                }
            }
        }

        return $tokens;
    }

    /**
     * Kiểm tra token có hết hạn không
     */
    public function isTokenExpired(?array $tokens = null): bool
    {
        if (!$tokens) {
            $tokens = Cache::get($this->cacheKey);
        }

        if (!$tokens) {
            return true;
        }

        // Nếu không có thông tin expires_in, coi như hết hạn
        if (!isset($tokens['expires_in'])) {
            return true;
        }

        $expiresAt = $tokens['expires_in'];
        $now = time();

        // Kiểm tra hết hạn trước 60 giây để tránh lỗi
        return $expiresAt < ($now + 60);
    }

    /**
     * Xóa token khỏi cache
     */
    public function logout(): void
    {
        Cache::forget($this->cacheKey);
        Log::info('BHYT Logout successful');
    }

    /**
     * Kiểm tra xem có đăng nhập không
     */
    public function isLoggedIn(): bool
    {
        $tokens = Cache::get($this->cacheKey);
        return $tokens && !$this->isTokenExpired($tokens);
    }
}

