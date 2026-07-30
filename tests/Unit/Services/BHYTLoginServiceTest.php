<?php

namespace Tests\Unit\Services;

use App\Services\BHYTLoginService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BHYTLoginServiceTest extends TestCase
{
    private function makeService(MockHandler $mock): BHYTLoginService
    {
        // login_url la gia tri dung chung, khong theo co so.
        Config::set('organization.BHYT', [
            'login_url' => 'https://egw.baohiemxahoi.gov.vn/api/token/take',
        ]);

        // Tai khoan RIENG theo co so - can tu khi BHYTLoginService nhan ma co so.
        Config::set('organization.BHYT_CO_SO', [
            '01929' => ['username' => 'user', 'password' => 'pass'],
        ]);

        $service = new BHYTLoginService('01929');

        $stack  = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(BHYTLoginService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function login_caches_token_with_ttl_in_minutes_not_seconds(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'APIKey' => [
                    'access_token' => 'access-abc',
                    'id_token'     => 'id-abc',
                    'token_type'   => 'Bearer',
                    'username'     => 'user',
                    'expires_in'   => 3600,
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
