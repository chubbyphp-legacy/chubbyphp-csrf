<?php

namespace Chubbyphp\Tests\Csrf;

use Chubbyphp\Csrf\CsrfTokenGenerator;

/**
 * @covers Chubbyphp\Csrf\CsrfTokenGenerator
 */
final class CsrfTokenGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $tokenGenerator = new CsrfTokenGenerator();

        $token = $tokenGenerator->generate();

        self::assertTrue(43 === strlen($token), 'Token got an invalid length');
        self::assertRegExp('/[a-i0-9\-]/i', $token);
    }
}
