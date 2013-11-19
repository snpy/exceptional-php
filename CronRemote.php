<?php

namespace OBV\Component\Exceptional;

/**
 * Class CronRemote
 *
 * @package OBV\Component\Exceptional
 */
class CronRemote
{
    /**
     * Process exceptional log
     *
     * @return bool
     */
    public static function sendExceptions()
    {
        foreach (Logger::getLogFiles() as $filePath) {
            $reports = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($reports as &$report) {
                list($url, $data) = Encoder::decode($report);

                $level = error_reporting(0);
                Remote::callRemote($url, $data) && ($report = null);
                error_reporting($level);
            }
            unset($report);

            if (array_filter($reports) || !unlink($filePath)) {
                return false;
            }
        }

        return true;
    }
}
