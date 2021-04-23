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
    
    public function getJournalEditors() : string {
        return $this->implodeEmptyFields($this->editors, self::noEditors);
    }

    public function getSectionEditor() : string {
        return $this->fillEmptyFields($this->sectionEditor, self::noEditors);
    }
    
    public function getReviews() : string {
       return $this->implodeEmptyFields($this->reviews, "");
    }

    public function getLastDecision() : string {
        return $this->lastDecision;
    }
}