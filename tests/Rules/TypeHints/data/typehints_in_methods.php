<?php

class Foo
{
    function test($no_type_hint): void
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
