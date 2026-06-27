<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * When throwing into a catch block, checks that the previous exception is passed to the new "throw" clause
 * (the initial stack trace must not be lost).
 *
 * @implements Rule<Catch_>
 */
class ThrowMustBundlePreviousExceptionRule implements Rule
{
    public function getNodeType(): string
    {
        return Catch_::class;
    }

    /**
     * @param Catch_ $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return \PHPStan\Rules\RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->var === null) {
            return [];
        }

        $catchedVariableName = is_string($node->var->name) ? $node->var->name : null;

        $visitor = new class($catchedVariableName) extends NodeVisitorAbstract {
            /**
             * @var string|null
             */
            private $catchedVariableName;
            /**
             * @var int
             */
            private $exceptionUsedCount = 0;
            /**
             * @var Node\Expr\Throw_[]
             */
            private $unusedThrows = [];

            public function __construct(?string $catchedVariableName)
            {
                $this->catchedVariableName = $catchedVariableName;
            }

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Expr\Variable) {
                    if ($node->name === $this->catchedVariableName) {
                        $this->exceptionUsedCount++;
                    }
                    return null;
                }

                // If the variable is used in the context of a method call (like $e->getMessage()), the exception is not passed as a "previous exception".
                if ($node instanceof Node\Expr\MethodCall) {
                    if ($node->var instanceof Node\Expr\Variable && $node->var->name === $this->catchedVariableName) {
                        $this->exceptionUsedCount--;
                    }
                }

                if ($node instanceof Node\Expr\Throw_ && $this->exceptionUsedCount === 0) {
                    $this->unusedThrows[] = $node;
                }
                return null;
            }

            /**
             * @return Node\Expr\Throw_[]
             */
            public function getUnusedThrows(): array
            {
                return $this->unusedThrows;
            }
        };

        $traverser = new NodeTraverser();

        $traverser->addVisitor($visitor);

        $traverser->traverse($node->stmts);

        $errors = [];

        foreach ($visitor->getUnusedThrows() as $throw) {
            $errors[] = RuleErrorBuilder::message(sprintf('Thrown exceptions in a catch block must bundle the previous exception (see throw statement line %d).', $throw->getLine()))
                ->identifier('thecodingmachine.previousExceptionNotBundled')
                ->tip('More info: http://bit.ly/bundleexception')
                ->build();
        }

        return $errors;
    }
}
