<?php

namespace Chubbyphp\Tests\Csrf;

use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\Csrf\CsrfMiddleware;
use Chubbyphp\ErrorHandler\HttpException;
use Chubbyphp\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * @covers Chubbyphp\Csrf\CsrfMiddleware
 */
final class CsrfMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeWithGetRequestWithoutNext()
    {
        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([])
        );

        $request = $this->getRequest('GET', []);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response);
    }

    public function testInvokeWithGetRequestWithNext()
    {
        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([])
        );

        $request = $this->getRequest('GET', []);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response, function (Request $request, Response $response) {
        });
    }

    public function testInvokeWithPostRequestWithoutToken()
    {
        self::expectException(HttpException::class);
        self::expectExceptionMessage(CsrfMiddleware::EXCEPTION_MISSING_IN_SESSION);
        self::expectExceptionCode(CsrfMiddleware::EXCEPTION_STATUS);

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([])
        );

        $request = $this->getRequest('POST', []);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response);
    }

    public function testInvokeWithPostRequestWithTokenWithoutData()
    {
        self::expectException(HttpException::class);
        self::expectExceptionMessage(CsrfMiddleware::EXCEPTION_MISSING_IN_BODY);
        self::expectExceptionCode(CsrfMiddleware::EXCEPTION_STATUS);

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfMiddleware::CSRF_KEY => 'token',
            ])
        );

        $request = $this->getRequest('POST', []);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response);
    }

    public function testInvokeWithPostRequestWithTokenWithData()
    {
        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfMiddleware::CSRF_KEY => 'token',
            ])
        );

        $request = $this->getRequest('POST', [CsrfMiddleware::CSRF_KEY => 'token']);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response);
    }

    public function testInvokeWithPostRequestWithTokenWithInvalidData()
    {
        self::expectException(HttpException::class);
        self::expectExceptionMessage(CsrfMiddleware::EXCEPTION_IS_NOT_SAME);
        self::expectExceptionCode(CsrfMiddleware::EXCEPTION_STATUS);

        $middleware = new CsrfMiddleware(
            $this->getCsrfTokenGenerator(),
            $this->getSession([
                CsrfMiddleware::CSRF_KEY => 'token',
            ])
        );

        $request = $this->getRequest('POST', [CsrfMiddleware::CSRF_KEY => 'invalidtoken']);
        $response = $this->getResponse();

        $middleware->__invoke($request, $response);
    }

    /**
     * @return CsrfTokenGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCsrfTokenGenerator(): CsrfTokenGeneratorInterface
    {
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
        $request = $this
            ->getMockBuilder(Request::class)
            ->setMethods(['getMethod'])
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
}
