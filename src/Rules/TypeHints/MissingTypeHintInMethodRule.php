<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpMethodReflection;
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
     * @param Node\Stmt\ClassMethod $node
     * @return bool
     */
    public function isReturnIgnored(Node $node): bool
    {
        return isset(self::RETURN_BLACKLIST[$node->name->name]);
    }

    protected function getReflection(Node\FunctionLike $function, Scope $scope, Broker $broker) : ParametersAcceptorWithPhpDocs
    {
        if (!$scope->isInClass()) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        $nativeMethod = $scope->getClassReflection()->getNativeMethod($function->name->name);
        if (!$nativeMethod instanceof PhpMethodReflection) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        /** @var \PHPStan\Reflection\ParametersAcceptorWithPhpDocs $parametersAcceptor */
        return ParametersAcceptorSelector::selectSingle($nativeMethod->getVariants());
    }

    protected function shouldSkip(Node\FunctionLike $function, Scope $scope): bool
    {
        // We should skip if the method is inherited!
        if (!$scope->isInClass()) {
            throw new \PHPStan\ShouldNotHappenException();
        }
        $nativeMethod = $scope->getClassReflection()->getNativeMethod($function->name->name);

        return $this->isInherited2($nativeMethod, $scope->getClassReflection());

    }

    private function isInherited2(MethodReflection $method, ClassReflection $class = null): bool
    {
        if ($class === null) {
            $class = $method->getDeclaringClass();
        }
        $interfaces = $class->getInterfaces();
        foreach ($interfaces as $interface) {
            if ($interface->hasMethod($method->getName())) {
                return true;
            }
        }

        $parentClass = $class->getParentClass();
        if ($parentClass !== false) {
            if ($parentClass->hasMethod($method->getName())) {
                return true;
            }
            return $this->isInherited2($method, $parentClass);
        }

        return false;
    }
}
