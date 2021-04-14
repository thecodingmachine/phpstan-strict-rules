<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;

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
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $visitor = new class($node->var->name) extends NodeVisitorAbstract {
            /**
             * @var string
             */
            private $catchedVariableName;
            /**
             * @var int
             */
            private $exceptionUsedCount = 0;
            /**
             * @var Node\Stmt\Throw_[]
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

                if (PHP_VERSION_ID >= 80000 && is_null($this->catchedVariableName)) {
                    $this->exceptionUsedCount--;
                }

                if ($node instanceof Node\Stmt\Throw_ && $this->exceptionUsedCount === 0) {
                    $this->unusedThrows[] = $node;
                }
                return null;
            }

            /**
             * @return Node\Stmt\Throw_[]
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
            $errors[] = sprintf('Thrown exceptions in a catch block must bundle the previous exception (see throw statement line %d). More info: http://bit.ly/bundleexception', $throw->getLine());
        }

        return $errors;
    }
}
