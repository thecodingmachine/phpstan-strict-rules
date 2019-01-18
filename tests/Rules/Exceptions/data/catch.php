<?php

namespace TestCatch;

class FooCatch
{
}

class MyCatchException extends \Exception
{
}

try {
} catch (\TestCatch\MyCatchException $e) {
}

try {
} catch (\Exception $e) {
    // Do something
    $foo = 42;
}

try {
} catch (\Exception $e) {
    // Do nothing
}

try {
} catch (\Exception $e) {
    // @ignoreException
}
