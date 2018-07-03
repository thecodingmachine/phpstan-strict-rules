<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;


use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\PhpParameterReflection;

class ParameterDebugContext implements DebugContextInterface
{
    /**
     * @var FunctionLike
     */
    private $function;
    /**
     * @var PhpParameterReflection
     */
    private $parameter;
    /**
     * @var Scope
     */
    private $scope;

    public function __construct(Scope $scope, FunctionLike $function, PhpParameterReflection $parameter)
    {

        $this->function = $function;
        $this->parameter = $parameter;
        $this->scope = $scope;
    }

    public function __toString()
    {
        if ($this->function instanceof ClassMethod) {
            if (!$this->scope->isInClass()) {
                return 'Should not happen';
            }

            return sprintf('In method "%s::%s", parameter $%s', $this->scope->getClassReflection()->getDisplayName(), $this->function->name->name, $this->parameter->getName());
        }
        elseif ($this->function instanceof Function_) {
            return sprintf('In function "%s", parameter $%s', $this->function->name->name, $this->parameter->getName());
        }
        return 'Should not happen';
    }

    public function getName(): string
    {
        return $this->parameter->getName();
    }
}