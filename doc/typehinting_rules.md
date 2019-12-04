PHPDoc related rules
====================

This package contains a set of rules to help you remember to type-hint correctly your methods.

Remember this is a PHP 7.1+ package so nullable type hints are available.

The ideas with those rules are very simple:

- You should use type-hinting when possible
- If not possible, you should use a Docblock to specify the type
- If type-hinting against an array, you should use a Docblock to further explain the content of the array

## Missing type-hint detection

This code snippet:

```php
function foo($no_type_hint)
{
    // ...
}
```

will yield 2 warnings:

    In function "foo", parameter $no_type_hint has no type-hint and no @param annotation.
    In function "foo", there is no return type and no @return annotation.
    
You can fix this waring like this:

```php
function foo(string $no_type_hint): void
{
    // ...
}
```

## Missing PHP type-hint detection

This code snippet:

```php
/**
 * @param string|null $type_hintable
 * @return string
 */
function foo($type_hintable)
{

}
```

will yield 2 warnings:

    In function "foo", parameter $type_hintable can be type-hinted to "?string".
    In function "foo", a "string" return type can be added.
    
Indeed! You specify a type in the PHP docblock while it could really be part of the method signature.

You can fix this waring like this:

```php
/**
 * @param string|null $type_hintable
 * @return string
 */
function foo(?string $type_hintable): string
{
    // ...
}
```

## Vague array type-hint detection

This code snippet:

```php
function foo(array $arr): array
{

}
```

will yield 2 warnings:

    In function "foo", parameter $arr type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @param int[] $arr
    In function "foo", return type is "array". Please provide a @param annotation to further specify the type of the array. For instance: @return int[]
    
The "array" type-hinting is vague in PHP. You don't really know what kind of array you are dealing with. Is it an array of objects? An array of strings? An array of array of objects?

You can fix this waring like this:

```php
/**
 * @param string[] $type_hintable
 * @return SomeObject[]
 */
function foo(array $arr): array
{
    // ...
}
```

## Type mismatch detection

This code snippet:

```php
/**
 * @param int $param
 * @return int
 */
function mismatch(string $param): string
{

}
```

will yield 2 warnings:

    In function "mismatch", parameter $param type is type-hinted to "string" but the @param annotation says it is a "int". Please fix the @param annotation.
    In function "mismatch", return type is type-hinted to "string" but the @return annotation says it is a "int". Please fix the @return annotation.
    
Indeed, the docblock says a type is an int while the typehint says it's a string.

Fix the PHP docblock like this:

```php
/**
 * @param string $param
 * @return string
 */
function no_more_mismatch(string $param): string
{

}
```

(by the way, the docblock is completely redundant with the function declaration so unless you add some textual comment in it, you could completely remove it)
