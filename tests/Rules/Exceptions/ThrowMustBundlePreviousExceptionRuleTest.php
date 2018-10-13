<?php declare(strict_types=1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Testing\RuleTestCase;
use TheCodingMachine\PHPStan\Rules\Exceptions\ThrowMustBundlePreviousExceptionRule;

class ThrowMustBundlePreviousExceptionRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new ThrowMustBundlePreviousExceptionRule();
    }

    public function testCheckCatchedException()
    {
        $this->analyse([__DIR__ . '/data/throw_must_bundle_previous_exception.php'], [
            [
                'Thrown exceptions in a catch block must bundle the previous exception (see throw statement line 28). More info: http://bit.ly/bundleexception',
                26,
            ],
        ]);
    }
}
