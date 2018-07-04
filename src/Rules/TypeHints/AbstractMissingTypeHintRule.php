<?php
declare(strict_types=1);

namespace TheCodingMachine\PHPStan\Rules\TypeHints;

use PHPStan\Reflection\Php\PhpFunctionReflection;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\PhpParameterReflection;
use PHPStan\Rules\Rule;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

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

    abstract public function isReturnIgnored(Node $node): bool;

    abstract protected function getReflection(Node\FunctionLike $function, Scope $scope, Broker $broker) : ParametersAcceptorWithPhpDocs;

    abstract protected function shouldSkip(Node\FunctionLike $function, Scope $scope): bool;

    /**
     * @param \PhpParser\Node\Stmt\Function_|\PhpParser\Node\Stmt\ClassMethod $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /*if ($node->getLine() < 0) {
            // Fixes some problems with methods in anonymous class (the line number is poorly reported).
            return [];
        }*/

        if ($this->shouldSkip($node, $scope)) {
            return [];
        }

        $parametersAcceptor = $this->getReflection($node, $scope, $this->broker);

        $errors = [];

        foreach ($parametersAcceptor->getParameters() as $parameter) {
            $debugContext = new ParameterDebugContext($scope, $node, $parameter);
            $result = $this->analyzeParameter($debugContext, $parameter);

            if ($result !== null) {
                $errors[] = $result;
            }
        }

        if (!$this->isReturnIgnored($node)) {
            $debugContext = new FunctionDebugContext($scope, $node);
            $returnTypeError = $this->analyzeReturnType($debugContext, $parametersAcceptor);
            if ($returnTypeError !== null) {
                $errors[] = $returnTypeError;
            }
        }

        return $errors;
    }

    /**
     * Analyzes a parameter and returns the error string if something goes wrong or null if everything is ok.
     *
     * @param PhpParameterReflection $parameter
     * @return null|string
     */
    private function analyzeParameter(DebugContextInterface $context, PhpParameterReflection $parameter): ?string
    {
        //$typeResolver = new \phpDocumentor\Reflection\TypeResolver();

        $phpTypeHint = $parameter->getNativeType();
        //try {
            $docBlockTypeHints = $parameter->getPhpDocType();
        /*} catch (\InvalidArgumentException $e) {
            return sprintf('%s, for parameter $%s, invalid docblock @param encountered. %s',
                $this->getContext($parameter),
                $parameter->getName(),
                $e->getMessage()
            );
        }*/

        if ($phpTypeHint instanceof MixedType && $phpTypeHint->isExplicitMixed() === false) {
            return $this->analyzeWithoutTypehint($context, $docBlockTypeHints);
        } else {
            // If there is a type-hint, we have nothing to say unless it is an array.
            if ($parameter->isVariadic()) {
                // Hack: wrap the native type in an array is variadic
                $phpTypeHint = new ArrayType(new IntegerType(), $phpTypeHint);
            }

            return $this->analyzeWithTypehint($context, $phpTypeHint, $docBlockTypeHints);
        }
    }

    /**
     * @return null|string
     */
    private function analyzeReturnType(DebugContextInterface $debugContext, ParametersAcceptorWithPhpDocs $function): ?string
    {
        $phpTypeHint = $function->getNativeReturnType();
        $docBlockTypeHints = $function->getPhpDocReturnType();

        // If there is a type-hint, we have nothing to say unless it is an array.
        if ($phpTypeHint instanceof MixedType && $phpTypeHint->isExplicitMixed() === false) {
            return $this->analyzeWithoutTypehint($debugContext, $docBlockTypeHints);
        } else {
            return $this->analyzeWithTypehint($debugContext, $phpTypeHint, $docBlockTypeHints);
        }
    }

    /**
     * @param DebugContextInterface $debugContext
     * @param Type $phpTypeHint
     * @param Type $docBlockTypeHints
     * @return null|string
     */
    private function analyzeWithTypehint(DebugContextInterface $debugContext, Type $phpTypeHint, Type $docBlockTypeHints): ?string
    {
        $docblockWithoutNullable = $this->typesWithoutNullable($docBlockTypeHints);

        if (!$this->isTypeIterable($phpTypeHint)) {
            // FIXME: this should be handled with the "accepts" method of types (and actually, this is already triggered by PHPStan 0.10)

            if ($docBlockTypeHints instanceof MixedType && $docBlockTypeHints->isExplicitMixed() === false) {
                // No docblock.
                return null;
            }

            // Let's detect mismatches between docblock and PHP typehint
            if ($docblockWithoutNullable instanceof UnionType) {
                $docblocks = $docblockWithoutNullable->getTypes();
            } else {
                $docblocks = [$docblockWithoutNullable];
            }
            $phpTypeHintWithoutNullable = $this->typesWithoutNullable($phpTypeHint);
            foreach ($docblocks as $docblockTypehint) {
                if (get_class($docblockTypehint) !== get_class($phpTypeHintWithoutNullable)) {
                    if ($debugContext instanceof ParameterDebugContext) {
                        return sprintf('%s type is type-hinted to "%s" but the @param annotation says it is a "%s". Please fix the @param annotation.', (string) $debugContext, $phpTypeHint->describe(VerbosityLevel::typeOnly()), $docblockTypehint->describe(VerbosityLevel::typeOnly()));
                    } elseif (!$docblockTypehint instanceof MixedType || $docblockTypehint->isExplicitMixed()) {
                        return sprintf('%s return type is type-hinted to "%s" but the @return annotation says it is a "%s". Please fix the @return annotation.', (string) $debugContext, $phpTypeHint->describe(VerbosityLevel::typeOnly()), $docblockTypehint->describe(VerbosityLevel::typeOnly()));
                    }
                }
            }

            return null;
        }

        if ($phpTypeHint instanceof ArrayType) {
            if ($docblockWithoutNullable instanceof MixedType && !$docblockWithoutNullable->isExplicitMixed()) {
                if ($debugContext instanceof ParameterDebugContext) {
                    return sprintf('%s type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $%s', (string) $debugContext, $debugContext->getName());
                } else {
                    return sprintf('%s return type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @return int[]', (string) $debugContext);
                }
            } else {
                if ($docblockWithoutNullable instanceof UnionType) {
                    $docblocks = $docblockWithoutNullable->getTypes();
                } else {
                    $docblocks = [$docblockWithoutNullable];
                }
                foreach ($docblocks as $docblockTypehint) {
                    if (!$this->isTypeIterable($docblockTypehint)) {
                        if ($debugContext instanceof ParameterDebugContext) {
                            return sprintf('%s mismatching type-hints for parameter %s. PHP type hint is "array" and docblock type hint is %s.', (string) $debugContext, $debugContext->getName(), $docblockTypehint->describe(VerbosityLevel::typeOnly()));
                        } else {
                            return sprintf('%s mismatching type-hints for return type. PHP type hint is "array" and docblock declared return type is %s.', (string) $debugContext, $docblockTypehint->describe(VerbosityLevel::typeOnly()));
                        }
                    }

                    if ($docblockTypehint instanceof ArrayType && $docblockTypehint->getKeyType() instanceof MixedType && $docblockTypehint->getItemType() instanceof MixedType && $docblockTypehint->getKeyType()->isExplicitMixed() && $docblockTypehint->getItemType()->isExplicitMixed()) {
                        if ($debugContext instanceof ParameterDebugContext) {
                            return sprintf('%s type is "array". Please provide a more specific @param annotation in the docblock. For instance: @param int[] $%s. Use @param mixed[] $%s if this is really an array of mixed values.', (string) $debugContext, $debugContext->getName(), $debugContext->getName());
                        } else {
                            return sprintf('%s return type is "array". Please provide a more specific @return annotation. For instance: @return int[]. Use @return mixed[] if this is really an array of mixed values.', (string) $debugContext);
                        }
                    }
                }
            }
        }

        return null;
    }

    private function isTypeIterable(Type $phpTypeHint) : bool
    {
        return /*$phpTypeHint->isIterable()->maybe() ||*/ $phpTypeHint->isIterable()->yes();
        /*if ($phpTypeHint instanceof Array_ || $phpTypeHint instanceof Iterable_) {
            return true;
        }
        if ($phpTypeHint instanceof Object_) {
            // TODO: cache BetterReflection for better performance!
            try {
                $class = (new BetterReflection())->classReflector()->reflect((string) $phpTypeHint);
            } catch (IdentifierNotFound $e) {
                // Class not found? Let's not throw an error. It will be caught by other rules anyway.
                return false;
            }
            if ($class->implementsInterface('Traversable')) {
                return true;
            }
        }

        return false;*/
    }

    /**
     * @param DebugContextInterface $debugContext
     * @param Type $docBlockTypeHints
     * @return null|string
     */
    private function analyzeWithoutTypehint(DebugContextInterface $debugContext, Type $docBlockTypeHints): ?string
    {
        if ($docBlockTypeHints instanceof MixedType && $docBlockTypeHints->isExplicitMixed() === false) {
            if ($debugContext instanceof ParameterDebugContext) {
                return sprintf('%s has no type-hint and no @param annotation.', (string) $debugContext);
            } else {
                return sprintf('%s there is no return type and no @return annotation.', (string) $debugContext);
            }
        }

        $nativeTypehint = $this->isNativelyTypehintable($docBlockTypeHints);

        if ($nativeTypehint !== null) {
            if ($debugContext instanceof ParameterDebugContext) {
                return sprintf('%s can be type-hinted to "%s".', (string) $debugContext, $nativeTypehint);
            } else {
                return sprintf('%s a "%s" return type can be added.', (string) $debugContext, $nativeTypehint);
            }
        }

        return null;
    }

    /**
     * @param Type $docBlockTypeHints
     * @return string|null
     */
    private function isNativelyTypehintable(Type $docBlockTypeHints): ?string
    {
        if ($docBlockTypeHints instanceof UnionType) {
            $count = count($docBlockTypeHints->getTypes());
        } else {
            $count = 1;
        }

        if ($count > 2) {
            return null;
        }
        $isNullable = $this->isNullable($docBlockTypeHints);
        if ($count === 2 && !$isNullable) {
            return null;
        }

        $type = $this->typesWithoutNullable($docBlockTypeHints);
        // At this point, there is at most one element here
        /*if (empty($type)) {
            return null;
        }*/

        //$type = $types[0];

        // "object" type-hint is not available in PHP 7.1
        if ($type instanceof ObjectWithoutClassType) {
            // In PHP 7.2, this is true but not in PHP 7.1
            return null;
        }

        if ($type instanceof ObjectType) {
            return ($isNullable?'?':'').'\\'.$type->describe(VerbosityLevel::typeOnly());
        }

        if ($type instanceof ArrayType) {
            return ($isNullable?'?':'').'array';
        }

        if ($this->isNativeType($type)) {
            return ($isNullable?'?':'').$type->describe(VerbosityLevel::typeOnly());
        }

        // TODO: more definitions to add here
        // Manage interface/classes
        // Manage array of things => (cast to array)


        return null;
    }

    private function isNativeType(Type $type): bool
    {
        if ($type instanceof StringType
            || $type instanceof IntegerType
            || $type instanceof BooleanType
            || $type instanceof FloatType
            || $type instanceof CallableType
            || $type instanceof IterableType
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param Type $docBlockTypeHints
     * @return bool
     */
    private function isNullable(Type $docBlockTypeHints): bool
    {
        if ($docBlockTypeHints instanceof UnionType) {
            foreach ($docBlockTypeHints->getTypes() as $docBlockTypeHint) {
                if ($docBlockTypeHint instanceof NullType) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Removes "null" from the list of types.
     *
     * @param Type $docBlockTypeHints
     * @return Type
     */
    private function typesWithoutNullable(Type $docBlockTypeHints): Type
    {
        if ($docBlockTypeHints instanceof UnionType) {
            $filteredTypes = array_values(array_filter($docBlockTypeHints->getTypes(), function (Type $item) {
                return !$item instanceof NullType;
            }));
            if (\count($filteredTypes) === 1) {
                return $filteredTypes[0];
            }
            return new UnionType($filteredTypes);
        }
        return $docBlockTypeHints;
    }
}
