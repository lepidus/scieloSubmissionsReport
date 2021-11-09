<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintsDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloPreprintFactory extends ScieloSubmissionFactory
{
    protected $application = 'ops';

    public function createSubmission(int $submissionId, string $locale)
    {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $publication = $submission->getCurrentPublication();
        $scieloPreprintsDAO = new ScieloPreprintsDAO();
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, $locale);
        $submissionData = $scieloPreprintsDAO->getSubmissionMainData($submissionId);
        $publicationId = $submissionData['current_publication_id'];

        $submissionTitle = $scieloPreprintsDAO->getPublicationTitle($publicationId, $locale, $submissionData['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submissionData['date_submitted'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submissionData['date_last_activity']);
        $status = $this->getStatusMessage($submissionData['status']);
        $authors = $this->retrieveAuthors($publication, $locale);
        $sectionName = $scieloPreprintsDAO->getPublicationSection($publicationId, $locale);
        $language = $submissionData['locale'];
        list($finalDecision, $finalDecisionDate) = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloPreprintsDAO, $submissionId, $locale);
        $sectionModerator = $scieloPreprintsDAO->getSectionModerator($submissionId);
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
            $sectionModerator,
            $publicationStatus,
            $publicationDOI,
            $notes
        );
    }
}
