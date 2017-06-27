<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

use Chubbyphp\ErrorHandler\HttpException;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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

        $container['csrf.errorResponseHandler'] = new class() implements CsrfErrorHandlerInterface {
            public function errorResponse(
                Request $request,
                Response $response,
                int $code,
                string $reasonPhrase = null
            ): Response {
                throw HttpException::create($request, $response, $code, $reasonPhrase);
            }
        };

        $container['csrf.middleware'] = function () use ($container) {
            return new CsrfErrorResponseMiddleware(
                $container['csrf.tokenGenerator'],
                $container['session'],
                $container['csrf.errorResponseHandler'],
                $container['logger'] ?? null
            );
        };
    }
}
