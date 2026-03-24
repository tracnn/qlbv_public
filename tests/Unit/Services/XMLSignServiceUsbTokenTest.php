<?php

namespace Tests\Unit\Services;

use App\Services\ACSLoginService;
use App\Services\XMLSignService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class XMLSignServiceUsbTokenTest extends TestCase
{
    private function makeService(array $usbConfig, MockHandler $mock): XMLSignService
    {
        Config::set('organization.usb_token_sign', $usbConfig);
        Config::set('organization.xml_sign', ['enabled' => false]);

        $mockAcs = $this->createMock(ACSLoginService::class);
        $service = new XMLSignService($mockAcs);

        $stack = HandlerStack::create($mock);
        $client = new Client(['handler' => $stack]);
        $ref = new \ReflectionProperty(XMLSignService::class, 'httpClient');
        $ref->setAccessible(true);
        $ref->setValue($service, $client);

        return $service;
    }

    /** @test */
    public function usb_token_mode_calls_local_service_and_returns_signed_xml(): void
    {
        $signedXml = '<Root><CHUKYDONVI><Signature/></CHUKYDONVI></Root>';
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Success' => true,
                'Data'    => base64_encode($signedXml),
                'Param'   => ['Messages' => []],
            ])),
        ]);

        $service = $this->makeService([
            'enabled'                   => true,
            'endpoint'                  => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token'             => 'test-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'                   => 30,
        ], $mock);

        $result = $service->signXml('<Root><CHUKYDONVI/></Root>');

        $this->assertTrue($result['isSigned']);
        $this->assertEquals($signedXml, $result['data']);
    }

    /** @test */
    public function usb_token_service_failure_returns_isSigned_false_with_error(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'Success' => false,
                'Data'    => null,
                'Param'   => ['Messages' => ['PIN không chính xác']],
            ])),
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'test-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $result = $service->signXml('<Root/>');

        $this->assertFalse($result['isSigned']);
        // PHPUnit 6 compatible: use assertContains instead of assertStringContainsString
        $this->assertContains('PIN', $result['error']);
    }

    /** @test */
    public function isEnabled_true_when_only_usb_token_enabled(): void
    {
        Config::set('organization.usb_token_sign', ['enabled' => true]);
        Config::set('organization.xml_sign', ['enabled' => false]);
        $mockAcs = $this->createMock(ACSLoginService::class);

        $service = new XMLSignService($mockAcs);
        $this->assertTrue($service->isEnabled());
    }

    /** @test */
    public function isEnabled_false_when_both_disabled(): void
    {
        Config::set('organization.usb_token_sign', ['enabled' => false]);
        Config::set('organization.xml_sign', ['enabled' => false]);
        $mockAcs = $this->createMock(ACSLoginService::class);

        $service = new XMLSignService($mockAcs);
        $this->assertFalse($service->isEnabled());
    }

    /** @test */
    public function request_sends_x_service_token_header(): void
    {
        $capturedHeaders = null;
        $mock = new MockHandler([
            function ($request) use (&$capturedHeaders) {
                $capturedHeaders = $request->getHeaders();
                return new Response(200, [], json_encode([
                    'Success' => true,
                    'Data'    => base64_encode('<Root/>'),
                    'Param'   => ['Messages' => []],
                ]));
            },
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'my-secret-token',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $service->signXml('<Root/>');

        $this->assertArrayHasKey('X-Service-Token', $capturedHeaders);
        $this->assertEquals(['my-secret-token'], $capturedHeaders['X-Service-Token']);
    }

    /** @test */
    public function configData_serializes_as_json_object_not_array(): void
    {
        $capturedBody = null;
        $mock = new MockHandler([
            function ($request) use (&$capturedBody) {
                // Capture the raw body string to inspect JSON encoding directly
                $capturedBody = $request->getBody()->getContents();
                return new Response(200, [], json_encode([
                    'Success' => true,
                    'Data'    => base64_encode('<Root/>'),
                    'Param'   => ['Messages' => []],
                ]));
            },
        ]);

        $service = $this->makeService([
            'enabled'       => true,
            'endpoint'      => 'http://127.0.0.1:18081/api/EmrSign/SignXmlBhyt',
            'service_token' => 'x',
            'tag_store_signature_value' => 'CHUKYDONVI',
            'timeout'       => 30,
        ], $mock);

        $service->signXml('<Root/>');

        $this->assertNotNull($capturedBody);
        // Verify ConfigData is encoded as JSON object {} not array []
        // by checking the raw JSON body string contains "ConfigData":{}
        $this->assertContains('"ConfigData":{}', $capturedBody, 'ConfigData must encode as {} not []');
    }
}
