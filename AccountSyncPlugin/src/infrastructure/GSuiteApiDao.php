<?php
namespace App\Plugins\AccountSync;

require_once __DIR__ . '/../dao/Dao.php';

use Exceedone\Exment\Model\CustomTable;

class GSuiteApiDao implements GSuiteDao {

    private const TOKEN = '/../token.json';
    private const CREDENTIALS = '/../credentials.json';

    public function getMembers()
    {
        Logger::log("Get gsuite members");
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new \Google_Service_Directory($client);

        $members = array();
        $pageToken = '';
        do {
            // Print the first 10 users in the domain.
            $optParams = array(
                'customer' => 'my_customer',
                'maxResults' => 500,
                'pageToken' => $pageToken
            );
            $results = $service->users->listUsers($optParams);
            Logger::log("Get members count: " . count($results['users']));
            Logger::log("Next pageToken: " . $results['nextPageToken']);

            // Logger::log("gettype: " . gettype($results['users']));

            $members = array_merge($members, $results['users']);
            $pageToken = $results['nextPageToken'];
        } while($pageToken != '');

        Logger::log("All members count: " . count($members));

        // データ加工
        foreach($members as &$member) {
            $member["status"] = $member["suspended"] ? "Suspended" : "Active";
        }

        return $members;
    }
    
    public function saveMembers(string $tableName, array $members)
    {
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
    
            // Logger::log(var_export($result, true));
        }

        return count($members);
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    private function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('exment');
        $client->setScopes(\Google_Service_Directory::ADMIN_DIRECTORY_USER_READONLY);
        $client->setAuthConfig(dirname(__FILE__) . self::CREDENTIALS);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = dirname(__FILE__) . self::TOKEN;
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

}
