<?php

class ScieloSubmission {

    private $id;
    private $title;
    private $submitter;
    private $dateSubmitted;
    private $daysUntilStatusChange;
    private $status;
    private $authors;
    private $section;
    private $language;
    private $finalDecision;
    private $finalDecisionDate;

    public function __construct(int $id, string $title, string $submitter, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate) {
        $this->id = $id;
        $this->title = $title;
        $this->submitter = $submitter;
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

    public function getId() : int {
        return $this->id;
    }

    public function getTitle() : string {
        return $this->title;
    }

    public function getSubmitter() : string {
        return $this->fillEmptyFields($this->submitter, "The submitting author was not found");
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

    private function authorsAsRecord() : string {
        $records = [];

        foreach($this->authors as $author) {
            $records[] = $author->asRecord();
        }

        return implode("; ", $records);
    }

    public function asRecord(): array {
        return array($this->id, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->authorsAsRecord(), $this->section, $this->language, $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());
    }
}