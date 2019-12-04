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
use TheCodingMachine\PHPStan\Utils\PrefixGenerator;
use Throwable;

/**
 * When catching \Exception, \RuntimeException or \Throwable, the exception MUST be thrown again
 * (unless you are developing an exception handler...)
 *
 * @implements Rule<Catch_>
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
        $exceptionType = null;
        foreach ($node->types as $type) {
            if (in_array((string)$type, [Exception::class, RuntimeException::class, Throwable::class], true)) {
                $exceptionType = (string)$type;
                break;
            }
        }

        if ($exceptionType === null) {
            return [];
        }

        // Let's visit and find a throw.
        $visitor = new class() extends NodeVisitorAbstract {
            /**
             * @var bool
             */
            private $throwFound = false;

            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Throw_) {
                    $this->throwFound = true;
                }
                return null;
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
            $errors[] = sprintf('%scaught "%s" must be rethrown. Either catch a more specific exception or add a "throw" clause in the "catch" block to propagate the exception. More info: http://bit.ly/failloud', PrefixGenerator::generatePrefix($scope), $exceptionType);
        }

        return $errors;
    }
}
