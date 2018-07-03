<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;


use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Php\PhpParameterReflection;

class FunctionDebugContext implements DebugContextInterface
{
    /**
     * @var FunctionLike
     */
    private $function;
    /**
     * @var Scope
     */
    private $scope;

    public function __construct(Scope $scope, FunctionLike $function)
    {

        $this->function = $function;
        $this->scope = $scope;
    }

    public function __toString()
    {
        if ($this->function instanceof ClassMethod) {
            if (!$this->scope->isInClass()) {
                return 'Should not happen';
            }

            return sprintf('In method "%s::%s",', $this->scope->getClassReflection()->getDisplayName(), $this->function->name->name);
        }
        elseif ($this->function instanceof Function_) {
            return sprintf('In function "%s",', $this->function->name->name);
        }
        return 'Should not happen';
    }
}