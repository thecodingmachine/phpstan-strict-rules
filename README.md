TheCodingMachine's additional rules for PHPStan
===============================================

This package contains a set of rules to be added to the [wonderful PHPStan static analyzer](https://github.com/phpstan/phpstan).

Those rules come from [TheCodingMachine best practices](http://bestpractices.thecodingmachine.com/).
They are more "strict" than the default PHPStan rules and some may be controversial. We use those at TheCodingMachine, have found them to help us in our daily work, and ask anyone working with us to follow them.

## Rules list

### Exception related rules

- You should not throw the "Exception" base class directly [but throw a sub-class instead](http://bestpractices.thecodingmachine.com/php/error_handling.html#subtyping-exceptions).
- You should not have empty catch statements
- When throwing an exception inside a catch block, [you should pass the catched exception as the "previous" exception](http://bestpractices.thecodingmachine.com/php/error_handling.html#wrapping-an-exception-do-not-lose-the-previous-exception)


    
    // TODO: Other ideas:
    // Don't use superglobals (__GET __POST)...
    // Always provide a "default" in a switch statement (and throw an exception if unexpected)
    // Never use public properties
    // Force type hinting on all methods, starting with PHP 7.1 (or mixed must be passed in @param docblock)

## Installation

We assume that [PHPStan](https://github.com/phpstan/phpstan) is already installed in your project.

Let's add this package:

```bash
composer require --dev thecodingmachine/phpstan-strict-rules
```

Now, edit you `phpstan.neon` file and add these rules:

```yml
services:
	-
		class: TheCodingMachine\PHPStan\Rules\Exceptions\ThrowMustBundlePreviousExceptionRule
		tags:
			- phpstan.rules.rule
	-
		class: TheCodingMachine\PHPStan\Rules\Exceptions\DoNotThrowExceptionBaseClassRule
		tags:
			- phpstan.rules.rule
	-
		class: TheCodingMachine\PHPStan\Rules\Exceptions\EmptyExceptionRule
		tags:
			- phpstan.rules.rule

```
