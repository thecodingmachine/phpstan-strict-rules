<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionFunctionAbstract;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use PhpParser\Node;
use PHPStan\Reflection\MethodReflection;

class MissingTypeHintInMethodRule extends AbstractMissingTypeHintRule
{
    private const RETURN_BLACKLIST = [
        '__construct' => true,
        '__destruct' => true,
        '__call' => true,
        '__callStatic' => true,
        '__get' => true,
        '__set' => true,
        '__isset' => true,
        '__unset' => true,
        '__sleep'  => true,
        '__wakeup'  => true,
        '__toString'  => true,
        '__invoke'  => true,
        '__set_state' => true,
        '__clone' => true,
        '__debugInfo' => true
    ];

    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
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
        return 'In method "'.$reflection->getDeclaringClass()->getName().'::'.$reflection->getName().'"';
    }

    /**
     * @param Node\Stmt\ClassMethod $node
     * @return bool
     */
    public function isReturnIgnored(Node $node): bool
    {
        return isset(self::RETURN_BLACKLIST[$node->name->name]);
    }
}
