<?php

namespace Chubbyphp\Tests\Csrf;

use Chubbyphp\Csrf\CsrfErrorHandlerInterface;
use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\Csrf\CsrfErrorResponseMiddleware;
use Chubbyphp\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\Csrf\CsrfErrorResponseMiddleware
 */
final class CsrfErrorResponseMiddlewareTest extends TestCase
{
    public function testInvokeWithGetRequestWithoutNext()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('GET', []);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: set token', $logger->__logs[0]['message']);
    }

    public function testInvokeWithGetRequestWithNext()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('GET', []);
        $response = $this->getResponse();

        $middleware($request, $response, function (Request $request, Response $response) {
        });

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: set token', $logger->__logs[0]['message']);
    }

    public function testInvokeWithPostRequestWithoutToken()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('POST', []);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_MISSING_IN_SESSION, $response->getReasonPhrase());
        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_STATUS, $response->getStatusCode());

        self::assertCount(2, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: check token', $logger->__logs[0]['message']);
        self::assertSame('error', $logger->__logs[1]['level']);
        self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
        self::assertSame(
            [
                'status' => CsrfErrorResponseMiddleware::EXCEPTION_STATUS,
                'message' => CsrfErrorResponseMiddleware::EXCEPTION_MISSING_IN_SESSION,
            ],
            $logger->__logs[1]['context']
        );
    }

    public function testInvokeWithPostRequestWithTokenWithoutData()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfErrorResponseMiddleware::CSRF_KEY => 'token',
            ]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('POST', []);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_MISSING_IN_BODY, $response->getReasonPhrase());
        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_STATUS, $response->getStatusCode());

        self::assertCount(2, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: check token', $logger->__logs[0]['message']);
        self::assertSame('error', $logger->__logs[1]['level']);
        self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
        self::assertSame(
            [
                'status' => CsrfErrorResponseMiddleware::EXCEPTION_STATUS,
                'message' => CsrfErrorResponseMiddleware::EXCEPTION_MISSING_IN_BODY,
            ],
            $logger->__logs[1]['context']
        );
    }

    public function testInvokeWithPostRequestWithTokenWithData()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfErrorResponseMiddleware::CSRF_KEY => 'token',
            ]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('POST', [CsrfErrorResponseMiddleware::CSRF_KEY => 'token']);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: check token', $logger->__logs[0]['message']);
    }

    public function testInvokeWithPostRequestWithTokenWithInvalidData()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfErrorResponseMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfErrorResponseMiddleware::CSRF_KEY => 'token',
            ]),
            $this->getErrorHandler(),
            $logger
        );

        $request = $this->getRequest('POST', [CsrfErrorResponseMiddleware::CSRF_KEY => 'invalidtoken']);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_IS_NOT_SAME, $response->getReasonPhrase());
        self::assertSame(CsrfErrorResponseMiddleware::EXCEPTION_STATUS, $response->getStatusCode());

        self::assertCount(2, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: check token', $logger->__logs[0]['message']);
        self::assertSame('error', $logger->__logs[1]['level']);
        self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
        self::assertSame(
            [
                'status' => CsrfErrorResponseMiddleware::EXCEPTION_STATUS,
                'message' => CsrfErrorResponseMiddleware::EXCEPTION_IS_NOT_SAME,
            ],
            $logger->__logs[1]['context']
        );
    }

    /**
     * @return CsrfTokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCsrfTokenGenerator(): CsrfTokenGeneratorInterface
    {
        /** @var CsrfTokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject $tokenGenerator */
        $tokenGenerator = $this
            ->getMockBuilder(CsrfTokenGeneratorInterface::class)
            ->setMethods(['generate'])
            ->getMockForAbstractClass()
        ;

        $tokenGenerator->expects(self::any())->method('generate')->willReturn('token');

        return $tokenGenerator;
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRequest(string $method, array $data): Request
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this
            ->getMockBuilder(Request::class)
            ->setMethods(['getMethod', 'getParsedBody'])
            ->getMockForAbstractClass()
        ;

        $request->expects(self::any())->method('getMethod')->willReturn($method);
        $request->expects(self::any())->method('getParsedBody')->willReturn($data);

        return $request;
    }

    /**
     * @return Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getResponse(): Response
    {
        /** @var Response|\PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this
            ->getMockBuilder(Response::class)
            ->setMethods(['withStatus', 'getStatusCode', 'getReasonPhrase'])
            ->getMockForAbstractClass()
        ;

        $response->__data = [
            'code' => null,
            'reasonPhrase' => null,
        ];

        $response
            ->expects(self::any())
            ->method('withStatus')
            ->willReturnCallback(
                function (int $code, string $reasonPhrase) use ($response) {
                    $response->__data['code'] = $code;
                    $response->__data['reasonPhrase'] = $reasonPhrase;

                    return $response;
                }
            )
        ;

        $response
            ->expects(self::any())
            ->method('getStatusCode')
            ->willReturnCallback(
                function () use ($response) {
                    return $response->__data['code'];
                }
            )
        ;

        $response
            ->expects(self::any())
            ->method('getReasonPhrase')
            ->willReturnCallback(
                function () use ($response) {
                    return $response->__data['reasonPhrase'];
                }
            )
        ;

        return $response;
    }

    /**
     * @param array $data
     *
     * @return SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSession(array $data): SessionInterface
    {
        /** @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this
            ->getMockBuilder(SessionInterface::class)
            ->setMethods(['get', 'has', 'set'])
            ->getMockForAbstractClass()
        ;

        $session->__data = $data;

        $session
            ->expects(self::any())
            ->method('get')
            ->willReturnCallback(
                function (Request $request, string $key) use ($session) {
                    return $session->__data[$key];
                }
            )
        ;

        $session
            ->expects(self::any())
            ->method('has')
            ->willReturnCallback(
                function (Request $request, string $key) use ($session) {
                    return isset($session->__data[$key]);
                }
            )
        ;

        $session
            ->expects(self::any())
            ->method('set')
            ->willReturnCallback(
                function (Request $request, string $key, $value) use ($session) {
                    $session->__data[$key] = $value;
                }
            )
        ;

        return $session;
    }

    /**
     * @return CsrfErrorHandlerInterface
     */
    private function getErrorHandler(): CsrfErrorHandlerInterface
    {
        /** @var CsrfErrorHandlerInterface|\PHPUnit_Framework_MockObject_MockObject $errorHandler */
        $errorHandler = $this
            ->getMockBuilder(CsrfErrorHandlerInterface::class)
            ->setMethods(['errorResponse'])
            ->getMockForAbstractClass()
        ;

        $errorHandler
            ->expects(self::any())
            ->method('errorResponse')
            ->willReturnCallback(
                function (Request $request, Response $response, int $code, string $reasonPhrase) {
                    return $response->withStatus($code, $reasonPhrase);
                }
            )
        ;

        return $errorHandler;
    }

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        $methods = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->setMethods(array_merge($methods, ['log']))
            ->getMockForAbstractClass()
        ;

        $logger->__logs = [];

        foreach ($methods as $method) {
            $logger
                ->expects(self::any())
                ->method($method)
                ->willReturnCallback(
                    function (string $message, array $context = []) use ($logger, $method) {
                        $logger->log($method, $message, $context);
                    }
                )
            ;
        }

        $logger
            ->expects(self::any())
            ->method('log')
            ->willReturnCallback(
                function (string $level, string $message, array $context = []) use ($logger) {
                    $logger->__logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
                }
            )
        ;

        return $logger;
    }
}
