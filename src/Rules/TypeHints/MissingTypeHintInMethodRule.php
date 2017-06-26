<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PhpParser\Node;

class MissingTypeHintInMethodRule extends AbstractMissingTypeHintRule
{
    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }
}
