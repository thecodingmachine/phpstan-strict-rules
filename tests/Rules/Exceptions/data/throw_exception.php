<?php

namespace TestCatch;

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
