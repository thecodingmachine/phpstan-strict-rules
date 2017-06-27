<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionFunctionAbstract;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\ReflectionParameter;
use PhpParser\Node;

class MissingTypeHintInFunctionRule extends AbstractMissingTypeHintRule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Function_::class;
    }

    /**
     * @param ReflectionFunctionAbstract|ReflectionParameter $reflection
     * @return string
     */
    public function getContext($reflection): string
    {
        if ($reflection instanceof ReflectionParameter) {
            $reflection = $reflection->getDeclaringFunction();
        }
        return 'In function "'.$reflection->getName().'"';
    }
}
