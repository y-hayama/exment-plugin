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

    public function getLastLoginLogs()
    {
        $loginLogs = array();
        $cursor = "";
        $limit = 1000;
        $oldest = strtotime("-2 week");
        do {
            $endpoint = "https://api.slack.com/audit/v1/logs?action=user_login" . "&limit=$limit" . "&cursor=$cursor" . "&oldest=$oldest";
            $contents = $this->sendGetRequest($endpoint);
            $entries = $contents["entries"];
            $cursor = $contents["response_metadata"]["next_cursor"];
            $loginLogs = array_merge($loginLogs, $contents["entries"]);

            Logger::log("Get logs count: " . count($entries));
            Logger::log("Next cursor: " . $cursor);
        } while($cursor != "");

        $latestLoginLogs = $this->removeDuplicateLoginLogs($loginLogs);

        return $latestLoginLogs;
    }

    public function getLastLogoutLogs()
    {
        $logoutLogs = array();
        $cursor = "";
        $limit = 1000;
        $oldest = strtotime("-2 week");
        do {
            $endpoint = "https://api.slack.com/audit/v1/logs?action=user_logout" . "&limit=$limit" . "&cursor=$cursor" . "&oldest=$oldest";
            $contents = $this->sendGetRequest($endpoint);
            $entries = $contents["entries"];
            $cursor = $contents["response_metadata"]["next_cursor"];
            $logoutLogs = array_merge($logoutLogs, $contents["entries"]);

            Logger::log("Get logs count: " . count($entries));
            Logger::log("Next cursor: " . $cursor);
        } while($cursor != "");

        $latestLogoutLogs = $this->removeDuplicateLogoutLogs($logoutLogs);

        return $latestLogoutLogs;
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

    public function saveLatestLoginLogs(string $tableName, array $latestLoginLogs) {
        $table = CustomTable::getEloquent($tableName);
        foreach ($latestLoginLogs as $log) {
            $userid = $log["actor"]["user"]["id"];
            $email = $log["actor"]["user"]["email"];
            $latestLoginTime = date("Y-m-d H:i:s", $log["date_create"]);

            $model = $table->getValueModel();
            $model = $model->where("value->userid", $userid)->first();
            $model->setValue("userid", $userid);
            $model->setValue("email", $email);
            $model->setValue("lastsignIn", $latestLoginTime);
            $result = $model->save();
        }

        return count($latestLoginLogs);
    }

    public function saveLatestLogoutLogs(string $tableName, array $latestLogoutLogs) {
        $table = CustomTable::getEloquent($tableName);
        foreach ($latestLogoutLogs as $log) {
            $userid = $log["actor"]["user"]["id"];
            $email = $log["actor"]["user"]["email"];
            $latestLogoutTime = date("Y-m-d H:i:s", $log["date_create"]);

            $model = $table->getValueModel();
            $model = $model->where("value->userid", $userid)->first();
            $model->setValue("userid", $userid);
            $model->setValue("email", $email);
            $model->setValue("lastsignout", $latestLogoutTime);
            $result = $model->save();
        }

        return count($latestLogoutLogs);
    }

    private function getMemberInfo() {
        $members = array();
        $cursor = "";
        $limit = 1000;
        do {
            $endpoint = "https://slack.com/api/users.list" . "?limit=$limit" . "&cursor=$cursor";
            $contents = $this->sendGetRequest($endpoint);

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
        $contents = $this->sendGetRequest($endpoint);

        Logger::log("Slack OK status: " . $contents["ok"]);
        Logger::log("Billable info count: " . count($contents["billable_info"]));

        return $contents["billable_info"];
    }

    private function removeDuplicateLoginLogs(array $loginLogs) {
        Logger::log("total logs count: " . count($loginLogs));

        // ログアウトログが複数あるメールアドレスを取り出す
        $allEmailList = array();
        foreach ($loginLogs as $log) {
            $allEmailList[] = $log["actor"]["user"]["email"];
        }
        $uniqEmailList = array_count_values($allEmailList);
        $duplicateEmailList = array_filter($uniqEmailList, function($v){return --$v;});
        Logger::log("total uniq email count: " . count($uniqEmailList));
        Logger::log("dup email count: " . count($duplicateEmailList));

        // ログアウトログが複数あるメールアドレスについて最新のログ以外を削除する
        foreach ($duplicateEmailList as $email => $count) {
            $duplicateLogs = array_filter($loginLogs, function($log) use ($email) {
                return strcmp($log["actor"]["user"]["email"], $email) == 0;
            });

            // 降順にログが帰ってくるので最初のログが最新のログになる
            $latestLogKey = array_key_first($duplicateLogs);
            // $lastLoginTime = max(array_column($duplicateLogs, "date_create"));
            foreach ($duplicateLogs as $k => $v) {
                if($k != $latestLogKey) unset($loginLogs[$k]);
            }
        }
        
        Logger::log("dup logs count: " . array_sum($duplicateEmailList));
        Logger::log("last logs count: " . count($loginLogs));

        return $loginLogs;
    }

    private function removeDuplicateLogoutLogs(array $loginLogs) {
        Logger::log("total logs count: " . count($loginLogs));

        // ログインログが複数あるメールアドレスを取り出す
        $allEmailList = array();
        foreach ($loginLogs as $log) {
            $allEmailList[] = $log["actor"]["user"]["email"];
        }
        $uniqEmailList = array_count_values($allEmailList);
        $duplicateEmailList = array_filter($uniqEmailList, function($v){return --$v;});
        Logger::log("total uniq email count: " . count($uniqEmailList));
        Logger::log("dup email count: " . count($duplicateEmailList));

        // ログインログが複数あるメールアドレスについて最新のログ以外を削除する
        foreach ($duplicateEmailList as $email => $count) {
            $duplicateLogs = array_filter($loginLogs, function($log) use ($email) {
                return strcmp($log["actor"]["user"]["email"], $email) == 0;
            });

            // 降順にログが帰ってくるので最初のログが最新のログになる
            $latestLogKey = array_key_first($duplicateLogs);
            // $lastLoginTime = max(array_column($duplicateLogs, "date_create"));
            foreach ($duplicateLogs as $k => $v) {
                if($k != $latestLogKey) unset($loginLogs[$k]);
            }
        }
        
        Logger::log("dup logs count: " . array_sum($duplicateEmailList));
        Logger::log("last logs count: " . count($loginLogs));

        return $loginLogs;
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

        $contents = json_decode($response->getBody()->getContents(), true);
        return $contents;
    }

}
