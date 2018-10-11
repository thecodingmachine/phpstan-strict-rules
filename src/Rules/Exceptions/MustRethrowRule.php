<?php


namespace TheCodingMachine\PHPStan\Rules\Exceptions;

use Exception;
use function in_array;
use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;
use RuntimeException;
use Throwable;

/**
 * When catching \Exception, \RuntimeException or \Throwable, the exception MUST be thrown again
 * (unless you are developing an exception handler...)
 */
class MustRethrowRule implements Rule
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
        // Let's only apply the filter to \Exception, \RuntimeException or \Throwable
        $elected = false;
        foreach ($node->types as $type) {
            if (in_array((string)$type, [Exception::class, RuntimeException::class, Throwable::class], true)) {
                $elected = true;
                break;
            }
        }

        if (!$elected) {
            return [];
        }

        // Let's visit and find a throw.
        $visitor = new class() extends NodeVisitorAbstract {
            private $throwFound = false;

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Throw_) {
                    $this->throwFound = true;
                }
            }

            /**
             * @return bool
             */
            public function isThrowFound(): bool
            {
                return $this->throwFound;
            }
        };

        $traverser = new NodeTraverser();

        $traverser->addVisitor($visitor);

        $traverser->traverse($node->stmts);

        $errors = [];

        if (!$visitor->isThrowFound()) {
            $errors[] = sprintf('Caught \Exception, \Throwable or \RuntimeException must be rethrown. Either catch a more specific exception or add a "throw" clause in the "catch" to propagate the exception.');
        }

        return $errors;
    }
}
