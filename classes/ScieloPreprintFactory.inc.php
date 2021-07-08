<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloPreprintFactory extends ScieloSubmissionFactory
{
    protected $application = 'ops';

    public function createSubmission(int $submissionId, string $locale): ScieloPreprint
    {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $publication = $submission->getCurrentPublication();
        $scieloPreprintsDAO = new ScieloPreprintsDAO();

        $submissionTitle = $publication->getData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($submission);
        $status = __($submission->getStatusKey());
        $authors = $this->retrieveAuthors($publication, $locale);
        $sectionName = $this->retrieveSectionName($publication, $locale);
        $language = $submission->getData('locale');
        list($finalDecision, $finalDecisionDate) = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloPreprintsDAO, $submissionId, $locale);
        $sectionModerator = $scieloPreprintsDAO->getSectionModerator($submissionId);
        $moderators = $scieloPreprintsDAO->getModerators($submissionId);
        $publicationStatus = $publication->getData('status');
        $publicationDOI = $scieloPreprintsDAO->getPublicationDOIBySubmission($submission);
        $notes = $scieloPreprintsDAO->getSubmissionNotes($submissionId);

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
