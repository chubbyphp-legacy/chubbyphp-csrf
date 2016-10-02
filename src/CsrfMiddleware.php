<?php

namespace Chubbyphp\Csrf;

use Chubbyphp\ErrorHandler\ErrorHandlerInterface;
use Chubbyphp\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

final class CsrfMiddleware
{
    /**
     * @var CsrfTokenGeneratorInterface
     */
    private $csrfTokenGenerator;

    /**
     * @var ErrorHandlerInterface
     */
    private $errorHandler;

    /**
     * @var SessionInterface
     */
    private $session;

    const CSRF_KEY = 'csrf';

    /**
     * @param CsrfTokenGeneratorInterface $csrfTokenGenerator
     * @param ErrorHandlerInterface       $errorHandler
     * @param SessionInterface            $session
     */
    public function __construct(
        CsrfTokenGeneratorInterface $csrfTokenGenerator,
        ErrorHandlerInterface $errorHandler,
        SessionInterface $session
    ) {
        $this->csrfTokenGenerator = $csrfTokenGenerator;
        $this->errorHandler = $errorHandler;
        $this->session = $session;
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
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!$this->checkCsrf($request)) {
                return $this->errorHandler->error($request, $response, 424);
            }
        }

        if (!$this->session->has($request, self::CSRF_KEY)) {
            $this->session->set($request, self::CSRF_KEY, $this->csrfTokenGenerator->generate());
        }

        if (null !== $next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function checkCsrf(Request $request): bool
    {
        if (!$this->session->has($request, self::CSRF_KEY)) {
            return false;
        }

        $data = $request->getParsedBody();

        if (!isset($data[self::CSRF_KEY])) {
            return false;
        }

        if ($this->session->get($request, self::CSRF_KEY) !== $data[self::CSRF_KEY]) {
            return false;
        }

        return true;
    }
}
