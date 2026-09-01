<?php

use App\Controllers\Auth;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class AuthSyncResultTest extends CIUnitTestCase
{
    private Auth $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new class () extends Auth {
            public function __construct()
            {
            }

            public function accurateSucceeded(?array $response, ?int $httpCode = null): bool
            {
                return $this->isAccurateSuccess($response, $httpCode);
            }

            public function errorMessage(?array $response, string $curlError = ''): string
            {
                return $this->accurateErrorMessage($response, $curlError);
            }

            public function decodeResponse($response, int $httpCode, string $curlError = ''): array
            {
                $method = new ReflectionMethod(Auth::class, 'decodeAccurateResponse');

                return $method->invoke($this, $response, $httpCode, $curlError);
            }
        };
    }

    public function testAccurateSuccessAcceptsSupportedBooleanRepresentations(): void
    {
        $this->assertTrue($this->controller->accurateSucceeded(['s' => true], 200));
        $this->assertTrue($this->controller->accurateSucceeded(['s' => 1], 201));
        $this->assertTrue($this->controller->accurateSucceeded(['s' => 'true'], 200));
    }

    public function testAccurateSuccessRejectsInvalidOrFailedResponses(): void
    {
        $this->assertFalse($this->controller->accurateSucceeded(['s' => false], 200));
        $this->assertFalse($this->controller->accurateSucceeded(['s' => true], 500));
        $this->assertFalse($this->controller->accurateSucceeded([], 200));
        $this->assertFalse($this->controller->accurateSucceeded(null, 200));
    }

    public function testAccurateErrorMessageHandlesNestedDetailsAndCurlErrors(): void
    {
        $this->assertSame(
            'Nomor sudah digunakan, Detail tidak valid',
            $this->controller->errorMessage([
                'd' => ['Nomor sudah digunakan', ['Detail tidak valid']],
            ]),
        );

        $this->assertSame(
            'Connection timed out',
            $this->controller->errorMessage(null, 'Connection timed out'),
        );
    }

    public function testDecodeAccurateResponseReportsExpiredOrRejectedAccess(): void
    {
        $result = $this->controller->decodeResponse('{}', 401);

        $this->assertFalse($result['s']);
        $this->assertSame(401, $result['_http_code']);
        $this->assertStringContainsString('hubungkan ulang Accurate', $result['d'][0]);
    }

    public function testDecodeAccurateResponseReportsNetworkAndInvalidJsonErrors(): void
    {
        $networkError = $this->controller->decodeResponse(false, 0, 'Connection timed out');
        $invalidJson = $this->controller->decodeResponse('<html>error</html>', 502);

        $this->assertFalse($networkError['s']);
        $this->assertStringContainsString('Connection timed out', $networkError['d'][0]);
        $this->assertFalse($invalidJson['s']);
        $this->assertStringContainsString('HTTP 502', $invalidJson['d'][0]);
    }
}
