<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/factory/DaoFactory.php';
require_once __DIR__ . '/dao/Dao.php';
require_once __DIR__ . '/model/Exment.php';

class AccountSync {

    private const SLACK_TABLE = "slack";
    private const GSUITE_TABLE = "Gsuite_id";
    private const TEMP_TABLE = "temporary";
    private const EMPL_TABLE = "employee";
    private const EMPL2_TABLE = "empl2";

    protected $slack_token;
    protected $slack_webhook;

    public function __construct(string $slack_token, string $slack_webhook) {
        $this->slack_token = $slack_token;
        $this->slack_webhook = $slack_webhook;
    }

    public function execute() {

        $slack = DaoFactory::getSlackDao($this->slack_token);
        $gsuite = DaoFactory::getGSuiteDao();
        $exment = new Exment();
        $exment->setWebHookUrl($this->slack_webhook);
        $_ = function($s){return $s;};

        try {
            $exment->sendMessage("AccountSyncを実行しました");

            $slackMembers = $slack->getMembers();
            Logger::log("テーブルにデータを保存します");
            $resultSlack = $slack->saveMembers(self::SLACK_TABLE, $slackMembers);

            $gsuiteMembers = $gsuite->getMembers();
            Logger::log("テーブルにデータを保存します");
            $resultGsuite = $gsuite->saveMembers(self::GSUITE_TABLE, $gsuiteMembers);

            $resultTempSlack = $exment->linkId(self::TEMP_TABLE, self::SLACK_TABLE, 'address', 'email');
            $resultEmplSlack = $exment->linkId(self::EMPL_TABLE, self::SLACK_TABLE, 'work_email', 'email');
            $resultEmpl2Slack = $exment->linkId(self::EMPL2_TABLE, self::SLACK_TABLE, 'work_email', 'email');
            $resultTempGsuite = $exment->linkId(self::TEMP_TABLE, self::GSUITE_TABLE, 'address', 'emailaddress');
            $resultEmplGsuite = $exment->linkId(self::EMPL_TABLE, self::GSUITE_TABLE, 'work_email', 'emailaddress');
            $resultEmpl2Gsuite = $exment->linkId(self::EMPL2_TABLE, self::GSUITE_TABLE, 'work_email', 'emailaddress');

            $exment->sendMessage(<<<_EOS
データの処理が終了しました
結果
```
・Slackアカウントを{$resultSlack}件処理しました
・GSuiteアカウントを{$resultGsuite}件処理しました
・{$_(self::TEMP_TABLE)}と{$_(self::SLACK_TABLE)}のデータを{$resultTempSlack}件紐付けました
・{$_(self::EMPL_TABLE)}と{$_(self::SLACK_TABLE)}のデータを{$resultEmplSlack}件紐付けました
・{$_(self::EMPL2_TABLE)}と{$_(self::SLACK_TABLE)}のデータを{$resultEmpl2Slack}件紐付けました
・{$_(self::TEMP_TABLE)}と{$_(self::GSUITE_TABLE)}のデータを{$resultTempGsuite}件紐付けました
・{$_(self::EMPL_TABLE)}と{$_(self::GSUITE_TABLE)}のデータを{$resultEmplGsuite}件紐付けました
・{$_(self::EMPL2_TABLE)}と{$_(self::GSUITE_TABLE)}のデータを{$resultEmpl2Gsuite}件紐付けました
```
_EOS
            );
        } catch(\Exception $e) {
            $exment->sendMessage("AccountSyncの実行に失敗しました：" . $e->getMessage());
        }

        $exment->sendMessage("AccountSyncを完了しました");
        return true;

    }
}
