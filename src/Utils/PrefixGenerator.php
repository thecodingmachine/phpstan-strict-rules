<?php


namespace TheCodingMachine\PHPStan\Utils;


use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;

class PrefixGenerator
{
    public static function generatePrefix(Scope $scope): string
    {
        $function = $scope->getFunction();
        $prefix = '';
        if ($function instanceof MethodReflection) {
            $prefix = 'In method "'.$function->getDeclaringClass()->getName().'::'.$function->getName().'", ';
        } elseif ($function instanceof FunctionReflection) {
            $prefix = 'In function "'.$function->getName().'", ';
        }

        return $prefix;
    }
}