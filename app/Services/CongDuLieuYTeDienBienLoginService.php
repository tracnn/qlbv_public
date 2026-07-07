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
    /**
     * Tuổi thọ tối đa (giây) cho phép dùng 1 access_token, bất kể server trả expiresTime dài hơn.
     * Server hiện trả token sống ~2 giờ, nhưng ta chỉ dùng tối đa 15 phút rồi lấy lại cho chắc.
     * Mốc hết hạn hiệu lực = min(expiresTime, thời điểm login + 15 phút). 15 phút = 900 giây.
     */
    private const MAX_TOKEN_LIFETIME_SECONDS = 900;

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
        $loginUrl = $this->config['login_url'] ?? 'https://api.congdulieuytedienbien.vn/api/token';

        if (empty($username) || empty($password) || empty($loginUrl)) {
            Log::error('Cong Du Lieu Y Te Dien Bien Login: Missing configuration', []);
            throw new \Exception('Cong Du Lieu Y Te Dien Bien login configuration is missing');
        }

        // Mật khẩu phải được hash MD5
        $passwordMd5 = $password; //lừa nhau

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

            // Mốc hết hạn hiệu lực = min(expiresTime của server, login + 15 phút).
            $effectiveExpiresAt = $this->calculateEffectiveExpiresAt($result['expiresTime'] ?? null);

            $tokens = [
                'access_token' => $result['access_token'],
                'token_type' => $result['token_type'] ?? 'Bearer',
                'username' => $result['username'] ?? $username,
                'expiresTime' => $result['expiresTime'] ?? null,   // giữ nguyên gốc để tham khảo
                'effective_expires_at' => $effectiveExpiresAt,     // mốc dùng để kiểm hạn (đã cap 15 phút)
            ];

            // Tính toán thời gian cache dựa trên mốc hết hạn hiệu lực.
            // LƯU Ý: Laravel 5.5 nhận tham số thứ 3 của Cache::put() là PHÚT, không phải giây.
            $cacheMinutes = $this->calculateCacheMinutes($effectiveExpiresAt);
            Cache::put($this->getCacheKey(), $tokens, $cacheMinutes);

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

        // Nếu không có token hoặc token đã hết hạn (theo nội dung) thì đăng nhập lại
        if (!$tokens || $this->isTokenExpired($tokens)) {
            try {
                $tokens = $this->login();
            } catch (\Exception $e) {
                // Nếu đăng nhập thất bại, thử lấy lại từ cache (có thể process khác đã đăng nhập)
                $tokens = Cache::get($this->getCacheKey());
                if (!$tokens || $this->isTokenExpired($tokens)) {
                    // Nếu vẫn không có token hợp lệ, throw exception
                    throw $e;
                }
            }
        }

        return $tokens;
    }

    /**
     * Kiểm tra token có hết hạn không (dựa trên mốc hết hạn hiệu lực đã cap 15 phút).
     * Trả về true nếu không có token, thiếu mốc hết hạn, hoặc đã quá mốc đó.
     *
     * @param array|null $tokens Nếu null sẽ lấy từ cache
     * @return bool
     */
    public function isTokenExpired(?array $tokens = null): bool
    {
        if (!$tokens) {
            $tokens = Cache::get($this->getCacheKey());
        }

        if (!$tokens) {
            return true;
        }

        // Nếu không có mốc hết hạn hiệu lực, coi như hết hạn
        if (empty($tokens['effective_expires_at'])) {
            return true;
        }

        // Đã tới/quá mốc hết hạn hiệu lực (đã cap 15 phút khi login) → cần lấy lại
        return (int) $tokens['effective_expires_at'] <= time();
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
        return $tokens && !$this->isTokenExpired($tokens);
    }

    /**
     * Lấy cache key
     */
    private function getCacheKey(): string
    {
        return $this->cacheKey;
    }

    /**
     * Tính mốc hết hạn hiệu lực (timestamp) = min(expiresTime của server, login + 15 phút).
     * Nếu expiresTime thiếu hoặc parse lỗi thì dùng luôn mốc login + 15 phút.
     */
    private function calculateEffectiveExpiresAt(?string $expiresTime): int
    {
        // Trần: chỉ cho phép dùng token tối đa 15 phút kể từ khi login
        $cap = time() + self::MAX_TOKEN_LIFETIME_SECONDS;

        if (empty($expiresTime)) {
            return $cap;
        }

        try {
            // Parse expiresTime (format: "2026-01-17T10:34:09.9434625+07:00")
            $serverExpiresAt = (new \DateTime($expiresTime))->getTimestamp();
        } catch (\Exception $e) {
            Log::warning('Cong Du Lieu Y Te Dien Bien: Failed to parse expiresTime', [
                'expiresTime' => $expiresTime,
                'error' => $e->getMessage(),
            ]);
            return $cap;
        }

        // Nếu expiresTime của server còn dài hơn 15 phút thì lấy mốc 15 phút; ngược lại theo server
        return min($serverExpiresAt, $cap);
    }

    /**
     * Tính thời gian cache (PHÚT) từ mốc hết hạn hiệu lực (timestamp).
     * Laravel 5.5 dùng đơn vị phút cho tham số TTL của Cache::put().
     */
    private function calculateCacheMinutes(int $effectiveExpiresAt): int
    {
        $diffSeconds = $effectiveExpiresAt - time();

        // Nếu đã tới/quá mốc hết hạn thì không cache token đã chết
        if ($diffSeconds <= 0) {
            return 0;
        }

        // Đổi ra phút, tối thiểu 1 phút để cache còn kịp giữ token
        return (int) max(1, ceil($diffSeconds / 60));
    }
}

