<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use TheCodingMachine\PHPStan\Rules\Exceptions\DoNotThrowExceptionBaseClassRule;
use TheCodingMachine\PHPStan\Rules\Exceptions\EmptyExceptionRule;

class DoNotThrowExceptionBaseClassRuleTest extends \PHPStan\Rules\AbstractRuleTest
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new DoNotThrowExceptionBaseClassRule();
	}

	public function testCheckCatchedException()
	{
		$this->analyse([__DIR__ . '/data/throw_exception.php'], [
			[
				'Do not throw the \Exception base class. Instead, extend the \Exception base class',
				17,
			],
		]);
	}
}
