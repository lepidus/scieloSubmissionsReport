<?php

class ScieloSubmissionsReport {

    private $sections;
    private $submissions;

    public function __construct(array $sections, array $submissions) {
        $this->sections = $sections;
        $this->submissions = $submissions;
    }

    public function getSections() : array {
        return $this->sections;
    }

    public function getSubmissions() : array {
        return $this->submissions;
    }
}