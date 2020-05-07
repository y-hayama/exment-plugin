<?php

namespace App\Plugins\WorkableSync;

use Exceedone\Exment\Services\Plugin\PluginSettingBase;

class PluginSetting extends PluginSettingBase {
    public const ACCESS_KEY = "access_key";
    public const TABLE = "table";
    public const BUTTON_UPDATED_AFTER = "button_updated_after";
    public const BATCH_UPDATED_AFTER = "batch_updated_after";

    // カスタムパラメータ有効化
    protected $useCustomOption = true;

    /**
     * プラグインの編集画面で設定するオプション
     *
     * @param $form
     * @return void
     */
    public function setCustomOptionForm(&$form) {
        $form->text(self::ACCESS_KEY, 'アクセスキー')
            ->help('Workableのアクセスキーを入力してください');
        
        $form->text(self::TABLE, 'Batch用更新テーブル')
            ->help('Batchで同期するテーブルを入力してください');
        
        $form->text(self::BATCH_UPDATED_AFTER, 'Batch用取得期間')
            ->help('Workableのデータ取得期間を入力してください（日指定）');

        $form->text(self::BUTTON_UPDATED_AFTER, 'ボタン用取得期間')
            ->help('Workableのデータ取得期間を入力してください（分指定）');
    }

}
