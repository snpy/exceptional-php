<?php

use OBV\Component\Exceptional\Exceptional;

// set custom error handler
function my_error_handler($errno, $errstr, $errfile, $errline) {
    printf('Error on line %s%s', $errline, "\n");
}
set_error_handler('my_error_handler');


// set custom exception handler
function my_exception_handler($exception) {
    printf('Exception thrown: %s%s', $exception->getMessage(), "\n");
}
set_exception_handler('my_exception_handler');


// setup Exceptional with the following two lines
// this code must come **after** you set custom error/exception handlers
Exceptional::setup('YOUR-API-KEY', true, '/tmp/eio-logs'); // use ssl and logging


// add controller and action
Exceptional::setController('welcome');
Exceptional::setAction('index');


// add context
$context = array(
    'user_id' => 1,
);
Exceptional::context($context);


// control which errors are caught with error_reporting
error_reporting(E_ALL | E_STRICT);


// start testing
echo $hi;
$math = 1 / 0;

function backtrace($i) {
    if ($i < 6) {
        return backtrace($i + 1);
    }
    echo $cool;
}
backtrace(0);

function change(&$var) {}
change($var = 5);

class Foo
{
    public function bar()
    {
        throw new Exception('This is pretty neat!');
    }
}

$f = new Foo;
$f->bar();


// execution halts after exception_handler is called (PHP behavior)
// so code below never gets called
echo 'This never gets called!';
