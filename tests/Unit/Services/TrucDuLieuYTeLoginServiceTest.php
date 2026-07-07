<?php

namespace Tests\Unit\Services;

use App\Services\TrucDuLieuYTeLoginService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TrucDuLieuYTeLoginServiceTest extends TestCase
{
    private function makeService(MockHandler $mock): TrucDuLieuYTeLoginService
    {
        Config::set('organization.truc_du_lieu_y_te', [
            'username'          => 'user',
            'password'          => 'pass',
            'code'              => 'CODE',
            'environment'       => 'sandbox',
            'login_url_sandbox' => 'https://sbauth-soyt.hanoi.gov.vn/api/auth/token/take',
        ]);

        $service = new TrucDuLieuYTeLoginService('sandbox');

        $stack  = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(TrucDuLieuYTeLoginService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function login_succeeds_and_returns_username_without_undefined_variable(): void
    {
        // expiresIn = 3600 giây
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'maKetQua' => true,
                'apiKey'   => [
                    'accessToken' => 'access-abc',
                    'idToken'     => 'id-abc',
                    'tokenType'   => 'Bearer',
                    'username'    => 'user',
                    'expiresIn'   => 3600,
                ],
            ])),
        ]);

        $service = $this->makeService($mock);

        Cache::shouldReceive('put')->once()->andReturnNull();

        $tokens = $service->login();

        $this->assertEquals('access-abc', $tokens['access_token']);
        $this->assertEquals('user', $tokens['username']);
    }

    /** @test */
    public function login_caches_token_with_ttl_in_minutes_not_seconds(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'maKetQua' => true,
                'apiKey'   => [
                    'accessToken' => 'access-abc',
                    'idToken'     => 'id-abc',
                    'tokenType'   => 'Bearer',
                    'username'    => 'user',
                    'expiresIn'   => 3600,
                ],
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

        // ~59 phút (3600 - 60 đệm, đổi ra phút), không phải ~3540 giây
        $this->assertNotNull($capturedTtl);
        $this->assertGreaterThanOrEqual(55, $capturedTtl);
        $this->assertLessThanOrEqual(60, $capturedTtl);
    }
}
