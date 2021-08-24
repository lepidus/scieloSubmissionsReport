<?php

class ScieloSubmission {

    protected $id;
    protected $title;
    protected $submitter;
    protected $submitterCountry;
    protected $dateSubmitted;
    protected $daysUntilStatusChange;
    protected $status;
    protected $authors;
    protected $section;
    protected $language;
    protected $finalDecision;
    protected $finalDecisionDate;

    public function __construct(int $id, string $title, string $submitter, string $submitterCountry, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate) {
        $this->id = $id;
        $this->title = $title;
        $this->submitter = $submitter;
        $this->submitterCountry = $submitterCountry;
        $this->dateSubmitted = $dateSubmitted;
        $this->daysUntilStatusChange = $daysUntilStatusChange;
        $this->status = $status;
        $this->authors = $authors;
        $this->section = $section;
        $this->language = $language;
        $this->finalDecision = $finalDecision;
        $this->finalDecisionDate = $finalDecisionDate;
    }

    protected function fillEmptyFields($field, $messageIfEmpty) {
        if (empty($field))
            return $messageIfEmpty;

        return $field;
    }

    protected function implodeEmptyFields($field, $messageIfEmpty) : string {
        if(empty($field))
            return $messageIfEmpty;

        return implode(",", $field);
    }

    public function getId() : int {
        return $this->id;
    }

    public function getTitle() : string {
        return preg_replace('/\s+/', ' ', $this->title);
    }

    public function getSubmitter() : string {
        $messageNoSubmitter = __("plugins.reports.scieloSubmissionsReport.warning.noSubmitter");
        return $this->fillEmptyFields($this->submitter, $messageNoSubmitter);
    }

    public function getSubmitterCountry() : string {
        return $this->submitterCountry;
    }

    public function getDateSubmitted() : string {
        return $this->dateSubmitted;
    }

    public function getStatus() : string {
        return $this->status;
    }

    public function getAuthors() : array {
        return $this->authors;
    }

    public function getSection() : string {
        return $this->section;
    }

    public function getLanguage() : string {
        return $this->language;
    }

    public function getFinalDecision() : string {
        return $this->finalDecision;
    }

    public function getDaysUntilStatusChange() : int {
        return $this->daysUntilStatusChange;
    }

    public function getFinalDecisionDate() : string {
        return $this->finalDecisionDate;
    }

    public function getTimeUnderReview() : int {
        $dateFinal = new DateTime(trim($this->finalDecisionDate));
        $dateBegin = new DateTime(trim($this->dateSubmitted));
        $reviewingTime = $dateFinal->diff($dateBegin);
        return $reviewingTime->format('%a');
    }
    
    public function getTimeBetweenSubmissionAndFinalDecision() : string {
        if(!empty($this->finalDecisionDate))
            return $this->getTimeUnderReview();

        return "";
    }

    protected function authorsAsRecord() : string {
        $records = [];

        foreach($this->authors as $author) {
            $records[] = $author->asRecord();
        }

        return implode("; ", $records);
    }

    public function asRecord(): array {
        return array($this->id, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authorsAsRecord(), $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());
    }
}