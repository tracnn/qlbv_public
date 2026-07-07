<?php

namespace Tests\Unit\Services;

use App\Services\CongDuLieuYTeDienBienLoginService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CongDuLieuYTeDienBienLoginServiceTest extends TestCase
{
    private function makeService(MockHandler $mock): CongDuLieuYTeDienBienLoginService
    {
        Config::set('organization.cong_du_lieu_y_te_dien_bien', [
            'username'  => 'user',
            'password'  => 'pass',
            'login_url' => 'https://api.congdulieuytedienbien.vn/api/token',
        ]);

        $service = new CongDuLieuYTeDienBienLoginService();

        $stack  = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(CongDuLieuYTeDienBienLoginService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function login_caps_effective_expiry_and_ttl_at_15_minutes_when_server_expiry_longer(): void
    {
        // Server trả token sống 2 giờ
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'abc123',
                'token_type'   => 'Bearer',
                'username'     => 'user',
                'expiresTime'  => Carbon::now()->addHours(2)->toIso8601String(),
            ])),
        ]);

        $service = $this->makeService($mock);

        $capturedTtl = null;
        $capturedValue = null;
        Cache::shouldReceive('put')
            ->once()
            ->andReturnUsing(function ($key, $value, $ttl) use (&$capturedTtl, &$capturedValue) {
                $capturedTtl = $ttl;
                $capturedValue = $value;
                return null;
            });

        $service->login();

        // Mốc hết hạn hiệu lực bị cap về ~ now + 15 phút (900 giây), không theo 2 giờ của server
        $this->assertGreaterThanOrEqual(time() + 890, $capturedValue['effective_expires_at']);
        $this->assertLessThanOrEqual(time() + 901, $capturedValue['effective_expires_at']);

        // TTL cache theo đó ~ 15 phút (đơn vị phút), không phải giây/2 giờ
        $this->assertGreaterThanOrEqual(14, $capturedTtl);
        $this->assertLessThanOrEqual(15, $capturedTtl);
    }

    /** @test */
    public function login_uses_server_expiry_when_shorter_than_15_minutes(): void
    {
        // Server trả token chỉ còn 5 phút
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'abc123',
                'token_type'   => 'Bearer',
                'username'     => 'user',
                'expiresTime'  => Carbon::now()->addMinutes(5)->toIso8601String(),
            ])),
        ]);

        $service = $this->makeService($mock);

        $capturedValue = null;
        Cache::shouldReceive('put')
            ->once()
            ->andReturnUsing(function ($key, $value, $ttl) use (&$capturedValue) {
                $capturedValue = $value;
                return null;
            });

        $service->login();

        // Ngắn hơn 15 phút → lấy theo server (~ now + 5 phút = 300 giây)
        $this->assertGreaterThanOrEqual(time() + 290, $capturedValue['effective_expires_at']);
        $this->assertLessThanOrEqual(time() + 301, $capturedValue['effective_expires_at']);
    }

    /** @test */
    public function login_caps_at_15_minutes_when_expiresTime_missing(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'abc123',
                'token_type'   => 'Bearer',
                'username'     => 'user',
            ])),
        ]);

        $service = $this->makeService($mock);

        $capturedTtl = null;
        Cache::shouldReceive('put')
            ->once()
            ->andReturnUsing(function ($key, $value, $ttl) use (&$capturedTtl) {
                $capturedTtl = $ttl;
                return null;
            });

        $service->login();

        // Không có expiresTime → dùng trần 15 phút
        $this->assertGreaterThanOrEqual(14, $capturedTtl);
        $this->assertLessThanOrEqual(15, $capturedTtl);
    }

    /** @test */
    public function isTokenExpired_true_when_no_token_in_cache(): void
    {
        $service = $this->makeService(new MockHandler([]));
        Cache::forget('cong_du_lieu_y_te_dien_bien_token');

        $this->assertTrue($service->isTokenExpired());
    }

    /** @test */
    public function isTokenExpired_true_when_effective_expiry_in_the_past(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x', 'effective_expires_at' => time() - 10];

        $this->assertTrue($service->isTokenExpired($tokens));
    }

    /** @test */
    public function isTokenExpired_false_when_effective_expiry_in_the_future(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x', 'effective_expires_at' => time() + 600];

        $this->assertFalse($service->isTokenExpired($tokens));
    }

    /** @test */
    public function isTokenExpired_true_when_effective_expiry_missing(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x'];

        $this->assertTrue($service->isTokenExpired($tokens));
    }

    /** @test */
    public function getTokens_relogins_when_cached_token_is_expired(): void
    {
        // Token trong cache đã quá mốc hết hạn hiệu lực (dù cache TTL còn)
        Cache::put('cong_du_lieu_y_te_dien_bien_token', [
            'access_token'         => 'old-token',
            'effective_expires_at' => time() - 60,
        ], 120);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'fresh-token',
                'token_type'   => 'Bearer',
                'username'     => 'user',
                'expiresTime'  => Carbon::now()->addHours(2)->toIso8601String(),
            ])),
        ]);

        $service = $this->makeService($mock);

        $tokens = $service->getTokens();

        $this->assertEquals('fresh-token', $tokens['access_token']);
    }

    /** @test */
    public function getTokens_uses_cached_token_when_not_expired(): void
    {
        Cache::put('cong_du_lieu_y_te_dien_bien_token', [
            'access_token'         => 'cached-token',
            'effective_expires_at' => time() + 600,
        ], 120);

        // MockHandler rỗng: nếu login bị gọi nhầm sẽ ném lỗi "Mock queue is empty"
        $service = $this->makeService(new MockHandler([]));

        $tokens = $service->getTokens();

        $this->assertEquals('cached-token', $tokens['access_token']);
    }
}
