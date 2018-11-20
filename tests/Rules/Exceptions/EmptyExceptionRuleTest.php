<?php declare(strict_types=1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Testing\RuleTestCase;
use TheCodingMachine\PHPStan\Rules\Exceptions\EmptyExceptionRule;

class EmptyExceptionRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new EmptyExceptionRule();
    }

    public function testCheckCatchedException()
    {
        $this->analyse([__DIR__ . '/data/catch.php'], [
            [
                'Empty catch block. If you are sure this is meant to be empty, please add a "// @ignoreException" comment in the catch block.',
                14,
            ],
            [
                'Empty catch block. If you are sure this is meant to be empty, please add a "// @ignoreException" comment in the catch block.',
                24,
            ],
        ]);
    }
}
