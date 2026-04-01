<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes\article;

use APP\plugins\reports\scieloSubmissionsReport\classes\submission\ScieloSubmissionFactory;

class ScieloArticleFactory extends ScieloSubmissionFactory
{
    protected $application = 'ojs';

    public function createSubmission(int $submissionId, string $locale)
    {
        $submissionData = $this->getSubmissionData($submissionId, $locale);
        $scieloArticlesDAO = app(ScieloArticlesDAO::class);

        [$finalDecision, $finalDecisionDate] = $this->retrieveFinalDecisionAndFinalDecisionDate($scieloArticlesDAO, $submissionId, $locale);
        $journalEditors = $scieloArticlesDAO->getJournalEditors($submissionId);
        $sectionEditor = $scieloArticlesDAO->getSectionEditor($submissionId);
        $reviews = $scieloArticlesDAO->getReviews($submissionId);
        $lastDecision = $scieloArticlesDAO->getLastDecision($submissionId);

        return new ScieloArticle(
            $submissionId,
            $submissionData['title'],
            $submissionData['submitter'],
            $submissionData['submitterCountry'],
            $submissionData['doi'],
            $submissionData['dateSubmitted'],
            $submissionData['daysUntilStatusChange'],
            $submissionData['status'],
            $submissionData['authors'],
            $submissionData['sectionName'],
            $submissionData['language'],
            $finalDecision,
            $finalDecisionDate,
            $journalEditors,
            $sectionEditor,
            $reviews,
            $lastDecision
        );
    }
}
