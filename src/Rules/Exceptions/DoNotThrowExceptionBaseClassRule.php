<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * This rule checks that the base \Exception class is never thrown. Instead, developers should subclass the \Exception
 * base class and throw the sub-type.
 *
 * @implements Rule<Node\Expr\Throw_>
 */
class DoNotThrowExceptionBaseClassRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\Throw_::class;
    }

    /**
     * @param \PhpParser\Node\Expr\Throw_ $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return \PHPStan\Rules\RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->expr instanceof Node\Expr\New_) {
            // Only catch "throw new ..."
            return [];
        }

        $type = $scope->getType($node->expr);

        if (\in_array('Exception', $type->getObjectClassNames(), true)) {
            return [
                RuleErrorBuilder::message('Do not throw the \Exception base class.')
                    ->identifier('thecodingmachine.exceptionBaseClassThrown')
                    ->tip('Instead, extend the \Exception base class. More info: http://bit.ly/subtypeexception')
                    ->build()
            ];
        }

        return [];
    }
}
