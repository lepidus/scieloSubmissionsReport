<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');

class ScieloArticle extends ScieloSubmission
{
    private $editors;
    private $sectionEditor;
    private $reviews;
    private $lastDecision;
    private $noEditors;
    private $noDecision;

    public function __construct(int $id, string $title, string $submitter, string $submitterCountry, string $dateSubmitted, int $daysUntilStatusChange, string $status, array $authors, string $section, string $language, string $finalDecision, string $finalDecisionDate, array $editors, string $sectionEditor, array $reviews, string $lastDecision)
    {
        parent::__construct($id, $title, $submitter, $submitterCountry, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $section, $language, $finalDecision, $finalDecisionDate);
        $this->editors = $editors;
        $this->sectionEditor = $sectionEditor;
        $this->reviews = $reviews;
        $this->lastDecision = $lastDecision;
        $this->noEditors = __("plugins.reports.scieloSubmissionsReport.warning.noEditors");
        $this->noDecision = __("plugins.reports.scieloSubmissionsReport.warning.noDecision");
    }

    public function asRecord(): array
    {
        return array($this->id, $this->title, $this->submitter, $this->submitterCountry, $this->dateSubmitted, $this->daysUntilStatusChange, $this->status, $this->getJournalEditors(), $this->getSectionEditor(), $this->authorsAsRecord(), $this->section, $this->language, $this->getReviews(), $this->lastDecision, $this->finalDecision, $this->finalDecisionDate, $this->getTimeUnderReview(), $this->getTimeBetweenSubmissionAndFinalDecision());
    }

    public function getJournalEditors(): string
    {
        return $this->implodeEmptyFields($this->editors, $this->noEditors);
    }

    public function getSectionEditor(): string
    {
        return $this->fillEmptyFields($this->sectionEditor, $this->noEditors);
    }

    public function getReviews(): string
    {
        return $this->implodeEmptyFields($this->reviews, "");
    }

    public function hasReviews(): bool
    {
        foreach ($this->reviews as $review) {
            if (!empty($review)) {
                return true;
            }
        }
        return false;
    }

    public function getLastDecision(): string
    {
        return $this->fillEmptyFields($this->lastDecision, $this->noDecision);
    }
}
