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

    public function buildCSV($fileDescriptor) : void {
        fprintf($fileDescriptor, chr(0xEF).chr(0xBB).chr(0xBF));

        foreach($this->submissions as $submission){
            fputcsv($fileDescriptor, $submission->asRecord());
        }
    }
}