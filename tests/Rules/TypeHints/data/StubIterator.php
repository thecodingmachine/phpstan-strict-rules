<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints\data;


use ArrayIterator;

class StubIterator implements \IteratorAggregate
{
    public function getIterator() {
        return new ArrayIterator($this);
    }
}
