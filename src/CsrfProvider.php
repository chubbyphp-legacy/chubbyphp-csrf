<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

final class CsrfProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $container['csrf.tokenGenerator.entropy'] = 256;

        $container['csrf.tokenGenerator'] = function () use ($container) {
            return new CsrfTokenGenerator($container['csrf.tokenGenerator.entropy']);
        };

        $container['csrf.middleware'] = function () use ($container) {
            return new CsrfMiddleware($container['csrf.tokenGenerator'], $container['session']);
        };
    }
}
