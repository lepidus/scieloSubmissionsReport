<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');

class ScieloPreprint extends ScieloSubmission {

    private $submitterIsScieloJournal;
    private $moderators;
    private $sectionModerators;
    private $publicationStatus;
    private $publicationDOI;
    private $notes;
    private $abstractViews;
    private $pdfViews;

    public function __construct(int $id, string $title, string $submitter, string $submitterCountry, bool $submitterIsScieloJournal, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate, array $moderators, array $sectionModerators, string $publicationStatus, string $publicationDOI, array $notes, int $abstractViews = null, int $pdfViews = null) {
        parent::__construct($id, $title, $submitter, $submitterCountry, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $section, $language, $finalDecision, $finalDecisionDate);
        $this->submitterIsScieloJournal = $submitterIsScieloJournal;
        $this->moderators = $moderators;
        $this->sectionModerators = $sectionModerators;
        $this->publicationStatus = $publicationStatus;
        $this->publicationDOI = $publicationDOI;
        $this->notes = $notes;
        $this->abstractViews = $abstractViews;
        $this->pdfViews = $pdfViews;
    }

    public function asRecord(): array {
        $record = array($this->id, $this->title, $this->submitter, $this->submitterCountry, $this->getSubmitterIsScieloJournal(), $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->getSectionModerators(), $this->getModerators(), $this->authorsAsRecord(), $this->section, $this->language, $this->getPublicationStatus(), $this->getPublicationDOI(), $this->getNotes(), $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());

        if(!is_null($this->abstractViews) && !is_null($this->pdfViews)){
            $record = array_merge($record, [$this->abstractViews, $this->pdfViews]);
        }
        return $record;
    }

    public function getSubmitterIsScieloJournal() : string {
        return $this->submitterIsScieloJournal ? __("common.yes") : __("common.no");
    }

    public function getModerators() : string {
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        return $this->implodeEmptyFields($this->moderators, $messageNoModerators);
    }

    public function getSectionModerators() : string {
        $messageNoModerators = __("plugins.reports.scieloSubmissionsReport.warning.noModerators");
        return $this->implodeEmptyFields($this->sectionModerators, $messageNoModerators);
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

    public function getAbstractViews() : ?int {
        return $this->abstractViews;
    }

    public function getPdfViews() : ?int {
        return $this->pdfViews;
    }
}

?>