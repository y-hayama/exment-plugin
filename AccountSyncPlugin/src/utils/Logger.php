<?php
namespace App\Plugins\AccountSync;

class Logger {

    public static function log($message) {
        \Log::debug($message);
    }

}
