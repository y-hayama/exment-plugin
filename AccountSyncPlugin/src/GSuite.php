<?php
namespace App\Plugins\AccountSync;

class GSuite {

    public function getMembers()
    {
        $this->log("Get gsuite members");
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
            $this->log("Get members count: " . count($results['users']));
            $this->log("Next pageToken: " . $results['nextPageToken']);

            // $this->log("gettype: " . gettype($results['users']));
            $members = array_merge($members, $results['users']);
            $pageToken = $results['nextPageToken'];
        } while($pageToken != '');

        $this->log("All members count: " . count($members));

        // データ加工
        foreach($members as &$member) {
            $member["status"] = $member["suspended"] ? "Suspended" : "Active";
        }

        return $members;
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
        $client->setAuthConfig(dirname(__FILE__) . '/credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = dirname(__FILE__) . '/token.json';
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

    private function log(string $message) {
        \Log::debug($message);
        // echo $message . "\n";
    }

}
