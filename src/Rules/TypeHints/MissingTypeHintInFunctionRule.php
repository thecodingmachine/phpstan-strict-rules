<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PhpParser\Node;

class MissingTypeHintInFunctionRule extends AbstractMissingTypeHintRule
{
    public function getNodeType(): string
    {
        return Node\Stmt\Function_::class;
    }
}
