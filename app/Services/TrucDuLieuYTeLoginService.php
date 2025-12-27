<?php

declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class TrucDuLieuYTeLoginService
{
    private Client $httpClient;
    private array $config;
    private string $cacheKey = 'truc_du_lieu_y_te_token';
    private string $environment;

    public function __construct(?string $environment = null)
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.truc_du_lieu_y_te', []);
        $this->environment = $environment ?? ($this->config['environment'] ?? 'sandbox');
    }

    /**
     * Đăng nhập và lấy access token
     */
    public function login(): array
    {
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';
        $code = $this->config['code'] ?? '';
        $loginUrl = $this->getLoginUrl();

        if (empty($username) || empty($password) || empty($code) || empty($loginUrl)) {
            Log::error('Truc Du Lieu Y Te Login: Missing configuration', [
                'environment' => $this->environment,
            ]);
            throw new \Exception('Truc Du Lieu Y Te login configuration is missing');
        }

        try {
            $response = $this->httpClient->post($loginUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                    'code' => $code,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Kiểm tra kết quả
            if (!isset($result['maKetQua']) || $result['maKetQua'] !== true) {
                Log::error('Truc Du Lieu Y Te Login failed: maKetQua is not true', [
                    'response' => $result,
                    'environment' => $this->environment,
                ]);
                throw new \Exception('Truc Du Lieu Y Te login failed: ' . ($result['message'] ?? 'Unknown error'));
            }

            if (!isset($result['apiKey']['accessToken'])) {
                Log::error('Truc Du Lieu Y Te Login failed: accessToken not found in response', [
                    'response' => $result,
                    'environment' => $this->environment,
                ]);
                throw new \Exception('Truc Du Lieu Y Te login failed: accessToken not found');
            }

            $apiKey = $result['apiKey'];
            $accessToken = $apiKey['accessToken'];
            $idToken = $apiKey['idToken'] ?? null;
            $expiresIn = $apiKey['expiresIn'] ?? null;
            
            // Tính thời gian hết hạn
            $expiresAt = null;
            if ($expiresIn !== null) {
                $expiresAt = $this->parseExpiresIn($expiresIn);
            }

            $tokens = [
                'access_token' => $accessToken,
                'id_token' => $idToken,
                'token_type' => $apiKey['tokenType'] ?? 'Bearer',
                'username' => $apiKey['username'] ?? $fullUsername,
                'expires_in' => $expiresAt,
                'environment' => $this->environment,
            ];

            // Lưu token vào cache
            if ($expiresAt) {
                $cacheSeconds = max(60, $expiresAt - time() - 60); // Trừ 60 giây để tránh hết hạn sớm
                Cache::put($this->getCacheKey(), $tokens, $cacheSeconds);
            } else {
                // Nếu không có thời gian hết hạn, cache 1 giờ
                Cache::put($this->getCacheKey(), $tokens, 3600);
            }

            Log::info('Truc Du Lieu Y Te Login successful', [
                'username' => $fullUsername,
                'environment' => $this->environment,
                'expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : 'unknown',
            ]);

            return $tokens;
        } catch (GuzzleException $e) {
            Log::error('Truc Du Lieu Y Te Login API Error: ' . $e->getMessage(), [
                'environment' => $this->environment,
                'url' => $loginUrl,
            ]);
            throw new \Exception('Failed to call Truc Du Lieu Y Te login API: ' . $e->getMessage());
        }
    }

    /**
     * Parse expires_in từ nhiều định dạng khác nhau
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
            try {
                $timestamp = strtotime($expiresIn);
                if ($timestamp !== false) {
                    return $timestamp;
                }
            } catch (\Exception $e) {
                Log::warning('Truc Du Lieu Y Te Login: Failed to parse expires_in', [
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
     * Lấy toàn bộ thông tin token (tự động đăng nhập lại nếu hết hạn)
     */
    public function getTokens(): array
    {
        $tokens = Cache::get($this->getCacheKey());

        // Nếu không có token hoặc token hết hạn thì đăng nhập lại
        if (!$tokens || $this->isTokenExpired($tokens)) {
            try {
                $tokens = $this->login();
            } catch (\Exception $e) {
                // Nếu đăng nhập thất bại, thử lấy lại từ cache (có thể process khác đã đăng nhập)
                $tokens = Cache::get($this->getCacheKey());
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
            $tokens = Cache::get($this->getCacheKey());
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
        Cache::forget($this->getCacheKey());
        Log::info('Truc Du Lieu Y Te Logout successful', [
            'environment' => $this->environment,
        ]);
    }

    /**
     * Kiểm tra xem có đăng nhập không
     */
    public function isLoggedIn(): bool
    {
        $tokens = Cache::get($this->getCacheKey());
        return $tokens && !$this->isTokenExpired($tokens);
    }

    /**
     * Lấy URL đăng nhập dựa trên environment
     */
    private function getLoginUrl(): string
    {
        if ($this->environment === 'production' || $this->environment === 'poc') {
            return $this->config['login_url_production'] ?? 'https://auth-soyt.hanoi.gov.vn/api/auth/token/take';
        }
        
        return $this->config['login_url_sandbox'] ?? 'https://sbauth-soyt.hanoi.gov.vn/api/auth/token/take';
    }

    /**
     * Lấy cache key dựa trên environment
     */
    private function getCacheKey(): string
    {
        return $this->cacheKey . '_' . $this->environment;
    }
}

