<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function strpos;

/**
 * @implements Rule<Catch_>
 */
class EmptyExceptionRule implements Rule
{
    public function getNodeType(): string
    {
        return Catch_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Catch_ $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return \PHPStan\Rules\RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($this->isEmpty($node->stmts)) {
            return [
                RuleErrorBuilder::message('Empty catch block.')
                    ->identifier('thecodingmachine.emptyCatch')
                    ->tip('If you are sure this is meant to be empty, please add a "// @ignoreException" comment in the catch block.')
                    ->build()
            ];
        }

        return [];
    }

    /**
     * @param Node[] $stmts
     * @return bool
     */
    private function isEmpty(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Nop) {
                return false;
            } else {
                foreach ($stmt->getComments() as $comment) {
                    if (strpos($comment->getText(), '@ignoreException') !== false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
