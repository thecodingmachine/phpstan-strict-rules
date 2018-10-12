<?php declare(strict_types=1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Testing\RuleTestCase;
use TheCodingMachine\PHPStan\Rules\Exceptions\MustRethrowRule;
use TheCodingMachine\PHPStan\Rules\Exceptions\ThrowMustBundlePreviousExceptionRule;

class MustRethrowRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new MustRethrowRule();
    }

    public function testCheckCatchedException()
    {
        $this->analyse([__DIR__ . '/data/must_rethrow.php'], [
            [
                'caught \Exception, \Throwable or \RuntimeException must be rethrown. Either catch a more specific exception or add a "throw" clause in the "catch" to propagate the exception.',
                18,
            ],
            [
                'caught \Exception, \Throwable or \RuntimeException must be rethrown. Either catch a more specific exception or add a "throw" clause in the "catch" to propagate the exception.',
                24,
            ],
            [
                'In function "TestCatch\foo", caught \Exception, \Throwable or \RuntimeException must be rethrown. Either catch a more specific exception or add a "throw" clause in the "catch" to propagate the exception.',
                31,
            ],
        ]);
    }
}
