<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloPreprintFactory extends ScieloSubmissionFactory
{
    private $application = 'ops';

    public function createSubmission(int $submissionId, string $locale): ScieloPreprint
    {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $publication = $submission->getCurrentPublication();
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();

        $submissionTitle = $publication->getData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($submission);
        $status = __($submission->getStatusKey());
        $authors = $this->retrieveAuthors($publication, $locale);
        $sectionName = $this->retrieveSectionName($publication, $locale);
        $language = $submission->getData('locale');

        $finalDecisionWithDate = $scieloSubmissionsDao->getFinalDecisionWithDate($this->application, $submissionId, $locale);
        $finalDecision = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDecision()) : "";
        $finalDecisionDate = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDateDecided()) : "";

        $publicationStatus = $publication->getData('status');
        $publicationDOI = $scieloSubmissionsDao->getPublicationDOIBySubmission($submission);
        list($sectionModerator, $moderators) = $scieloSubmissionsDao->getAllModeratorsBySubmissionId($submissionId);
        $notes = $scieloSubmissionsDao->getSubmissionNotes($submissionId);

        return new ScieloPreprint(
            $submissionId,
            $submissionTitle,
            $submitter,
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
