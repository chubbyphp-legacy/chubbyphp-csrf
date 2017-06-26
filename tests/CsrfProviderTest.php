<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Csrf\CsrfErrorHandlerInterface;
use Chubbyphp\Csrf\CsrfErrorResponseMiddleware;
use Chubbyphp\Csrf\CsrfProvider;
use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\ErrorHandler\HttpException;
use Chubbyphp\Session\SessionInterface;
use Pimple\Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * @covers \Chubbyphp\Csrf\CsrfProvider
 */
final class CsrfProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $container = new Container();
        $container->register(new CsrfProvider());

        $container['session'] = $this->getSession();

        self::assertTrue(isset($container['csrf.tokenGenerator.entropy']));
        self::assertTrue(isset($container['csrf.tokenGenerator']));
        self::assertTrue(isset($container['csrf.errorResponseHandler']));
        self::assertTrue(isset($container['csrf.middleware']));

        self::assertSame(256, $container['csrf.tokenGenerator.entropy']);

        self::assertInstanceOf(CsrfTokenGeneratorInterface::class, $container['csrf.tokenGenerator']);
        self::assertInstanceOf(CsrfErrorHandlerInterface::class, $container['csrf.errorResponseHandler']);
        self::assertInstanceOf(CsrfErrorResponseMiddleware::class, $container['csrf.middleware']);

        $request = $this->getRequest();
        $response = $this->getResponse();

        try {
            $container['csrf.errorResponseHandler']->errorResponse($request, $response, 424, 'test');
        } catch (HttpException $e) {
            self::assertSame(424, $e->getCode());
            self::assertSame('test', $e->getMessage());

            return;
        }

        self::fail(sprintf('Expected %s', HttpException::class));
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRequest(): Request
    {
        return $this
            ->getMockBuilder(Request::class)
            ->getMockForAbstractClass()
        ;
    }

    /**
     * @return Response|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getResponse(): Response
    {
        return $this
            ->getMockBuilder(Response::class)
            ->getMockForAbstractClass()
        ;
    }

    /**
     * @return SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSession(): SessionInterface
    {
        $session = $this
            ->getMockBuilder(SessionInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass()
        ;

        return $session;
    }
}
