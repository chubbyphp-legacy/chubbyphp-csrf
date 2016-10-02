<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Csrf\CsrfProvider;
use Chubbyphp\Csrf\CsrfMiddleware;
use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\ErrorHandler\ErrorHandlerInterface;
use Chubbyphp\Session\SessionInterface;
use Pimple\Container;

/**
 * @covers Chubbyphp\Csrf\CsrfProvider
 */
final class CsrfProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $container = new Container();
        $container->register(new CsrfProvider());

        $container['csrf.errorHandler.key'] = 'myproject.errorHandler';

        $container['myproject.errorHandler'] = $this->getErrorHandler();
        $container['session'] = $this->getSession();

        self::assertTrue(isset($container['csrf.tokenGenerator.entropy']));
        self::assertTrue(isset($container['csrf.tokenGenerator']));
        self::assertTrue(isset($container['csrf.middleware']));
        self::assertTrue(isset($container['csrf.errorHandler.key']));

        self::assertSame(256, $container['csrf.tokenGenerator.entropy']);

        self::assertInstanceOf(CsrfTokenGeneratorInterface::class, $container['csrf.tokenGenerator']);
        self::assertInstanceOf(CsrfMiddleware::class, $container['csrf.middleware']);
        self::assertSame('myproject.errorHandler', $container['csrf.errorHandler.key']);
    }

    /**
     * @return ErrorHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getErrorHandler(): ErrorHandlerInterface
    {
        $errorHandler = $this
            ->getMockBuilder(ErrorHandlerInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass()
        ;

        return $errorHandler;
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
