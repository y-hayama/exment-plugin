<?php
namespace App\Plugins\AccountSync;

use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Model\CustomColumn;
use Exceedone\Exment\Model\CustomRelation;
use Exceedone\Exment\Services\NotifyService;

class Exment {

    protected $webhook;

    public function setWebHookUrl(string $webhook) {
        $this->webhook = $webhook;
    }

    public function sendMessage(string $message) {
        Logger::log($message);
        NotifyService::notifySlack([
            'webhook_url' => $this->webhook,
            'body' => $message,
        ]);
    }

    public function linkId(string $parentTableName, string $childTableName, string $parentKey, string $childKey) {
        Logger::log("{$parentTableName}と{$childTableName}のデータを紐付けます");

        $parentTable = CustomTable::getEloquent($parentTableName);
        $childTable = CustomTable::getEloquent($childTableName);
        $relationName = CustomRelation::getRelationNamebyTables($parentTableName, $childTableName);
        
        $count = 0;
        $items = $parentTable->getValueModel()->all();
        foreach($items as $item) {
            $result = $childTable->getValueModel()->where("value->" . $childKey, $item->getValue($parentKey))->first();
            // Logger::log(var_export($result, true));

            if(isset($result)) {
                $item->$relationName()->sync($result->id);
                $count++;
            }
        }
        return $count;
    }

}
