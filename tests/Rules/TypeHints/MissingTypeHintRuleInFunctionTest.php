<?php declare(strict_types = 1);

namespace TheCodingMachine\PHPStan\Rules\TypeHints;

class MissingTypeHintRuleInFunctionTest extends \PHPStan\Rules\AbstractRuleTest
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new MissingTypeHintInFunctionRule(
		    $this->createBroker()
        );
	}

	public function testCheckCatchedException()
	{
        require_once __DIR__.'/data/typehints.php';

        $this->analyse([__DIR__ . '/data/typehints.php'], [
			[
				'Parameter $no_type_hint has no type-hint and no @param annotation.',
				3,
			],
            [
                'Parameter $type_hintable can be type-hinted to "?string".',
                11,
            ],
            [
                'Parameter $type_hintable can be type-hinted to "\DateTimeInterface".',
                19,
            ],
            [
                'Parameter $type_hintable can be type-hinted to "array".',
                27,
            ],
            [
                'Parameter $better_type_hint type is "array". Please provide a @param annotation to further speciy the type of the array. For instance: @param int[] $better_type_hint',
                40,
            ],
            [
                'Parameter $param type is type-hinted to "string" but the @param annotation says it is a "int". Please fix the @param annotation.',
                48,
            ],
		]);
	}
}
