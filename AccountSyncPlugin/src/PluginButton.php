<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/AccountSync.php';

use Exceedone\Exment\Services\Plugin\PluginButtonBase;

class PluginButton extends PluginButtonBase {

    /**
     * execute
     */
    public function execute() {

        $token = $this->plugin->getCustomOption(PluginSetting::SLACK_TOKEN);
        $webhook = $this->plugin->getCustomOption(PluginSetting::SLACK_WEBHOOK);
        if ($token == null || $webhook == null) {
            throw new \Exception("必要なパラメータが設定されていません");
        }

        $accountSync = new AccountSync($token, $webhook);
        $accountSync->execute();

        return true;

    }

}
