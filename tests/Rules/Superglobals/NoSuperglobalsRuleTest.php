<?php

namespace TheCodingMachine\PHPStan\Rules\Superglobals;

use PHPStan\Testing\RuleTestCase;

class NoSuperglobalsRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new NoSuperglobalsRule();
    }

    public function testPost()
    {
        require_once __DIR__.'/data/superglobals.php';

        $this->analyse([__DIR__ . '/data/superglobals.php'], [
            [
                'In function "foo", you should not use the $_POST superglobal. You should instead rely on your framework that provides you with a "request" object (for instance a PSR-7 RequestInterface or a Symfony Request). More info: http://bit.ly/nosuperglobals',
                8,
            ],
            [
                'In method "FooBarSuperGlobal::__construct", you should not use the $_GET superglobal. You should instead rely on your framework that provides you with a "request" object (for instance a PSR-7 RequestInterface or a Symfony Request). More info: http://bit.ly/nosuperglobals',
                15,
            ],
        ]);
    }
}
