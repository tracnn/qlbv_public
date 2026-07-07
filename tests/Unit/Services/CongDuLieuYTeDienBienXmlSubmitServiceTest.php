<?php

namespace Tests\Unit\Services;

use App\Services\CongDuLieuYTeDienBienLoginService;
use App\Services\CongDuLieuYTeDienBienXmlSubmitService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Lớp login giả, kế thừa lớp thật để giữ đúng chữ ký phương thức
 * (tránh createMock() vốn gây deprecation ReflectionType trên PHPUnit 6).
 */
class FakeDienBienLoginService extends CongDuLieuYTeDienBienLoginService
{
    /** @var string[] */
    public $tokenSequence = ['token'];
    public $tokenCallCount = 0;
    public $logoutCallCount = 0;

    public function __construct()
    {
        // Bỏ qua constructor cha để không khởi tạo Guzzle/Config thật.
    }

    public function getAccessToken(): string
    {
        $token = $this->tokenSequence[$this->tokenCallCount] ?? 'fallback';
        $this->tokenCallCount++;
        return $token;
    }

    public function logout(): void
    {
        $this->logoutCallCount++;
    }
}

class CongDuLieuYTeDienBienXmlSubmitServiceTest extends TestCase
{
    private function makeService(MockHandler $mock, CongDuLieuYTeDienBienLoginService $login): CongDuLieuYTeDienBienXmlSubmitService
    {
        Config::set('organization.cong_du_lieu_y_te_dien_bien', [
            'submit_xml_url' => 'https://api.congdulieuytedienbien.vn/api/Cong130/CheckIn',
        ]);

        $service = new CongDuLieuYTeDienBienXmlSubmitService($login);

        $stack  = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(CongDuLieuYTeDienBienXmlSubmitService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function retries_once_with_fresh_token_when_first_attempt_returns_401(): void
    {
        // Lần 1 trả 401 (token hết hạn) → phải đăng nhập lại và gửi lại → lần 2 thành công
        $capturedTokens = [];
        $mock = new MockHandler([
            function ($request) use (&$capturedTokens) {
                $capturedTokens[] = $request->getHeaderLine('Authorization');
                return new Response(401, [], json_encode(['message' => 'Unauthorized']));
            },
            function ($request) use (&$capturedTokens) {
                $capturedTokens[] = $request->getHeaderLine('Authorization');
                return new Response(200, [], json_encode([
                    'maGiaoDich' => 'GD001',
                    'trangThai'  => 1,
                ]));
            },
        ]);

        $login = new FakeDienBienLoginService();
        $login->tokenSequence = ['stale-token', 'fresh-token'];

        $service = $this->makeService($mock, $login);

        $result = $service->submitXml('<Root/>');

        $this->assertTrue($result['success']);
        $this->assertEquals('GD001', $result['maGiaoDich']);
        $this->assertEquals(['Bearer stale-token', 'Bearer fresh-token'], $capturedTokens);
        $this->assertEquals(1, $login->logoutCallCount, 'Phải xoá cache token đúng 1 lần khi gặp 401');
        $this->assertEquals(2, $login->tokenCallCount, 'Phải lấy token 2 lần (ban đầu + sau khi re-auth)');
    }

    /** @test */
    public function does_not_retry_on_non_401_error(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['message' => 'Server error'])),
        ]);

        $login = new FakeDienBienLoginService();
        $login->tokenSequence = ['some-token'];

        $service = $this->makeService($mock, $login);

        $result = $service->submitXml('<Root/>');

        $this->assertFalse($result['success']);
        $this->assertEquals(500, $result['statusCode']);
        $this->assertEquals(0, $login->logoutCallCount, 'Không được re-auth với lỗi khác 401');
        $this->assertEquals(1, $login->tokenCallCount);
    }

    /** @test */
    public function returns_error_when_retry_also_fails_with_401(): void
    {
        // Cả 2 lần đều 401 → không lặp vô hạn, trả về lỗi
        $mock = new MockHandler([
            new Response(401, [], json_encode(['message' => 'Unauthorized'])),
            new Response(401, [], json_encode(['message' => 'Unauthorized'])),
        ]);

        $login = new FakeDienBienLoginService();
        $login->tokenSequence = ['stale-token', 'still-bad'];

        $service = $this->makeService($mock, $login);

        $result = $service->submitXml('<Root/>');

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['statusCode']);
        $this->assertEquals(1, $login->logoutCallCount, 'Chỉ re-auth đúng 1 lần, không lặp vô hạn');
        $this->assertEquals(2, $login->tokenCallCount);
    }
}
