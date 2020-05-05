<?php

namespace App\Plugins\WorkableSync;

require_once __DIR__ . '/IWorkableRepository.php';
require_once __DIR__ . '/../Entity/Candidate.php';

use App\Plugins\WorkableSync\Candidate;
use App\Plugins\WorkableSync\IWorkableRepository;

class WorkableRestRepository implements IWorkableRepository {
    private const ENDPOINT = 'https://uzabase-inc.workable.com';
    private const API_JOB = '/spi/v3/jobs';
    private const API_CANDIDATES = '/spi/v3/candidates';
    private const TIMEOUT = 10.0;
    private const LIMIT = 1000;
    private $api_key;
    
    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    private function restRequest(string $method, string $request) {
        $client = new \GuzzleHttp\Client([
            'base_uri' => self::ENDPOINT,
            'headers' => [
                'Content-Type' => "application/json",
                'Authorization' => "Bearer " . $this->api_key
            ],
            'timeout' => self::TIMEOUT
        ]);
        //var_dump($client);

        $response = $client->request($method, $request);
        
        if($response->getStatusCode() != 200 ) {
            \Log::debug("error");
            \Log::debug(var_export($response->getStatusCode(), true));
            \Log::debug(var_export($response->getBody(), true));
            throw new \Exception("Httpリクエストエラー");
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @return Candidate[]
     */
    public function getCandidate(\DateTime $updated_after) {
        $limit = self::LIMIT;
        $updated = $updated_after->format(\DateTime::ATOM);
        $request = self::API_CANDIDATES . "?limit=$limit&updated_after=$updated";
        $candidates = array();

        do {
            \Log::debug("Workable Request URL:" . $request);

            $response = $this->restRequest('GET', $request);
            //var_dump($response);
            $request = array_key_exists("paging", $response) ? $response["paging"]["next"] : null;
            //var_dump($request);

            foreach ($response["candidates"] as $candidate) {
                array_push($candidates, new Candidate($candidate));
            }
        } while($request != NULL);

        return $candidates;
    }
}
