<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Csrf\CsrfProvider;
use Chubbyphp\Csrf\CsrfMiddleware;
use Chubbyphp\Csrf\CsrfTokenGeneratorInterface;
use Chubbyphp\Session\SessionInterface;
use Pimple\Container;

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
        self::assertTrue(isset($container['csrf.middleware']));

        self::assertSame(256, $container['csrf.tokenGenerator.entropy']);

        self::assertInstanceOf(CsrfTokenGeneratorInterface::class, $container['csrf.tokenGenerator']);
        self::assertInstanceOf(CsrfMiddleware::class, $container['csrf.middleware']);
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
