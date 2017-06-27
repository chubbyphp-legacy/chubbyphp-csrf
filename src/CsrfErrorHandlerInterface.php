<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

interface CsrfErrorHandlerInterface
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param int      $code
     * @param string   $reasonPhrase
     *
     * @return Response
     */
    public function errorResponse(Request $request, Response $response, int $code, string $reasonPhrase): Response;
}
