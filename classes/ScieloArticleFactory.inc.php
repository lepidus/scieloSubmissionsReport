<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticle');

class ScieloArticleFactory extends ScieloSubmissionFactory
{
    private $application = 'ojs';

    public function createSubmission(int $submissionId, string $locale): ScieloArticle
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

        list($finalDecision, $finalDecisionDate) = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloSubmissionsDao, $submissionId, $locale);
        list($editors, $sectionEditor) = $scieloSubmissionsDao->getEditors($submissionId);
        $reviews = $scieloSubmissionsDao->getReviews($submissionId);
        $lastDecision = $scieloSubmissionsDao->getLastDecision($submissionId);

        return new ScieloArticle(
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
            $editors,
            $sectionEditor,
            $reviews,
            $lastDecision
        );
    }

    private function retrieveFinalDecisionAndFinalDecisionDate($scieloSubmissionsDao, $submissionId, $locale): array
    {
        $finalDecisionWithDate = $scieloSubmissionsDao->getFinalDecisionWithDate($this->application, $submissionId, $locale);
        $finalDecision = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDecision()) : "";
        $finalDecisionDate = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDateDecided()) : "";
        return array($finalDecision, $finalDecisionDate);
    }
}
