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

    public function getFullName() : string {
        return $this->fullName;
    }
    
    public function getCountry() : string {
        return $this->country;
    }

    public function getAffiliation() : string {
        return $this->affiliation;
    }

    public function asRecord() : string {
        return implode(", ", [$this->fullName, $this->country, $this->affiliation]);
    }
}
    