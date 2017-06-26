<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

use Chubbyphp\ErrorHandler\HttpException;
use Chubbyphp\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @deprecated use CsrfErrorResponseMiddleware
 */
final class CsrfMiddleware
{
    /**
     * @var CsrfErrorResponseMiddleware
     */
    private $csrfErrorResponseMiddleware;

    const CSRF_KEY = 'csrf';

    const EXCEPTION_STATUS = 424;

    const EXCEPTION_MISSING_IN_SESSION = 'Csrf token is missing within session';
    const EXCEPTION_MISSING_IN_BODY = 'Csrf token is missing within body';
    const EXCEPTION_IS_NOT_SAME = 'Csrf token within body is not the same as in session';

    /**
     * @param CsrfTokenGeneratorInterface $csrfTokenGenerator
     * @param SessionInterface            $session
     * @param LoggerInterface|null        $logger
     */
    public function __construct(
        CsrfTokenGeneratorInterface $csrfTokenGenerator,
        SessionInterface $session,
        LoggerInterface $logger = null
    ) {
        $this->csrfErrorResponseMiddleware = new CsrfErrorResponseMiddleware(
            $csrfTokenGenerator,
            $session,
            new class() implements CsrfErrorHandlerInterface {
                public function errorResponse(
                    Request $request,
                    Response $response,
                    int $code,
                    string $reasonPhrase
                ): Response {
                    throw HttpException::create($request, $response, $code, $reasonPhrase);
                }
            },
            $logger
        );

        $this->csrfTokenGenerator = $csrfTokenGenerator;
        $this->session = $session;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param callable $next
     *
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next = null)
    {
        return $this->csrfErrorResponseMiddleware->__invoke($request, $response, $next);
    }
}
