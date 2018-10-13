<?php

namespace TheCodingMachine\PHPStan\Rules\Conditionals;

use PHPStan\Testing\RuleTestCase;

class SwitchMustContainDefaultRuleTest extends RuleTestCase
{
    protected function getRule(): \PHPStan\Rules\Rule
    {
        return new SwitchMustContainDefaultRule();
    }

    public function testProcessNode()
    {
        $this->analyse([__DIR__ . '/data/switch.php'], [
            [
                'In function "baz", switch statement does not have a "default" case. If your code is supposed to enter at least one "case" or another, consider adding a "default" case that throws an exception. More info: http://bit.ly/switchdefault',
                11,
            ],
        ]);
    }
}
