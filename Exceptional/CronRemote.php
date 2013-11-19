<?php

namespace OBV\Exceptional;

class CronRemote
{
    public static function sendExceptions()
    {
        foreach (Logger::getLogFiles() as $filePath) {
            $reports = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($reports as &$report) {
                list($url, $data) = Encoder::decode($report);

                Remote::callRemote($url, $data) && ($report = null);
            }
            unset($report);

            if (array_filter($reports) || !unlink($filePath)) {
                break;
            }
        }
    }
}
