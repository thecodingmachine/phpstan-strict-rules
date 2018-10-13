<?php declare(strict_types=1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Testing\RuleTestCase;
use TheCodingMachine\PHPStan\Rules\Exceptions\DoNotThrowExceptionBaseClassRule;

class DoNotThrowExceptionBaseClassRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new DoNotThrowExceptionBaseClassRule();
    }

    public function testCheckCatchedException()
    {
        $this->analyse([__DIR__ . '/data/throw_exception.php'], [
            [
                'Do not throw the \Exception base class. Instead, extend the \Exception base class. More info: http://bit.ly/subtypeexception',
                16,
            ],
        ]);
    }
}
