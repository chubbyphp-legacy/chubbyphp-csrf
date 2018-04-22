<?php

namespace Chubbyphp\Tests\Csrf;

use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\Csrf\CsrfMiddleware;
use Chubbyphp\ErrorHandler\HttpException;
use Chubbyphp\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

/**
 * @covers \Chubbyphp\Csrf\CsrfMiddleware
 */
final class CsrfMiddlewareTest extends TestCase
{
    public function testInvokeWithGetRequestWithoutNext()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([]),
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

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([]),
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
        try {
            $logger = $this->getLogger();

            $middleware = new CsrfMiddleware(
                $this->getCsrfTokenGenerator(),
                $this->getSession([]),
                $logger
            );

            $request = $this->getRequest('POST', []);
            $response = $this->getResponse();

            $middleware($request, $response);
        } catch (HttpException $e) {
            self::assertSame(CsrfMiddleware::EXCEPTION_MISSING_IN_SESSION, $e->getMessage());
            self::assertSame(CsrfMiddleware::EXCEPTION_STATUS, $e->getCode());

            self::assertCount(2, $logger->__logs);
            self::assertSame('info', $logger->__logs[0]['level']);
            self::assertSame('csrf: check token', $logger->__logs[0]['message']);
            self::assertSame('error', $logger->__logs[1]['level']);
            self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
            self::assertSame(
                [
                    'status' => CsrfMiddleware::EXCEPTION_STATUS,
                    'message' => CsrfMiddleware::EXCEPTION_MISSING_IN_SESSION,
                ],
                $logger->__logs[1]['context']
            );

            return;
        }

        self::fail(sprintf('Expected %s', HttpException::class));
    }

    public function testInvokeWithPostRequestWithTokenWithoutData()
    {
        try {
            $logger = $this->getLogger();

            $middleware = new CsrfMiddleware(
                $this->getCsrfTokenGenerator(),
                $this->getSession([
                    CsrfMiddleware::CSRF_KEY => 'token',
                ]),
                $logger
            );

            $request = $this->getRequest('POST', []);
            $response = $this->getResponse();

            $middleware($request, $response);
        } catch (HttpException $e) {
            self::assertSame(CsrfMiddleware::EXCEPTION_MISSING_IN_BODY, $e->getMessage());
            self::assertSame(CsrfMiddleware::EXCEPTION_STATUS, $e->getCode());

            self::assertCount(2, $logger->__logs);
            self::assertSame('info', $logger->__logs[0]['level']);
            self::assertSame('csrf: check token', $logger->__logs[0]['message']);
            self::assertSame('error', $logger->__logs[1]['level']);
            self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
            self::assertSame(
                [
                    'status' => CsrfMiddleware::EXCEPTION_STATUS,
                    'message' => CsrfMiddleware::EXCEPTION_MISSING_IN_BODY,
                ],
                $logger->__logs[1]['context']
            );

            return;
        }

        self::fail(sprintf('Expected %s', HttpException::class));
    }

    public function testInvokeWithPostRequestWithTokenWithData()
    {
        $logger = $this->getLogger();

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfMiddleware::CSRF_KEY => 'token',
            ]),
            $logger
        );

        $request = $this->getRequest('POST', [CsrfMiddleware::CSRF_KEY => 'token']);
        $response = $this->getResponse();

        $middleware($request, $response);

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('csrf: check token', $logger->__logs[0]['message']);
    }

    public function testInvokeWithPostRequestWithTokenWithInvalidData()
    {
        try {
            $logger = $this->getLogger();

            $middleware = new CsrfMiddleware(
                $this->getCsrfTokenGenerator(),
                $this->getSession([
                    CsrfMiddleware::CSRF_KEY => 'token',
                ]),
                $logger
            );

            $request = $this->getRequest('POST', [CsrfMiddleware::CSRF_KEY => 'invalidtoken']);
            $response = $this->getResponse();

            $middleware($request, $response);
        } catch (HttpException $e) {
            self::assertSame(CsrfMiddleware::EXCEPTION_IS_NOT_SAME, $e->getMessage());
            self::assertSame(CsrfMiddleware::EXCEPTION_STATUS, $e->getCode());

            self::assertCount(2, $logger->__logs);
            self::assertSame('info', $logger->__logs[0]['level']);
            self::assertSame('csrf: check token', $logger->__logs[0]['message']);
            self::assertSame('error', $logger->__logs[1]['level']);
            self::assertSame('csrf: error {status} {message}', $logger->__logs[1]['message']);
            self::assertSame(
                [
                    'status' => CsrfMiddleware::EXCEPTION_STATUS,
                    'message' => CsrfMiddleware::EXCEPTION_IS_NOT_SAME,
                ],
                $logger->__logs[1]['context']
            );

            return;
        }

        self::fail(sprintf('Expected %s', HttpException::class));
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
            ->setMethods(['withStatus'])
            ->getMockForAbstractClass()
        ;

        $response
            ->expects(self::any())
            ->method('withStatus')
            ->willReturnCallback(
                function (int $status) use ($response) {
                    return $response;
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
