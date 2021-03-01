<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/../infrastructure/GSuiteApiDao.php';
require_once __DIR__ . '/../infrastructure/SlackApiDao.php';

class DaoFactory {

    public static function getSlackDao(string $token) {
        return new SlackApiDao($token);
    }

    public static function getGSuiteDao() {
        return new GSuiteApiDao();
    }

}
