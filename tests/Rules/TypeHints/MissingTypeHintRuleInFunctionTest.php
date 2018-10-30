<?php declare(strict_types=1);

namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PHPStan\Testing\RuleTestCase;

class MissingTypeHintRuleInFunctionTest extends RuleTestCase
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
                'In function "test", parameter $no_type_hint has no type-hint and no @param annotation. More info: http://bit.ly/usetypehint',
                3,
            ],
            [
                'In function "test", there is no return type and no @return annotation. More info: http://bit.ly/usetypehint',
                3,
            ],
            [
                'In function "test2", parameter $type_hintable can be type-hinted to "?string". More info: http://bit.ly/usetypehint',
                11,
            ],
            [
                'In function "test2", a "string" return type can be added. More info: http://bit.ly/usetypehint',
                11,
            ],
            [
                'In function "test3", parameter $type_hintable can be type-hinted to "\DateTimeInterface". More info: http://bit.ly/usetypehint',
                19,
            ],
            [
                'In function "test3", a "\DateTimeInterface" return type can be added. More info: http://bit.ly/usetypehint',
                19,
            ],
            [
                'In function "test4", parameter $type_hintable can be type-hinted to "array". More info: http://bit.ly/usetypehint',
                27,
            ],
            [
                'In function "test4", a "array" return type can be added. More info: http://bit.ly/usetypehint',
                27,
            ],
            [
                'In function "test6", parameter $better_type_hint type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $better_type_hint. More info: http://bit.ly/typehintarray',
                38,
            ],
            [
                'In function "test6", return type is "array". Please provide a @return annotation to further specify the type of the array. For instance: @return int[]. More info: http://bit.ly/typehintarray',
                38,
            ],
            [
                'In function "mismatch", parameter $param type is type-hinted to "string|null" but the @param annotation says it is a "int". Please fix the @param annotation.',
                46,
            ],
            [
                'In function "mismatch", return type is type-hinted to "string" but the @return annotation says it is a "int". Please fix the @return annotation.',
                46,
            ],
            [
                'In function "test8", parameter $any_array type is "array". Please provide a more specific @param annotation in the docblock. For instance: @param int[] $any_array. Use @param mixed[] $any_array if this is really an array of mixed values. More info: http://bit.ly/typehintarray',
                62,
            ],
            [
                'In function "test8", return type is "array". Please provide a more specific @return annotation. For instance: @return int[]. Use @return mixed[] if this is really an array of mixed values. More info: http://bit.ly/typehintarray',
                62,
            ],
            [
                'In function "test10", parameter $id has no type-hint and no @param annotation. More info: http://bit.ly/usetypehint',
                76,
            ],
            [
                'In function "test13", parameter $type_hintable type is type-hinted to "ClassDoesNotExist" but the @param annotation says it is a "array<DateTimeImmutable>". Please fix the @param annotation.',
                97,
            ],
            [
                'In function "test15", parameter $foo type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $foo. More info: http://bit.ly/typehintarray',
                110,
            ],
            [
                'In function "test15", mismatching type-hints for return type. PHP type hint is "array" and docblock declared return type is a.',
                110,
            ],
            [
                'In function "test19", a "void" return type can be added. More info: http://bit.ly/usetypehint',
                139,
            ]

        ]);
    }
}
