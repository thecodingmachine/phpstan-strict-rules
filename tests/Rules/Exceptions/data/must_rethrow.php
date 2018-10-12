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
} catch (\Throwable $e) {
    // Do something
    $foo = 42;
}

function foo() {
    try {
    } catch (\RuntimeException $e) {
        // Do something
        $foo = 42;
    }
}

try {
} catch (\Exception $e) {
    // Do something
    throw $e;
}
