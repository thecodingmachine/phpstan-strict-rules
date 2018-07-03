<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpParameterReflection;
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

    public function isReturnIgnored(Node $node): bool
    {
        return false;
    }

    protected function getReflection(Node\FunctionLike $function, Scope $scope, Broker $broker) : ParametersAcceptorWithPhpDocs
    {
        $functionName = $function->name->name;
        if (isset($function->namespacedName)) {
            $functionName = (string) $function->namespacedName;
        }
        $functionNameName = new Node\Name($functionName);
        if (!$broker->hasCustomFunction($functionNameName, null)) {
            throw new \RuntimeException("Cannot find function '$functionName'");
        }
        $functionReflection = $broker->getCustomFunction($functionNameName, null);
        /** @var \PHPStan\Reflection\ParametersAcceptorWithPhpDocs $parametersAcceptor */
        return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants());
    }

    protected function shouldSkip(Node\FunctionLike $function, Scope $scope): bool
    {
        return false;
    }
}
