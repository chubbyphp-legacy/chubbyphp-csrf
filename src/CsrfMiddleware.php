<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

use Chubbyphp\ErrorHandler\HttpException;
use Chubbyphp\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

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

    /**
     * @var LoggerInterface
     */
    private $logger;

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
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $this->logger->debug('csrf: check token');
            $this->checkCsrf($request, $response);
        }

        if (!$this->session->has($request, self::CSRF_KEY)) {
            $this->logger->debug('csrf: set token');
            $this->session->set($request, self::CSRF_KEY, $this->csrfTokenGenerator->generate());
        }

        if (null !== $next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @throws HttpException
     */
    private function checkCsrf(Request $request, Response $response)
    {
        if (!$this->session->has($request, self::CSRF_KEY)) {
            $this->throwException($request, $response, self::EXCEPTION_MISSING_IN_SESSION);
        }

        $data = $request->getParsedBody();

        if (!isset($data[self::CSRF_KEY])) {
            $this->throwException($request, $response, self::EXCEPTION_MISSING_IN_BODY);
        }

        if ($this->session->get($request, self::CSRF_KEY) !== $data[self::CSRF_KEY]) {
            $this->throwException($request, $response, self::EXCEPTION_IS_NOT_SAME);
        }
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param string   $message
     *
     * @throws HttpException
     */
    private function throwException(Request $request, Response $response, string $message)
    {
        $this->logger->error('csrf: error {code} {message}', ['code' => self::EXCEPTION_STATUS, 'message' => $message]);

        throw HttpException::create($request, $response, self::EXCEPTION_STATUS, $message);
    }
}
