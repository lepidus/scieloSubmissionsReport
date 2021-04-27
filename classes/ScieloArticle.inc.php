<?php

class ScieloArticle extends ScieloSubmission {

    private $editors;
    private $sectionEditor;
    private $reviews;
    private $lastDecision;
    private const noEditors = "No editors";

    public function __construct(int $id, string $title, string $submitter, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate, array $editors, string $sectionEditor, array $reviews, string $lastDecision) {
        parent::__construct($id, $title, $submitter, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $section, $language, $finalDecision, $finalDecisionDate);
        $this->editors = $editors;
        $this->sectionEditor = $sectionEditor;
        $this->reviews = $reviews;
        $this->lastDecision = $lastDecision;
    }
    
    public function asRecord(): array {
        return array($this->id, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->getJournalEditors(), $this->getSectionEditor(), $this->authorsAsRecord(), $this->section, $this->language, $this->getReviews(), $this->lastDecision, $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());
    }

    public function getJournalEditors() : string {
        return $this->implodeEmptyFields($this->editors, self::noEditors);
    }

    public function getSectionEditor() : string {
        return $this->fillEmptyFields($this->sectionEditor, self::noEditors);
    }
    
    public function getReviews() : string {
       return $this->implodeEmptyFields($this->reviews, "");
    }

    public function hasReviews() : bool {
        foreach($this->reviews as $review){
            if(!empty($review))
                return true;
        }

        return false;
    }

    public function getLastDecision() : string {
        return $this->lastDecision;
    }
}