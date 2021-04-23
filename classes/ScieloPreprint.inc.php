<?php

class ScieloPreprint extends ScieloSubmission {

    private $moderators;
    private $sectionModerator;
    private $publicationStatus;
    private $publicationDOI;
    private $notes;

    public function __construct(int $id, string $title, string $submitter, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate, array $moderators, string $sectionModerator, string $publicationStatus, string $publicationDOI, array $notes) {
        parent::__construct($id, $title, $submitter, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $section, $language, $finalDecision, $finalDecisionDate);
        $this->moderators = $moderators;
        $this->sectionModerator = $sectionModerator;
        $this->publicationStatus = $publicationStatus;
        $this->publicationDOI = $publicationDOI;
        $this->notes = $notes;
    }

    public function getModerators() : string {
        return $this->implodeEmptyFields($this->moderators, "No moderators");
    }

    public function getSectionModerator() : string {
        return $this->fillEmptyFields($this->sectionModerator, "No moderators");
    }

    public function getPublicationStatus() : string {
        return $this->fillEmptyFields($this->publicationStatus, "No publication's status");
    }

    public function getPublicationDOI() : string {
        return $this->fillEmptyFields($this->publicationDOI, "No publication's DOI");
    }

    public function getNotes() : string {
        if(empty($this->notes))
            return "No notes";
        
        return trim(preg_replace('/\s+/', ' ', "Note: " . implode(". Note: ", $this->notes)));
    }
}

?>