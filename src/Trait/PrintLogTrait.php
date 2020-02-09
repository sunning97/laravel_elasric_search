<?php

namespace Kuroneko\ElasticSearch\Traits;

/**
 * Trait PrintLogTrait
 * @package Kuroneko\ElasticSearch\Traits
 * @author Giang Nguyen
 */
trait PrintLogTrait
{
    /**
     * @param string $message
     * @param bool $full
     */

    public function printMessage($message = '', $full = true)
    {
        if (is_bool($message)) $message = $message == true ? 'true' : 'false';

        if ($full) {
            echo "\n" . date('d/m/Y H:i:s') . ' - [m] Class: ' . get_called_class() . ' - Message: ' . $message . "\n";
        } else {
            echo "\nMessage: " . $message . "\n";
        }
    }

    /**
     * @param $message
     * @param bool $full
     */
    public function printError($message, $full = true)
    {
        if ($full) {
            echo "\n" . date('d/m/Y H:i:s') . ' - [e] Class: ' . get_called_class() . ' - Message: ' . $message . "\n";
        } else {
            echo "\nMessage: " . $message . "\n";
        }
    }

    /**
     * @param \Exception $exception
     * @param bool $full
     */
    public function printException(\Exception $exception, $full = true)
    {
        if ($full) {
            echo "\n" . date('d/m/Y H:i:s') . ' - [Exception] Class: ' . get_called_class() . ' - Message: ' . $exception->getMessage() . ' - Line: ' . $exception->getLine() . ' - File: ' . $exception->getFile() . ' - Trace: ' . $exception->getTraceAsString() . "\n";
        } else {
            echo "\nException: " . $exception->getMessage() . "\n";
        }
    }
}