<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsDAO');
import('plugins.reports.scieloSubmissionsReport.classes.SubmissionAuthor');

class ScieloSubmissionFactory
{
    public function createSubmission(int $submissionId, string $locale)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $submission = $scieloSubmissionsDao->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submissionTitle = $scieloSubmissionsDao->getPublicationTitle($publicationId, $locale, $submission['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submission['date_submitted'];
        $status = $this->getStatusMessage($submission['status']);
        $sectionName = $scieloSubmissionsDao->getPublicationSection($publicationId, $locale);
        $language = $submission['locale'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submission['date_last_activity']);
        $authors = $this->retrieveAuthors($publicationId, $locale);

        $finalDecision = "";
        $finalDecisionDate = "";

        return new ScieloSubmission(
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
            $finalDecisionDate
        );
    }

    protected function retrieveFinalDecisionAndFinalDecisionDate($scieloSubmissionDAO, $submissionId, $locale): array
    {
        $finalDecisionWithDate = $scieloSubmissionDAO->getFinalDecisionWithDate($submissionId, $locale);
        $finalDecision = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDecision()) : "";
        $finalDecisionDate = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDateDecided()) : "";
        return array($finalDecision, $finalDecisionDate);
    }

    protected function retrieveSectionName($publication, $locale)
    {
        $sectionId = $publication->getData('sectionId');
        $section = DAORegistry::getDAO('SectionDAO')->getById($sectionId);
        return $section->getTitle($locale);
    }

    protected function retrieveSubmitter($submissionId)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $userId = $scieloSubmissionsDao->getIdOfSubmitterUser($submissionId);

        if (is_null($userId)) {
            return "";
        }

        $userDao = DAORegistry::getDAO('UserDAO');
        $submitter = $userDao->getById($userId);

        return $submitter->getFullName();
    }

    protected function retrieveSubmitterCountry($submissionId)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $userId = $scieloSubmissionsDao->getIdOfSubmitterUser($submissionId);

        if (is_null($userId)) {
            return "";
        }

        $userDao = DAORegistry::getDAO('UserDAO');
        $submitter = $userDao->getById($userId);
        $submitterCountry = $submitter->getCountryLocalized();

        return !is_null($submitterCountry) ? $submitterCountry : "";
    }

    protected function calculateDaysUntilStatusChange($dateSubmitted, $dateLastActivity)
    {
        $dateSubmitted = new DateTime($dateSubmitted);
        $dateLastActivity = new DateTime($dateLastActivity);
        $daysUntilStatusChange = $dateLastActivity->diff($dateSubmitted)->format('%a');

        return $daysUntilStatusChange;
    }

    protected function getStatusMessage($statusKey)
    {
        $statusMap = [
            STATUS_QUEUED => 'submissions.queued',
            STATUS_PUBLISHED => 'submission.status.published',
            STATUS_DECLINED => 'submission.status.declined',
            STATUS_SCHEDULED => 'submission.status.scheduled'
        ];

        return __($statusMap[$statusKey]);
    }

    protected function retrieveAuthors($publicationId, $locale)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $authorsIds =  $scieloSubmissionsDao->getPublicationAuthors($publicationId);
        $submissionAuthors = [];

        foreach ($authorsIds as $authorId) {
            $author = DAORegistry::getDAO('AuthorDAO')->getById($authorId);
            $fullName = $author->getFullName($locale);
            $country = $author->getCountryLocalized();
            $affiliation = $author->getLocalizedData('affiliation', $locale);

            $country = (!is_null($country)) ? ($country) : ("");
            $affiliation = (!is_null($affiliation)) ? ($affiliation) : ("");
            $submissionAuthors[] = new SubmissionAuthor($fullName, $country, $affiliation);
        }

        return $submissionAuthors;
    }
}
