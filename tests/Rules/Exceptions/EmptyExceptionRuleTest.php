<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use TheCodingMachine\PHPStan\Rules\Exceptions\EmptyExceptionRule;

class EmptyExceptionRuleTest extends \PHPStan\Rules\AbstractRuleTest
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new EmptyExceptionRule();
	}

	public function testCheckCatchedException()
	{
		$this->analyse([__DIR__ . '/data/catch.php'], [
			[
				'Empty catch block',
				17,
			],
			[
				'Empty catch block',
				30,
			],
		]);
	}
}
