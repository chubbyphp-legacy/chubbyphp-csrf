<?php

namespace Chubbyphp\Csrf;

use Chubbyphp\ErrorHandler\HttpException;
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
     * @var SessionInterface
     */
    private $session;

    const CSRF_KEY = 'csrf';

    const EXCEPTION_STATUS = 424;

    const EXCEPTION_MISSING_IN_SESSION = 'Csrf token is missing within session';
    const EXCEPTION_MISSING_IN_BODY = 'Csrf token is missing within body';
    const EXCEPTION_IS_NOT_SAME = 'Csrf token within body is not the same as in session';

    /**
     * @param CsrfTokenGeneratorInterface $csrfTokenGenerator
     * @param SessionInterface            $session
     */
    public function __construct(CsrfTokenGeneratorInterface $csrfTokenGenerator, SessionInterface $session)
    {
        $this->csrfTokenGenerator = $csrfTokenGenerator;
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
            $this->checkCsrf($request);
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
     * @throws HttpException
     */
    private function checkCsrf(Request $request)
    {
        if (!$this->session->has($request, self::CSRF_KEY)) {
            $this->throwException(self::EXCEPTION_MISSING_IN_SESSION);
        }

        $data = $request->getParsedBody();

        if (!isset($data[self::CSRF_KEY])) {
            $this->throwException(self::EXCEPTION_MISSING_IN_BODY);
        }

        if ($this->session->get($request, self::CSRF_KEY) !== $data[self::CSRF_KEY]) {
            $this->throwException(self::EXCEPTION_IS_NOT_SAME);
        }
    }

    /**
     * @param string $message
     *
     * @throws HttpException
     */
    private function throwException(string $message)
    {
        throw HttpException::create(self::EXCEPTION_STATUS, $message);
    }
}
