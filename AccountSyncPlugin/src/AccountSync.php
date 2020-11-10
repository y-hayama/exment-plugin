<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/Slack.php';
require_once __DIR__ . '/GSuite.php';

use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Model\CustomColumn;
use Exceedone\Exment\Model\CustomRelation;
use Exceedone\Exment\Services\NotifyService;
use Exceedone\Exment\Enums\RelationType;

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

        $this->sendSlackMessage("AccountSyncを実行しました");

        try {
            $slack = new Slack($this->slack_token);
            $slackMembers = $slack->getMembers();
            $resultSlack = $this->storeToSlack(self::SLACK_TABLE, $slackMembers);

            $gsuite = new GSuite();
            $gsuiteMembers = $gsuite->getMembers();
            $resultGsuite = $this->storeToGsuite(self::GSUITE_TABLE, $gsuiteMembers);

            $resultTempSlack = $this->syncId(self::TEMP_TABLE, self::SLACK_TABLE, 'address', 'email');
            $resultEmplSlack = $this->syncId(self::EMPL_TABLE, self::SLACK_TABLE, 'work_email', 'email');
            $resultEmpl2Slack = $this->syncId(self::EMPL2_TABLE, self::SLACK_TABLE, 'work_email', 'email');
            $resultTempGsuite = $this->syncId(self::TEMP_TABLE, self::GSUITE_TABLE, 'addres', 'emailaddress');
            $resultEmplGsuite = $this->syncId(self::EMPL_TABLE, self::GSUITE_TABLE, 'work_email', 'emailaddress');
            $resultEmpl2Gsuite = $this->syncId(self::EMPL2_TABLE, self::GSUITE_TABLE, 'work_email', 'emailaddress');
 
            $_ = function($s){return $s;};
            $this->sendSlackMessage(<<<_EOS
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
            $this->sendSlackMessage("AccountSyncの実行に失敗しました：" . $e->getMessage());
        }

        $this->sendSlackMessage("AccountSyncを完了しました");
        return true;

    }
    
    private function storeToSlack(string $tableName, array $members) {
        $this->log("テーブルにデータを保存します");

        $table = CustomTable::getEloquent($tableName);
        foreach ($members as $member) {
            $model = $table->getValueModel();
            $result = $model->where("value->userid", $member["id"])->first();
            if(isset($result)) {
                $model = $result;
            }

            $model->setValue("userid", $member["id"]);
            $model->setValue("username", $member["name"]);
            $model->setValue("displayname", $member["profile"]["display_name"]);
            $model->setValue("fullname", $member["profile"]["real_name"]);
            $model->setValue("email", $member["profile"]["email"]);
            $model->setValue("status", $member["status"]);
            $model->setValue("billing-active", $member["billingActive"]);

            $result = $model->save();
    
            // \Log::debug(var_export($result, true));
        }

        return count($members);
    }

    private function storeToGsuite(string $tableName, array $members) {
        $this->log("テーブルにデータを保存します");

        $table = CustomTable::getEloquent($tableName);
        foreach ($members as $member) {
            $model = $table->getValueModel();
            $result = $model->where("value->gsuite_id", $member["id"])->first();
            if(isset($result)) {
                $model = $result;
            }

            $model->setValue("gsuite_id", $member["id"]);
            $model->setValue("firstname", $member["name"]["givenName"]);
            $model->setValue("lastname", $member["name"]["familyName"]);
            $model->setValue("emailaddress", $member["primaryEmail"]);
            $model->setValue("status", $member["status"]);
            $model->setValue("lastsignIn", $member["lastLoginTime"]);
            $result = $model->save();
    
            // \Log::debug(var_export($result, true));
        }

        return count($members);
    }

    private function syncId(string $parentTableName, string $childTableName, string $parentKey, string $childKey) {
        $this->log("{$parentTableName}と{$childTableName}のデータを紐付けます");

        $parentTable = CustomTable::getEloquent($parentTableName);
        $childTable = CustomTable::getEloquent($childTableName);
        $relationName = CustomRelation::getRelationNamebyTables($parentTableName, $childTableName);
        
        $count = 0;
        $items = $parentTable->getValueModel()->all();
        foreach($items as $item) {
            $result = $childTable->getValueModel()->where("value->" . $childKey, $item->getValue($parentKey))->first();
            if(isset($result)) {
                $item->$relationName()->sync($result->id);
                $count++;
            }
        }
        return $count;
    }

    private function sendSlackMessage(string $message) {
        $this->log($message);
        NotifyService::notifySlack([
            'webhook_url' => $this->slack_webhook,
            'body' => $message,
        ]);
    }

    private function log($message) {
        \Log::debug($message);
    }

}
