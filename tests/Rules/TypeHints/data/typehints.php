<?php

function test($no_type_hint)
{

}

/**
 * @param string|null $type_hintable
 * @return string
 */
function test2($type_hintable)
{

}

/**
 * @param DateTimeInterface $type_hintable
 * @return DateTimeInterface
 */
function test3($type_hintable)
{

}

/**
 * @param DateTimeInterface[] $type_hintable
 * @return DateTimeInterface[]
 */
function test4($type_hintable)
{

}

/**
 * @param DateTimeInterface[] $type_hintable
 */
function test5(array $type_hintable): void
{

}

function test6(array $better_type_hint): array
{

}

/**
 * @param int $param
 * @return int
 */
function mismatch(?string $param): string
{

}
