<?php

// setup Exceptional with the following two lines
\OBV\Exceptional\Exceptional::setup("YOUR-API-KEY");

// control which errors are caught with error_reporting
error_reporting(E_ALL);

// start testing
$math = 1 / 0;
