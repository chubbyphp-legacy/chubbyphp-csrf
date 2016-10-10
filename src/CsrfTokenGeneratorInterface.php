<?php

declare(strict_types=1);

namespace Chubbyphp\Csrf;

interface CsrfTokenGeneratorInterface
{
    /**
     * @return string
     */
    public function generate(): string;
}
