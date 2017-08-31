<?php
declare(strict_types=1);

namespace TheCodingMachine\PHPStan\Rules\TypeHints;


use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Callable_;
use phpDocumentor\Reflection\Types\Float_;
use phpDocumentor\Reflection\Types\Integer;
use phpDocumentor\Reflection\Types\Mixed_;
use phpDocumentor\Reflection\Types\Null_;
use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Scalar;
use phpDocumentor\Reflection\Types\String_;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Rules\Rule;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflection\ReflectionParameter;
use Roave\BetterReflection\Util\FindReflectionOnLine;

abstract class AbstractMissingTypeHintRule implements Rule
{

    /**
     * @var Broker
     */
    private $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    abstract public function getNodeType(): string;

    /**
     * @param ReflectionMethod|ReflectionFunction $reflection
     * @return string
     */
    abstract public function getContext($reflection): string;

    abstract public function isReturnIgnored(Node $node): bool;

    /**
     * @param \PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\ClassMethod $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        // TODO: improve performance by caching better reflection results.
        $finder = FindReflectionOnLine::buildDefaultFinder();

        if ($node->getLine() < 0) {
            // Fixes some problems with methods in anonymous class (the line number is poorly reported).
            return [];
        }

        $reflection = $finder($scope->getFile(), $node->getLine());

        // If the method implements/extends another method, we have no choice on the signature so let's bypass this check.
        if ($reflection instanceof ReflectionMethod && $this->isInherited($reflection)) {
            return [];
        }

        $errors = [];

        if ($reflection === null) {
            throw new \RuntimeException('Could not find item at '.$scope->getFile().':'.$node->getLine());
        }

        foreach ($reflection->getParameters() as $parameter) {
            $result = $this->analyzeParameter($parameter);

            if ($result !== null) {
                $errors[] = $result;
            }
        }

        if (!$this->isReturnIgnored($node)) {
            $returnTypeError = $this->analyzeReturnType($reflection);
            if ($returnTypeError !== null) {
                $errors[] = $returnTypeError;
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
        $typeResolver = new \phpDocumentor\Reflection\TypeResolver();

        $phpTypeHint = $parameter->getType();
        $docBlockTypeHints = $parameter->getDocBlockTypes();

        // If there is a type-hint, we have nothing to say unless it is an array.
        if ($phpTypeHint !== null) {
            $phpdocTypeHint = $typeResolver->resolve((string) $phpTypeHint);

            return $this->analyzeWithTypehint($parameter, $phpdocTypeHint, $docBlockTypeHints);
        } else {
            return $this->analyzeWithoutTypehint($parameter, $docBlockTypeHints);
        }
    }

    /**
     * @param ReflectionFunction|ReflectionMethod $function
     * @return null|string
     */
    private function analyzeReturnType($function): ?string
    {
        $reflectionPhpTypeHint = $function->getReturnType();
        $phpTypeHint = null;
        if ($reflectionPhpTypeHint !== null) {
            $typeResolver = new \phpDocumentor\Reflection\TypeResolver();
            $phpTypeHint = $typeResolver->resolve((string) $reflectionPhpTypeHint);
        }
        $docBlockTypeHints = $function->getDocBlockReturnTypes();

        // If there is a type-hint, we have nothing to say unless it is an array.
        if ($phpTypeHint !== null) {
            return $this->analyzeWithTypehint($function, $phpTypeHint, $docBlockTypeHints);
        } else {
            return $this->analyzeWithoutTypehint($function, $docBlockTypeHints);
        }
    }

    /**
     * @param ReflectionParameter|ReflectionMethod|ReflectionFunction $context
     * @param Type $phpTypeHint
     * @param Type[] $docBlockTypeHints
     * @return null|string
     */
    private function analyzeWithTypehint($context, Type $phpTypeHint, array $docBlockTypeHints): ?string
    {
        $docblockWithoutNullable = $this->typesWithoutNullable($docBlockTypeHints);

        if (!$phpTypeHint instanceof Array_) {
            // Let's detect mismatches between docblock and PHP typehint
            foreach ($docblockWithoutNullable as $docblockTypehint) {
                if (get_class($docblockTypehint) !== get_class($phpTypeHint)) {
                    if ($context instanceof ReflectionParameter) {
                        return sprintf('%s, parameter $%s type is type-hinted to "%s" but the @param annotation says it is a "%s". Please fix the @param annotation.', $this->getContext($context), $context->getName(), (string) $phpTypeHint, (string) $docblockTypehint);
                    } else {
                        return sprintf('%s, return type is type-hinted to "%s" but the @return annotation says it is a "%s". Please fix the @return annotation.', $this->getContext($context), (string) $phpTypeHint, (string) $docblockTypehint);
                    }
                }
            }

            return null;
        }

        if (empty($docblockWithoutNullable)) {
            if ($context instanceof ReflectionParameter) {
                return sprintf('%s, parameter $%s type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $%s', $this->getContext($context), $context->getName(), $context->getName());
            } else {
                return sprintf('%s, return type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @return int[]', $this->getContext($context));
            }
        } else {
            foreach ($docblockWithoutNullable as $docblockTypehint) {
                if (!$docblockTypehint instanceof Array_) {
                    if ($context instanceof ReflectionParameter) {
                        return sprintf('%s, mismatching type-hints for parameter %s. PHP type hint is "array" and docblock type hint is %s.', $this->getContext($context), $context->getName(), (string)$docblockTypehint);
                    } else {
                        return sprintf('%s, mismatching type-hints for return type. PHP type hint is "array" and docblock declared return type is %s.', $this->getContext($context), (string)$docblockTypehint);
                    }
                }

                if ($docblockTypehint->getValueType() instanceof Mixed_) {
                    if ($context instanceof ReflectionParameter) {
                        return sprintf('%s, parameter $%s type is "array". Please provide a more specific @param annotation. For instance: @param int[] $%s', $this->getContext($context), $context->getName(), $context->getName());
                    } else {
                        return sprintf('%s, return type is "array". Please provide a more specific @return annotation. For instance: @return int[]', $this->getContext($context));
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param ReflectionParameter|ReflectionMethod|ReflectionFunction $context
     * @param Type[] $docBlockTypeHints
     * @return null|string
     */
    private function analyzeWithoutTypehint($context, array $docBlockTypeHints): ?string
    {
        if (empty($docBlockTypeHints)) {
            if ($context instanceof ReflectionParameter) {
                return sprintf('%s, parameter $%s has no type-hint and no @param annotation.', $this->getContext($context), $context->getName());
            } else {
                return sprintf('%s, there is no return type and no @return annotation.', $this->getContext($context));
            }
        }

        $nativeTypehint = $this->isNativelyTypehintable($docBlockTypeHints);

        if ($nativeTypehint !== null) {
            if ($context instanceof ReflectionParameter) {
                return sprintf('%s, parameter $%s can be type-hinted to "%s".', $this->getContext($context), $context->getName(), $nativeTypehint);
            } else {
                return sprintf('%s, a "%s" return type can be added.', $this->getContext($context), $nativeTypehint);
            }
        }

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
     * @return Type[]
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
            if ($parentClass->hasMethod($method->getName())) {
                return true;
            }
            return $this->isInherited($method, $parentClass);
        }

        return false;
    }
}
