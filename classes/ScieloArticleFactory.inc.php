<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticle');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticlesDAO');

class ScieloArticleFactory extends ScieloSubmissionFactory
{
    protected $application = 'ojs';

    public function createSubmission(int $submissionId, string $locale)
    {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $publication = $submission->getCurrentPublication();
        $scieloArticlesDAO = new ScieloArticlesDAO();
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, $locale);

        $submissionTitle = $publication->getLocalizedData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($submission);
        $status = __($submission->getStatusKey());
        $authors = $this->retrieveAuthors($publication, $locale);
        $sectionName = $this->retrieveSectionName($publication, $locale);
        $language = $submission->getData('locale');

        list($finalDecision, $finalDecisionDate) = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloArticlesDAO, $submissionId, $locale);
        $editors = $scieloArticlesDAO->getEditors($submissionId);
        $sectionEditor = $scieloArticlesDAO->getSectionEditor($submissionId);
        $reviews = $scieloArticlesDAO->getReviews($submissionId);
        $lastDecision = $scieloArticlesDAO->getLastDecision($submissionId);

        return new ScieloArticle(
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
            $editors,
            $sectionEditor,
            $reviews,
            $lastDecision
        );
    }

}
