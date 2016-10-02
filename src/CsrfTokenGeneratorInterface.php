<?php

namespace Chubbyphp\Csrf;

interface CsrfTokenGeneratorInterface
{
    /**
     * @return string
     */
    public function generate(): string;
}
