<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
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

    public function isReturnIgnored(Node $node): bool
    {
        return false;
    }
}
