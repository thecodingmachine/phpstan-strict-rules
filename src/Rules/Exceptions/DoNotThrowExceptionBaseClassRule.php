<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;


use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;

/**
 * This rule checks that the base \Exception class is never thrown. Instead, developers should subclass the \Exception
 * base class and throw the sub-type.
 */
class DoNotThrowExceptionBaseClassRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Throw_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Throw_ $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {

        $type = $scope->getType($node->expr);

        $class = $type->getClass();

        if ($class === 'Exception') {
            return [
                'Do not throw the \Exception base class. Instead, extend the \Exception base class'
            ];
        }

        return [];
    }
}
