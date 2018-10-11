<?php

namespace TestThrowException;

class MyCatchException extends \Exception
{
}

function foo()
{
    throw new MyCatchException('');
}

function bar()
{
    throw new \Exception('');
}

function baz()
{
    try {
        //...
    } catch (\Exception $e) {
        // This is ok
        throw $e;
    }
}
