<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ACSLoginService
{
    private $httpClient;
    private $config;
    private $cacheKey = 'acs_token_data';

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->config = Config::get('organization.login_acs', []);
    }

    /**
     * Đăng nhập ACS và lấy token
     */
    public function login()
    {
        $basicAuth = base64_encode($this->config['application_code'] . ':' . $this->config['username'] . ':' . $this->config['password']);
        
        try {
            $response = $this->httpClient->get($this->config['login_url'], [
                'headers' => [
                    'Authorization' => 'Basic ' . $basicAuth,
                    'Content-Type' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (!$result['Success']) {
                Log::error('ACS Login failed', ['response' => $result]);
                throw new \Exception('ACS Login failed');
            }

            $tokenData = $result['Data'];
            
            // Lưu token vào cache với thời gian hết hạn
            $expireTime = \Carbon\Carbon::parse($tokenData['ExpireTime']);
            $cacheMinutes = $expireTime->diffInMinutes(now());
            
            Cache::put($this->cacheKey, $tokenData, $cacheMinutes);
            
            Log::info('ACS Login successful', [
                'login_name' => $tokenData['User']['LoginName'],
                'expire_time' => $tokenData['ExpireTime']
            ]);

            return $tokenData;

        } catch (GuzzleException $e) {
            Log::error('ACS Login API Error: ' . $e->getMessage());
            throw new \Exception('Failed to call ACS login API: ' . $e->getMessage());
        }
    }

    /**
     * Lấy token hiện tại (tự động đăng nhập lại nếu hết hạn)
     */
    public function getToken()
    {
        $tokenData = Cache::get($this->cacheKey);
        
        // Nếu không có token hoặc token hết hạn thì đăng nhập lại
        if (!$tokenData || $this->isTokenExpired($tokenData)) {
            $tokenData = $this->login();
        }
        
        return $tokenData['TokenCode'];
    }

    /**
     * Lấy toàn bộ thông tin token
     */
    public function getTokenData()
    {
        $tokenData = Cache::get($this->cacheKey);
        
        if (!$tokenData || $this->isTokenExpired($tokenData)) {
            $tokenData = $this->login();
        }
        
        return $tokenData;
    }

    /**
     * Kiểm tra token có hết hạn không
     */
    public function isTokenExpired($tokenData = null)
    {
        if (!$tokenData) {
            $tokenData = Cache::get($this->cacheKey);
        }
        
        if (!$tokenData) {
            return true;
        }
        
        $expireTime = \Carbon\Carbon::parse($tokenData['ExpireTime']);
        $now = now();
        
        // Kiểm tra hết hạn trước 5 phút để tránh lỗi
        return $expireTime->subMinutes(5)->isBefore($now);
    }

    /**
     * Làm mới token
     */
    public function renewToken()
    {
        $tokenData = Cache::get($this->cacheKey);
        
        if (!$tokenData || !isset($tokenData['RenewCode'])) {
            return $this->login();
        }
        
        // TODO: Implement renew token API nếu có
        // Hiện tại fallback về login mới
        return $this->login();
    }

    /**
     * Xóa token khỏi cache
     */
    public function logout()
    {
        Cache::forget($this->cacheKey);
        Log::info('ACS Logout successful');
    }

    /**
     * Kiểm tra xem có đăng nhập không
     */
    public function isLoggedIn()
    {
        $tokenData = Cache::get($this->cacheKey);
        return $tokenData && !$this->isTokenExpired($tokenData);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function getCurrentUser()
    {
        $tokenData = $this->getTokenData();
        return $tokenData['User'] ?? null;
    }

    /**
     * Lấy danh sách vai trò của user
     */
    public function getUserRoles()
    {
        $tokenData = $this->getTokenData();
        return $tokenData['RoleDatas'] ?? [];
    }
}
