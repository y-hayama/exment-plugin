<?php
namespace App\Plugins\AccountSync\Factory;

require_once __DIR__ . '/../Infrastructure/GSuiteApiDao.php';
require_once __DIR__ . '/../Infrastructure/SlackApiDao.php';

use App\Plugins\AccountSync\Infrastructure\SlackApiDao;
use App\Plugins\AccountSync\Infrastructure\GSuiteApiDao;

class DaoFactory {

    public static function getSlackDao(string $token) {
        return new SlackApiDao($token);
    }

    public static function getGSuiteDao() {
        return new GSuiteApiDao();
    }

}
