<?php

$foo = $_POST;

function foo()
{
    echo $var;
    echo $_POST['bar'];
}

class FooBarSuperGlobal
{
    public function __construct()
    {
        echo $_GET;
    }
}