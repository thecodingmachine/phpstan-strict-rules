<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints\data;

class ParentStubClass
{
    public function bazbaz($no_type_hint)
    {
    }
}


class StubClass extends ParentStubClass
{
    public function baz($no_type_hint)
    {
    }
}
