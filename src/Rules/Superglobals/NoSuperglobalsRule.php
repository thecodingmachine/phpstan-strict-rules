<?php


namespace TheCodingMachine\PHPStan\Rules\Superglobals;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use TheCodingMachine\PHPStan\Utils\PrefixGenerator;

/**
 * This rule checks that no superglobals are used in code.
 *
 * @implements Rule<Node\Expr\Variable>
 */
class NoSuperglobalsRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\Variable::class;
    }

    /**
     * @param Node\Expr\Variable $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $function = $scope->getFunction();
        // If we are at the top level (not in a function), let's ignore all this.
        // It might be ok.
        if ($function === null) {
            return [];
        }

        $forbiddenGlobals = [
            '_GET', '_POST', '_FILES', '_COOKIE', '_SESSION', '_REQUEST'
        ];

        if (\in_array($node->name, $forbiddenGlobals, true)) {
            return [PrefixGenerator::generatePrefix($scope).'you should not use the $'.$node->name.' superglobal. You should instead rely on your framework that provides you with a "request" object (for instance a PSR-7 RequestInterface or a Symfony Request). More info: http://bit.ly/nosuperglobals'];
        }

        return [];
    }
}
