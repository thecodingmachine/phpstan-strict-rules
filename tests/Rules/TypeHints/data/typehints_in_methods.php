<?php

class Foo
{
    function test($no_type_hint): void
    {

    }
}

class Bar implements \TheCodingMachine\PHPStan\Rules\TypeHints\data\StubInterface
{

    public function foo($no_type_hint): void
    {
    }
}
