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
				'In function "test", parameter $no_type_hint has no type-hint and no @param annotation.',
				3,
			],
            [
                'In function "test", there is no return type and no @return annotation.',
                3,
            ],
            [
                'In function "test2", parameter $type_hintable can be type-hinted to "?string".',
                12,
            ],
            [
                'In function "test2", a "string" return type can be added.',
                12,
            ],
            [
                'In function "test3", parameter $type_hintable can be type-hinted to "\DateTimeInterface".',
                21,
            ],
            [
                'In function "test3", a "\DateTimeInterface" return type can be added.',
                21,
            ],
            [
                'In function "test4", parameter $type_hintable can be type-hinted to "array".',
                30,
            ],
            [
                'In function "test4", a "array" return type can be added.',
                30,
            ],
            [
                'In function "test6", parameter $better_type_hint type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $better_type_hint',
                43,
            ],
            [
                'In function "test6", return type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @return int[]',
                43,
            ],
            [
                'In function "mismatch", parameter $param type is type-hinted to "string" but the @param annotation says it is a "int". Please fix the @param annotation.',
                52,
            ],
            [
                'In function "mismatch", return type is type-hinted to "string" but the @return annotation says it is a "int". Please fix the @return annotation.',
                52,
            ],
            [
                'In function "test8", parameter $any_array type is "array". Please provide a more specific @param annotation in the docblock. For instance: @param int[] $any_array. Use @param mixed[] $any_array if this is really an array of mixed values.',
                70,
            ],
            [
                'In function "test8", return type is "array". Please provide a more specific @return annotation. For instance: @return int[]. Use @return mixed[] if this is really an array of mixed values.',
                70,
            ],
            [
                'In function "test10", for parameter $id, invalid docblock @param encountered. Attempted to resolve "" but it appears to be empty',
                86,
            ]
		]);
	}
}
