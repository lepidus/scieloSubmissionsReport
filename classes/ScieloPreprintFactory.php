<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class ScieloPreprintFactory extends ScieloSubmissionFactory
{
    protected $application = 'ops';
    private $includeViews;

    public function __construct(bool $includeViews = false)
    {
        $this->includeViews = $includeViews;
    }

    public function createSubmission(int $submissionId, string $locale)
    {
        $scieloPreprintsDAO = new ScieloPreprintsDAO();
        $submission = $scieloPreprintsDAO->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submissionTitle = $scieloPreprintsDAO->getPublicationTitle($publicationId, $locale, $submission['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $submitterIsScieloJournal = $this->retrieveSubmitterIsScieloJournal($submissionId);
        $dateSubmitted = $submission['date_submitted'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submission['date_last_activity']);
        $status = $this->getStatusMessage($submission['status']);
        $authors = $this->retrieveAuthors($publicationId, $locale);
        $sectionName = $scieloPreprintsDAO->getPublicationSection($publicationId, $locale);
        $language = $submission['locale'];
        [$finalDecision, $finalDecisionDate] = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloPreprintsDAO, $submissionId, $locale);
        $sectionModerators = $scieloPreprintsDAO->getSectionModerators($submissionId);
        $responsibles = $scieloPreprintsDAO->getResponsibles($submissionId);
        $publicationStatus = $scieloPreprintsDAO->getPublicationStatus($publicationId);
        $publicationDOI = $scieloPreprintsDAO->getPublicationDOI($publicationId);
        $notes = $scieloPreprintsDAO->getSubmissionNotes($submissionId);
        $stats = null;

        if ($this->includeViews) {
            $abstractViews = $scieloPreprintsDAO->getAbstractViews($submissionId, $submission['context_id']);
            $pdfViews = $scieloPreprintsDAO->getPdfViews($submissionId, $submission['context_id']);
            $stats = new SubmissionStats($abstractViews, $pdfViews);
        }

        return new ScieloPreprint(
            $submissionId,
            $submissionTitle,
            $submitter,
            $submitterCountry,
            $submitterIsScieloJournal,
            $dateSubmitted,
            $daysUntilStatusChange,
            $status,
            $authors,
            $sectionName,
            $language,
            $finalDecision,
            $finalDecisionDate,
            $responsibles,
            $sectionModerators,
            $publicationStatus,
            $publicationDOI,
            $notes,
            $stats
        );
    }

    private function retrieveSubmitterIsScieloJournal($submissionId)
    {
        $scieloPreprintsDao = new ScieloPreprintsDAO();
        $submitterId = $scieloPreprintsDao->getIdOfSubmitterUser($submissionId);

        if (is_null($submitterId)) {
            return false;
        }

        return $scieloPreprintsDao->getSubmitterIsScieloJournal($submitterId);
    }
}
