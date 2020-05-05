<?php
namespace App\Plugins\HarddeleteData;

use Exceedone\Exment\Services\Plugin\PluginTriggerBase;
use Exceedone\Exment\Model\CustomTable;

class Plugin extends PluginTriggerBase{
    private const TABLE='table';

    // カスタムパラメータ有効化
    protected $useCustomOption = true;

    public function setCustomOptionForm(&$form) {
        $form->text(self::TABLE, '削除テーブル')
            ->help('物理するテーブルを入力してください');
    }

    /**
     * execute
     */
    public function execute() {

        $this->log("トリガーを実行しました");

        try {
            $table_name = $this->plugin->getCustomOption(self::TABLE);
            $this->log($table_name . "を削除します", true);
            $table = CustomTable::getEloquent($table_name);
            $model = $table->getValueModel();
            $this->log(var_export($model->onlyTrashed()->count() .  "件", true));
            $model->onlyTrashed()->forceDelete();
            $this->log(var_export($model->onlyTrashed()->count() .  "件", true));
        } catch(\Exception $e) {
            $this->log("バッチの実行に失敗しました：" . $e->getMessage());
        }

        return true;

    }
    
    private function log($message) {
        \Log::debug($message);
    }
}