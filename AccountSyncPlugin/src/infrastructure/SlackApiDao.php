<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/../dao/Dao.php';

use Exceedone\Exment\Model\CustomTable;

class SlackApiDao implements SlackDao {
    protected $token;
    private const TIMEOUT = 300;

    function __construct(string $token) {
        $this->token = $token;
    }

    public function getMembers() {
        // 情報取得
        $members = $this->getMemberInfo();
        $billableInfo = $this->getBillableInfo();

        // データ加工
        foreach($members as &$member) {
            $status = "Member";
            if($member["deleted"]) {
                $status = "Deactivated";
            } else if($member["is_primary_owner"]) {
                $status = "Primary Owner";
            } else if($member["is_owner"]) {
                $status = "Owner";
            } else if($member["is_admin"]) {
                $status = "Admin";
            } else if($member["is_ultra_restricted"]) {
                $status = "Single-Channel Guest";
            } else if($member["is_restricted"]) {
                $status = "Multi-Channel Guest";
            } else if($member["is_bot"]) {
                $status = "Bot";
            } else if($member["is_app_user"]) {
                $status = "Bot";
            }
            $member["status"] = $status;

            if(!array_key_exists("email", $member["profile"])) {
                $member["profile"]["email"] = "";
                if($member["is_bot"] || $member["is_app_user"]) {
                    $member["profile"]["email"] = "botuser-" . $member["team_id"] . "-" . $member["profile"]["bot_id"] . "@slack-bots.com";
                }
            }

            $member["billingActive"] = array_key_exists($member["id"], $billableInfo) ? 1 : 0;
        }
        
        return $members;
    }

    public function saveMembers(string $tableName, array $members)
    {
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
    
            // Logger::log(var_export($result, true));
        }

        return count($members);
    }

    private function getMemberInfo() {
        $members = array();
        $cursor = "";
        $limit = 1000;
        do {
            $endpoint = "https://slack.com/api/users.list" . "?limit=$limit" . "&cursor=$cursor";
            $response = $this->sendGetRequest($endpoint);
            $contents = json_decode($response->getBody()->getContents(), true);

            Logger::log("Slack OK status: " . $contents["ok"]);
            Logger::log("Get members count: " . count($contents["members"]));
            $members = array_merge($members, $contents["members"]);
            $cursor = $contents["response_metadata"]["next_cursor"];
            Logger::log("Next cursor: " . $cursor);
        } while($cursor != "");

        Logger::log("All members count: " . count($members));
        return $members;
    }

    private function getBillableInfo() {
        $endpoint = "https://slack.com/api/team.billableInfo";
        $response = $this->sendGetRequest($endpoint);
        $contents = json_decode($response->getBody()->getContents(), true);

        Logger::log("Slack OK status: " . $contents["ok"]);
        Logger::log("Billable info count: " . count($contents["billable_info"]));

        return $contents["billable_info"];
    }

    private function sendGetRequest(string $url)
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request("GET", $url, [
            'headers' => [
                'Accept' => "application/x-www-form-urlencoded",
                'Authorization' => "Bearer " . $this->token
            ],
            'timeout' => self::TIMEOUT
        ]);

        Logger::log("SlackAPIを実行しました");
        Logger::log("HTTP Status Code: " . $response->getStatusCode());
        Logger::log("HTTP Status: " . $response->getReasonPhrase());

        if($response->getStatusCode() != 200 ) {
            Logger::log("API Request error");
            Logger::log("HTTP Body: " . $response->getBody());
            throw new \Exception("SlackAPI リクエストエラー");
        }

        return $response;
    }

}
