<?php

namespace App\Plugins\WorkableSync;

require_once __DIR__ . '/PluginSetting.php';
require_once __DIR__ . '/src/Entity/Candidate.php';
require_once __DIR__ . '/src/Repositories/ExmentRepository.php';
require_once __DIR__ . '/src/Repositories/IExmentRepository.php';
require_once __DIR__ . '/src/Repositories/WorkableRestRepository.php';
require_once __DIR__ . '/src/Repositories/IWorkableRepository.php';

use Exceedone\Exment\Services\Plugin\PluginBatchBase;
use Exceedone\Exment\Model\CustomTable;
use App\Plugins\WorkableSync\Candidate;
use App\Plugins\WorkableSync\IWorkableRepository;
use App\Plugins\WorkableSync\WorkableRestRepository;
use App\Plugins\WorkableSync\IExmentRepository;
use App\Plugins\WorkableSync\ExmentRepository;

class PluginBatch extends PluginBatchBase {

    public function execute() {
        $this->log("バッチを実行しました");

        try {
            $table_name = $this->plugin->getCustomOption(PluginSetting::TABLE);
            $updated_after = $this->plugin->getCustomOption(PluginSetting::UPDATED_AFTER);
            $access_key = $this->plugin->getCustomOption(PluginSetting::ACCESS_KEY);
            if ($table_name == null || $updated_after == null || $access_key == null) {
                throw new \Exception("必要なパラメータが入力されていません");
            }

            $workable = new WorkableRestRepository($access_key);
            $exment = new ExmentRepository();
            $table = CustomTable::getEloquent($table_name);

            $candidates = $workable->getCandidate(new \DateTime("$updated_after day"));
            //\Log::debug(var_export($candidates, true));
            $result = $exment->store($table, $candidates);
            $this->log($result . "件のデータを取得しました");
        } catch(\Exception $e) {
            $this->log("バッチの実行に失敗しました：" . $e->getMessage());
        }

        return true;
    }

    private function log($message) {
        \Log::debug($message);
    }
}
