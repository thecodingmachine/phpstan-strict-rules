<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints\data;


class Foo
{
    public function test($no_type_hint): void
    {
    }
}

class Bar implements \TheCodingMachine\PHPStan\Rules\TypeHints\data\StubInterface
{
    // Constructor do not need return statements
    public function __construct()
    {
    }

    public function foo($no_type_hint): void
    {
    }
}

class BazClass extends \TheCodingMachine\PHPStan\Rules\TypeHints\data\StubClass
{
    // Extended function
    public function baz($no_type_hint)
    {
    }

    public function bazbaz($no_type_hint)
    {
    }

    public function notInherited($no_type_hint): void
    {
    }
}
