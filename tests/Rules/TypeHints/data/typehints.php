<?php

function test($no_type_hint)
{

}

/**
 * @param string|null $type_hintable
 */
function test2($type_hintable)
{

}

/**
 * @param DateTimeInterface $type_hintable
 */
function test3($type_hintable)
{

}

/**
 * @param DateTimeInterface[] $type_hintable
 */
function test4($type_hintable)
{

}

/**
 * @param DateTimeInterface[] $type_hintable
 */
function test5(array $type_hintable)
{

}

function test6(array $better_type_hint)
{

}

/**
 * @param int $param
 */
function mismatch(string $param)
{

}
