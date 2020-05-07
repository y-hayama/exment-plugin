<?php

namespace App\Plugins\WorkableSync;

require_once __DIR__ . '/PluginSetting.php';
require_once __DIR__ . '/src/Entity/Candidate.php';
require_once __DIR__ . '/src/Repositories/ExmentRepository.php';
require_once __DIR__ . '/src/Repositories/IExmentRepository.php';
require_once __DIR__ . '/src/Repositories/WorkableRestRepository.php';
require_once __DIR__ . '/src/Repositories/IWorkableRepository.php';

use Exceedone\Exment\Services\Plugin\PluginButtonBase;
use App\Plugins\WorkableSync\Candidate;
use App\Plugins\WorkableSync\IWorkableRepository;
use App\Plugins\WorkableSync\WorkableRestRepository;
use App\Plugins\WorkableSync\IExmentRepository;
use App\Plugins\WorkableSync\ExmentRepository;

class PluginButton extends PluginButtonBase {

    public function execute() {
        $this->log("トリガーを実行しました");

        try {
            $access_key = $this->plugin->getCustomOption(PluginSetting::ACCESS_KEY);
            $updated_after = $this->plugin->getCustomOption(PluginSetting::BUTTON_UPDATED_AFTER);
            if ($access_key == null || $updated_after == null) {
                throw new \Exception("必要なパラメータが入力されていません");
            }

            $this->log("設定完了");

            $workable = new WorkableRestRepository($access_key);
            $exment = new ExmentRepository();
            $table = $this->custom_table;

            $this->log("初期化完了");

            $candidates = $workable->getCandidate(new \DateTime("-$updated_after minute"));
            //\Log::debug(var_export($candidates, true));
            $result = $exment->store($table, $candidates);
        } catch(\Exception $e) {
            return [
                'result' => false,
                'swaltext' => '"データ更新に失敗しました：" . $e->getMessage()'
            ];
        }

        return [
            'result' => true,
            'swaltext' => $result . "件のデータを取得しました"
        ];
    }

    private function log($message) {
        \Log::debug($message);
    }

    private function popup($message) {
        admin_toastr($message);
    }
}
