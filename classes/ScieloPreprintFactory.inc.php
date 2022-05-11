<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintsDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloPreprintFactory extends ScieloSubmissionFactory
{
    protected $application = 'ops';

    public function createSubmission(int $submissionId, string $locale)
    {
        $scieloPreprintsDAO = new ScieloPreprintsDAO();
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, $locale);
        $submission = $scieloPreprintsDAO->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submissionTitle = $scieloPreprintsDAO->getPublicationTitle($publicationId, $locale, $submission['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submission['date_submitted'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submission['date_last_activity']);
        $status = $this->getStatusMessage($submission['status']);
        $authors = $this->retrieveAuthors($publicationId, $locale);
        $sectionName = $scieloPreprintsDAO->getPublicationSection($publicationId, $locale);
        $language = $submission['locale'];
        list($finalDecision, $finalDecisionDate) = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloPreprintsDAO, $submissionId, $locale);
        $sectionModerators = $scieloPreprintsDAO->getSectionModerators($submissionId);
        $moderators = $scieloPreprintsDAO->getModerators($submissionId);
        $publicationStatus = $scieloPreprintsDAO->getPublicationStatus($publicationId);
        $publicationDOI = $scieloPreprintsDAO->getPublicationDOI($publicationId);
        $notes = $scieloPreprintsDAO->getSubmissionNotes($submissionId);

        return new ScieloPreprint(
            $submissionId,
            $submissionTitle,
            $submitter,
            $submitterCountry,
            $dateSubmitted,
            $daysUntilStatusChange,
            $status,
            $authors,
            $sectionName,
            $language,
            $finalDecision,
            $finalDecisionDate,
            $moderators,
            $sectionModerators,
            $publicationStatus,
            $publicationDOI,
            $notes
        );
    }
}
