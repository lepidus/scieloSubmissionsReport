<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class ScieloArticleFactory extends ScieloSubmissionFactory
{
    protected $application = 'ojs';

    public function createSubmission(int $submissionId, string $locale)
    {
        $scieloArticlesDAO = app(ScieloArticlesDAO::class);
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

        [$finalDecision, $finalDecisionDate] = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloArticlesDAO, $submissionId, $locale);
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
