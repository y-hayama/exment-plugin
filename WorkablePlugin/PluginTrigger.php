<?php

namespace App\Plugins\WorkableSync;

require_once __DIR__ . '/PluginSetting.php';
require_once __DIR__ . '/src/Entity/Candidate.php';
require_once __DIR__ . '/src/Repositories/ExmentRepository.php';
require_once __DIR__ . '/src/Repositories/IExmentRepository.php';
require_once __DIR__ . '/src/Repositories/WorkableRestRepository.php';
require_once __DIR__ . '/src/Repositories/IWorkableRepository.php';

use Exceedone\Exment\Services\Plugin\PluginTriggerBase;
use App\Plugins\WorkableSync\Candidate;
use App\Plugins\WorkableSync\IWorkableRepository;
use App\Plugins\WorkableSync\WorkableRestRepository;
use App\Plugins\WorkableSync\IExmentRepository;
use App\Plugins\WorkableSync\ExmentRepository;

class PluginTrigger extends PluginTriggerBase {

    public function execute() {
        $this->log("トリガーを実行しました");

        try {
            $access_key = $this->plugin->getCustomOption(PluginSetting::ACCESS_KEY);
            if ($access_key == null) {
                throw new \Exception("必要なパラメータが入力されていません");
            }

            $this->log("設定完了");

            $workable = new WorkableRestRepository($access_key);
            $exment = new ExmentRepository();
            $table = $this->custom_table;
            $updated_after = -1;

            $this->log("初期化完了");

            $candidates = $workable->getCandidate(new \DateTime("$updated_after day"));
            //\Log::debug(var_export($candidates, true));
            $result = $exment->store($table, $candidates);
            $this->popup($result . "件のデータを取得しました");
        } catch(\Exception $e) {
            $this->popup("データ更新に失敗しました：" . $e->getMessage());
        }

        return true;
    }

    private function log($message) {
        \Log::debug($message);
    }

    private function popup($message) {
        admin_toastr($message);
    }
}
