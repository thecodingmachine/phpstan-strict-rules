<?php

namespace TestThrowMustBundlePreviousException;

class FooCatch
{
}

class MyCatchException extends \Exception
{
}

try {
} catch (\Exception $e) {
    // This is okay
    throw $e;
}

try {
} catch (\Exception $e) {
    // This is okay
    throw new \Exception('New exception', 0, $e);
}

try {
} catch (\Exception $e) {
    // This is not okay
    throw new \Exception('New exception '.$e->getMessage());
}

try {
}catch (\Exception) {
    // This is okay
    throw new \Exception();
}