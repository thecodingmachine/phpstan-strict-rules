<?php


namespace TheCodingMachine\PHPStan\Rules\TypeHints;


use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Reflection\ReflectionMethod;
use BetterReflection\Reflection\ReflectionParameter;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use TheCodingMachine\PHPStan\BetterReflection\FindReflectionOnLine;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Mixed;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Scalar;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;

class AbstractMissingTypeHintRule implements Rule
{

    /**
     * @var Broker
     */
    private $broker;

    public function __construct(Broker $broker)
    {

        $this->broker = $broker;
    }

    public function getNodeType(): string
    {
        // FIXME: does this encompass the Method_?
        return Node\Stmt\Function_::class;
    }

    /**
     * @param \PhpParser\Node\Stmt\Function_ $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // TODO: improve performance by caching better reflection results.
        $finder = FindReflectionOnLine::buildDefaultFinder();

        $reflection = $finder($scope->getFile(), $node->getLine());

        // If the method implements/extends another method, we have no choice on the signature so let's bypass this check.
        if ($reflection instanceof ReflectionMethod && $this->isInherited($reflection)) {
            return [];
        }

        $errors = [];

        foreach ($reflection->getParameters() as $parameter) {
            $result = $this->analyzeParameter($parameter);

            if ($result !== null) {
                $errors[] = $result;
            }
        }

        return $errors;
    }

    /**
     * Analyzes a parameter and returns the error string if xomething goes wrong or null if everything is ok.
     *
     * @param ReflectionParameter $parameter
     * @return null|string
     */
    private function analyzeParameter(ReflectionParameter $parameter): ?string
    {
        $phpTypeHint = $parameter->getTypeHint();
        $docBlockTypeHints = $parameter->getDocBlockTypes();

        // If there is a type-hint, we have nothing to say unless it is an array.
        if ($phpTypeHint !== null) {
            return $this->analyzeParameterWithTypehint($parameter, $phpTypeHint, $docBlockTypeHints);
        } else {
            return $this->analyzeParameterWithoutTypehint($parameter, $docBlockTypeHints);
        }
    }

    /**
     * @param Type[] $docBlockTypeHints
     */
    private function analyzeParameterWithTypehint(ReflectionParameter $parameter, Type $phpTypeHint, array $docBlockTypeHints): ?string
    {
        $docblockWithoutNullable = $this->typesWithoutNullable($docBlockTypeHints);

        if (!$phpTypeHint instanceof Array_) {
            // Let's detect mismatches between docblock and PHP typehint
            foreach ($docblockWithoutNullable as $docblockTypehint) {
                if (get_class($docblockTypehint) !== get_class($phpTypeHint)) {
                    return sprintf('Parameter $%s type is type-hinted to "%s" but the @param annotation says it is a "%s". Please fix the @param annotation.', $parameter->getName(), (string) $phpTypeHint, (string) $docblockTypehint);
                }
            }

            return null;
        }

        if (empty($docblockWithoutNullable)) {
            return sprintf('Parameter $%s type is "array". Please provide a @param annotation to further speciy the type of the array. For instance: @param int[] $%s', $parameter->getName(), $parameter->getName());
        } else {
            foreach ($docblockWithoutNullable as $docblockTypehint) {
                if (!$docblockTypehint instanceof Array_) {
                    return sprintf('Mismatching type-hints for parameter %s. PHP type hint is "array" and docblock type hint is %s.', $parameter->getName(), (string) $docblockTypehint);
                }

                if ($docblockTypehint->getValueType() instanceof Mixed) {
                    return sprintf('Parameter $%s type is "array". Please provide a more specific @param annotation. For instance: @param int[] $%s', $parameter->getName(), $parameter->getName());
                }
            }
        }

        return null;
    }

    /**
     * @param Type[] $docBlockTypeHints
     * @return null|string
     */
    private function analyzeParameterWithoutTypehint(ReflectionParameter $parameter, array $docBlockTypeHints): ?string
    {
        if (empty($docBlockTypeHints)) {
            return sprintf('Parameter $%s has no type-hint and no @param annotation.', $parameter->getName());
        }

        $nativeTypehint = $this->isNativelyTypehintable($docBlockTypeHints);

        if ($nativeTypehint !== null) {
            return sprintf('Parameter $%s can be type-hinted to "%s".', $parameter->getName(), $nativeTypehint);
        }

        // TODO: return types

        return null;
    }

    /**
     * @param Type[] $docBlockTypeHints
     * @return string|null
     */
    private function isNativelyTypehintable(array $docBlockTypeHints): ?string
    {
        if (count($docBlockTypeHints) > 2) {
            return null;
        }
        $isNullable = $this->isNullable($docBlockTypeHints);
        if (count($docBlockTypeHints) === 2 && !$isNullable) {
            return null;
        }

        $types = $this->typesWithoutNullable($docBlockTypeHints);
        // At this point, there is at most one element here
        if (empty($types)) {
            return null;
        }

        $type = $types[0];

        if ($this->isNativeType($type)) {
            return ($isNullable?'?':'').((string)$type);
        }

        if ($type instanceof Array_) {
            return ($isNullable?'?':'').'array';
        }

        // TODO: more definitions to add here
        // Manage interface/classes
        // Manage array of things => (cast to array)

        if ($type instanceof Object_) {
            return ($isNullable?'?':'').((string)$type);
        }

        return null;
    }

    private function isNativeType(Type $type): bool
    {
        if ($type instanceof String_
            || $type instanceof Integer
            || $type instanceof Boolean
            || $type instanceof Float_
            || $type instanceof Scalar
            || $type instanceof Callable_
            || ((string) $type) === 'iterable'
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param Type[] $docBlockTypeHints
     * @return bool
     */
    private function isNullable(array $docBlockTypeHints): bool
    {
        foreach ($docBlockTypeHints as $docBlockTypeHint) {
            if ($docBlockTypeHint instanceof Null_) {
                return true;
            }
        }
        return false;
    }

    /**
     * Removes "null" from the list of types.
     *
     * @param Type[] $docBlockTypeHints
     * @return array
     */
    private function typesWithoutNullable(array $docBlockTypeHints): array
    {
        return array_filter($docBlockTypeHints, function($item) {
            return !$item instanceof Null_;
        });
    }

    private function isInherited(ReflectionMethod $method, ReflectionClass $class = null): bool
    {
        if ($class === null) {
            $class = $method->getDeclaringClass();
        }
        $interfaces = $class->getInterfaces();
        foreach ($interfaces as $interface) {
            if ($interface->hasMethod($method->getName())) {
                return true;
            }
        }

        $parentClass = $class->getParentClass();
        if ($parentClass !== null) {
            return $this->isInherited($method, $parentClass);
        }

        return false;
    }
}
