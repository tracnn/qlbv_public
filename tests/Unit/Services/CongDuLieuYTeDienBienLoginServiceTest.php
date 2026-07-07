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
    public function login_caches_token_with_ttl_in_minutes_not_seconds(): void
    {
        // Token còn sống 1 giờ nữa
        $expiresTime = Carbon::now()->addHour()->toIso8601String();

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'abc123',
                'token_type'   => 'Bearer',
                'username'     => 'user',
                'expiresTime'  => $expiresTime,
            ])),
        ]);

        $service = $this->makeService($mock);

        // TTL truyền cho Cache::put phải là PHÚT (~55), không phải giây (~3300)
        $capturedTtl = null;
        Cache::shouldReceive('put')
            ->once()
            ->andReturnUsing(function ($key, $value, $ttl) use (&$capturedTtl) {
                $capturedTtl = $ttl;
                return null;
            });

        $service->login();

        $this->assertNotNull($capturedTtl);
        // 1 giờ - 5 phút đệm = ~55 phút. Cho phép sai lệch nhỏ do thời gian chạy.
        $this->assertGreaterThanOrEqual(53, $capturedTtl, 'TTL phải tính bằng phút (~55), không phải giây (~3300)');
        $this->assertLessThanOrEqual(56, $capturedTtl, 'TTL phải tính bằng phút (~55), không phải giây (~3300)');
    }

    /** @test */
    public function login_defaults_to_60_minutes_when_expiresTime_missing(): void
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

        $this->assertSame(60, $capturedTtl);
    }

    /** @test */
    public function isTokenExpired_true_when_no_token_in_cache(): void
    {
        $service = $this->makeService(new MockHandler([]));
        Cache::forget('cong_du_lieu_y_te_dien_bien_token');

        $this->assertTrue($service->isTokenExpired());
    }

    /** @test */
    public function isTokenExpired_true_when_expiresTime_in_the_past(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x', 'expiresTime' => Carbon::now()->subHour()->toIso8601String()];

        $this->assertTrue($service->isTokenExpired($tokens));
    }

    /** @test */
    public function isTokenExpired_false_when_expiresTime_well_in_future(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x', 'expiresTime' => Carbon::now()->addHour()->toIso8601String()];

        $this->assertFalse($service->isTokenExpired($tokens));
    }

    /** @test */
    public function isTokenExpired_true_when_expiresTime_missing(): void
    {
        $service = $this->makeService(new MockHandler([]));

        $tokens = ['access_token' => 'x'];

        $this->assertTrue($service->isTokenExpired($tokens));
    }

    /** @test */
    public function getTokens_relogins_when_cached_token_is_expired(): void
    {
        // Token trong cache đã hết hạn theo nội dung (dù cache TTL còn)
        Cache::put('cong_du_lieu_y_te_dien_bien_token', [
            'access_token' => 'old-token',
            'expiresTime'  => Carbon::now()->subMinute()->toIso8601String(),
        ], 120);

        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'access_token' => 'fresh-token',
                'token_type'   => 'Bearer',
                'username'     => 'user',
                'expiresTime'  => Carbon::now()->addHour()->toIso8601String(),
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
            'access_token' => 'cached-token',
            'expiresTime'  => Carbon::now()->addHour()->toIso8601String(),
        ], 120);

        // MockHandler rỗng: nếu login bị gọi nhầm sẽ ném lỗi "Mock queue is empty"
        $service = $this->makeService(new MockHandler([]));

        $tokens = $service->getTokens();

        $this->assertEquals('cached-token', $tokens['access_token']);
    }
}
