<?php

// setup Exceptional with the following two lines
\OBV\Exceptional\Exceptional::setup('YOUR-API-KEY', '/tmp/eio-logs');

// send to Exceptional.io all postponed exception reports
\OBV\Exceptional\CronRemote::sendExceptions();
