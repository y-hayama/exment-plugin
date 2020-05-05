<?php

namespace App\Plugins\WorkableSync;

class Candidate {
    /*
    private $id;
    private $name;
    private $firstname;
    private $lastname;
    private $headline;
    private $stage;
    private $disqualified;
    private $disqualification_reason;
    private $sourced;
    private $profile_url;
    private $email;
    private $domain;
    private $created_at;
    private $updated_at;
    private $account;
    private $job;
    */
    private $var = [];

    public function __construct(array $candidate) {
        /*
        $this->id = $candidate["id"];
        $this->name = $candidate["name"];
        $this->firstname = $candidate["firstname"];
        $this->lastname = $candidate["lastname"];
        $this->headline = $candidate["headline"];
        $this->stage = $candidate["stage"];
        $this->disqualified = $candidate["disqualified"];
        $this->disqualification_reason = $candidate["disqualification_reason"];
        $this->sourced = $candidate["sourced"];
        $this->profile_url = $candidate["profile_url"];
        $this->email = $candidate["email"];
        $this->domain = $candidate["domain"];
        $this->created_at = $candidate["created_at"];
        $this->updated_at = $candidate["updated_at"];
        $this->account = $candidate["account"];
        $this->job = $candidate["job"];
        */
        $this->var = $candidate;
    }

    public function get(string $key, $default = null) {
        if(array_key_exists($key, $this->var)) {
            return $this->var[$key];
        }
        return $default;
    }

    public function set(string $key, $val) {
        $this->var[$key] = $val;
    }
}
