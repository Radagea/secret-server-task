<?php

namespace App\Entity;

use App\Repository\SecretRepository;

class TestSecret {
    private $secret;
    private $expireAfterViews;
    private $expireAfter;


    public function getSecret() : ?string {
        return $this->secret;
    }
    
    public function getExpireAfterViews() : ?int {
        return $this->expireAfterViews;
    }

    public function getExpireAfter(): ?int {
        return $this->expireAfter;
    }

    public function setSecret($secret)  {
        $this->secret = $secret;
    }

    public function setExpireAfterViews($expireAfterViews) {
        $this->expireAfterViews = $expireAfterViews;
    }

    public function setExpireAfter($expire) {
        $this->expireAfter = $expire;
    }

}

?>