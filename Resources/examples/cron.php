<?php

use OBV\Component\Exceptional\Exceptional;
use OBV\Component\Exceptional\CronRemote;

// setup Exceptional with the following two lines
Exceptional::setup('YOUR-API-KEY', true, '/tmp/eio-logs');

// send to Exceptional.io all postponed exception reports
CronRemote::sendExceptions();
