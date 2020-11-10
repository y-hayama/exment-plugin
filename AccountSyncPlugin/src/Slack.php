<?php
namespace App\Plugins\AccountSync;

class Slack {
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

    private function getMemberInfo() {
        $members = array();
        $cursor = "";
        $limit = 1000;
        do {
            $endpoint = "https://slack.com/api/users.list" . "?limit=$limit" . "&cursor=$cursor";
            $response = $this->sendGetRequest($endpoint);
            $contents = json_decode($response->getBody()->getContents(), true);

            $this->log("Slack OK status: " . $contents["ok"]);
            $this->log("Get members count: " . count($contents["members"]));
            $members = array_merge($members, $contents["members"]);
            $cursor = $contents["response_metadata"]["next_cursor"];
            $this->log("Next cursor: " . $cursor);
        } while($cursor != "");

        $this->log("All members count: " . count($members));
        return $members;
    }

    private function getBillableInfo() {
        $endpoint = "https://slack.com/api/team.billableInfo";
        $response = $this->sendGetRequest($endpoint);
        $contents = json_decode($response->getBody()->getContents(), true);

        $this->log("Slack OK status: " . $contents["ok"]);
        $this->log("Billable info count: " . count($contents["billable_info"]));

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

        $this->log("SlackAPIを実行しました");
        $this->log("HTTP Status Code: " . $response->getStatusCode());
        $this->log("HTTP Status: " . $response->getReasonPhrase());

        if($response->getStatusCode() != 200 ) {
            $this->log("API Request error");
            $this->log("HTTP Body: " . $response->getBody());
            throw new \Exception("SlackAPI リクエストエラー");
        }

        return $response;
    }

    private function log(string $message) {
        \Log::debug($message);
    }

}
