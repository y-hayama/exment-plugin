<?php
namespace App\Plugins\AccountSync\Utils;

class Logger {

    public static function log($message) {
        \Log::debug($message);
    }

}
