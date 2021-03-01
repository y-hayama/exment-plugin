<?php

namespace App\Plugins\AccountSync;

use Exceedone\Exment\Services\Plugin\PluginSettingBase;

class PluginSetting extends PluginSettingBase {

    public const SLACK_TOKEN = 'SLACK_TOKEN';
    public const SLACK_WEBHOOK = 'SLACK_WEBHOOK';

    // カスタムパラメータ有効化
    protected $useCustomOption = true;

    public function setCustomOptionForm(&$form) {
        $form->text(self::SLACK_TOKEN, 'Slackトークン')
            ->help('Slackトークンを入力してください');
        $form->text(self::SLACK_WEBHOOK, 'Slack Webhook URL')
            ->help('Slack Webhook URLを入力してください');
    }

}
