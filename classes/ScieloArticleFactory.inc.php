<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticle');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticlesDAO');

class ScieloArticleFactory extends ScieloSubmissionFactory
{
    protected $application = 'ojs';

    public function createSubmission(int $submissionId, string $locale)
    {
        $scieloArticlesDAO = new ScieloArticlesDAO();
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, $locale);
        $submission = $scieloArticlesDAO->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submissionTitle = $scieloArticlesDAO->getPublicationTitle($publicationId, $locale, $submission['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submission['date_submitted'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submission['date_last_activity']);
        $status = $this->getStatusMessage($submission['status']);
        $authors = $this->retrieveAuthors($publicationId, $locale);
        $sectionName = $scieloArticlesDAO->getPublicationSection($publicationId, $locale);
        $language = $submission['locale'];

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
