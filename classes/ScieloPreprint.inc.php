<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');

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

    public function asRecord(): array {
        return array($this->id, $this->title, $this->submitter, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->getSectionModerator(), $this->getModerators(), $this->authorsAsRecord(), $this->section, $this->language, $this->getPublicationStatus(), $this->getPublicationDOI(), $this->getNotes(), $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());
    }

    public function getModerators() : string {
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        return $this->implodeEmptyFields($this->moderators, $messageNoModerators);
    }

    public function getSectionModerator() : string {
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        return $this->fillEmptyFields($this->sectionModerator, $messageNoModerators);
    }

    public function getPublicationStatus() : string {
        $messageNoPublicationStatus = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationStatus");
        return $this->fillEmptyFields($this->publicationStatus, $messageNoPublicationStatus);
    }

    public function getPublicationDOI() : string {
        $messageNoPublicationDOI = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");
        return $this->fillEmptyFields($this->publicationDOI, $messageNoPublicationDOI);
    }

    public function getNotes() : string {
        if(empty($this->notes))
            return __("plugins.reports.scieloSubmissionsReport.warning.noNotes");
        
        return trim(preg_replace('/\s+/', ' ', "Note: " . implode(". Note: ", $this->notes)));
    }
}

?>