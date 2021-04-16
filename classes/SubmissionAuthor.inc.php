<?php 

class SubmissionAuthor {
    private $fullName;
    private $country;
    private $affiliation;

public function __construct(string $fullName, string $country, string $affiliation){
        $this->fullName = $fullName;
        $this->country = $country;
        $this->affiliation = $affiliation;
    }

    public function getFullName() {
        return $this->fullName;
    }
    
    public function getCountry() {
        return $this->country;
    }

    public function getAffiliation() {
        return $this->affiliation;
    }
}
    