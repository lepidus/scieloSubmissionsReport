<?php

class ScieloPreprint extends ScieloSubmission {

    private $moderators;
    private $sectionModerator;
    private $publicationStatus;
    private $publicationDOI;

    public function __construct(int $id, string $title, string $submitter, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate, array $moderators, string $sectionModerator, string $publicationStatus, string $publicationDOI) {
        parent::__construct($id, $title, $submitter, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $section, $language, $finalDecision, $finalDecisionDate);
        $this->moderators = $moderators;
        $this->sectionModerator = $sectionModerator;
        $this->publicationStatus = $publicationStatus;
        $this->publicationDOI = $publicationDOI;
    }

    public function getModerators() : array {
        return $this->moderators;
    }

    public function getSectionModerator() : string {
        return $this->sectionModerator;
    }

    public function getPublicationStatus() : string {
        return $this->publicationStatus;
    }

    public function getPublicationDOI() : string {
        return $this->publicationDOI;
    }
}

?>