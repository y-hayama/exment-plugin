<?php
namespace App\Plugins\AccountSync;

interface SlackDao {
    public function getMembers();
    public function saveMembers(string $tableName, array $members);
}

interface GSuiteDao {
    public function getMembers();
    public function saveMembers(string $tableName, array $members);
}

