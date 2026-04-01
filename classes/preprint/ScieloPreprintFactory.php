<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes\preprint;

use APP\plugins\reports\scieloSubmissionsReport\classes\submission\ScieloSubmissionFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\SubmissionStats;

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
        $submissionData = $this->getSubmissionData($submissionId, $locale);
        $scieloPreprintsDAO = new ScieloPreprintsDAO();
        $submission = $scieloPreprintsDAO->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submitterIsScieloJournal = $this->retrieveSubmitterIsScieloJournal($submissionId);
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
            $submissionData['title'],
            $submissionData['submitter'],
            $submissionData['submitterCountry'],
            $submitterIsScieloJournal,
            $submissionData['doi'],
            $submissionData['dateSubmitted'],
            $submissionData['daysUntilStatusChange'],
            $submissionData['status'],
            $submissionData['authors'],
            $submissionData['sectionName'],
            $submissionData['language'],
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
