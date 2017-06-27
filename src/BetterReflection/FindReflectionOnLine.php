<?php

namespace TheCodingMachine\PHPStan\BetterReflection;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionFunction;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * TODO: remove this when https://github.com/Roave/BetterReflection/pull/286 is merged
 */
final class FindReflectionOnLine
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    public function __construct(SourceLocator $sourceLocator = null)
    {
        $this->sourceLocator = $sourceLocator;
    }

    /**
     * @return self
     */
    public static function buildDefaultFinder() : self
    {
        return new self(new AggregateSourceLocator([
            new PhpInternalSourceLocator(),
            new EvaledCodeSourceLocator(),
            new AutoloadSourceLocator(),
        ]));
    }

    /**
     * Find a reflection on the specified line number.
     *
     * Returns null if no reflections found on the line.
     *
     * @param string $filename
     * @param int $lineNumber
     * @return ReflectionMethod|ReflectionClass|ReflectionFunction|null
     * @throws \InvalidArgumentException
     */
    public function __invoke($filename, $lineNumber)
    {
        $lineNumber = (int)$lineNumber;
        $reflections = $this->computeReflections($filename);

        foreach ($reflections as $reflection) {
            if ($reflection instanceof ReflectionClass && $this->containsLine($reflection, $lineNumber)) {
                foreach ($reflection->getMethods() as $method) {
                    if ($this->containsLine($method, $lineNumber)) {
                        return $method;
                    }
                }
                return $reflection;
            }

            if ($reflection instanceof ReflectionFunction && $this->containsLine($reflection, $lineNumber)) {
                return $reflection;
            }
        }

        return null;
    }

    /**
     * Find all class and function reflections in the specified file
     *
     * @param string $filename
     * @return Reflection[]
     */
    private function computeReflections($filename)
    {
        $sourceLocator = new SingleFileSourceLocator($filename);
        if ($this->sourceLocator !== null) {
            $reflector = new ClassReflector(new AggregateSourceLocator([$this->sourceLocator, $sourceLocator]));
        } else {
            $reflector = new ClassReflector($sourceLocator);
        }

        return array_merge(
            $sourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)),
            $sourceLocator->locateIdentifiersByType($reflector, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
    }

    /**
     * Check to see if the line is within the boundaries of the reflection specified.
     *
     * @param mixed $reflection
     * @param int $lineNumber
     * @return bool
     * @throws \InvalidArgumentException
     */
    private function containsLine($reflection, $lineNumber)
    {
        if (!method_exists($reflection, 'getStartLine')) {
            throw new \InvalidArgumentException('Reflection does not have getStartLine method');
        }

        if (!method_exists($reflection, 'getEndLine')) {
            throw new \InvalidArgumentException('Reflection does not have getEndLine method');
        }

        return $lineNumber >= $reflection->getStartLine() && $lineNumber <= $reflection->getEndLine();
    }
}
