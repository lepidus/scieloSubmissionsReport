<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

use APP\facades\Repo;
use APP\submission\Submission;
use DateTime;
use PKP\db\DAORegistry;

class ScieloSubmissionFactory
{
    public function createSubmission(int $submissionId, string $locale)
    {
        $submissionData = $this->getSubmissionData($submissionId, $locale);
        $finalDecision = '';
        $finalDecisionDate = '';

        return new ScieloSubmission(
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
            $finalDecisionDate
        );
    }

    protected function getSubmissionData(int $submissionId, string $locale): array
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $submission = $scieloSubmissionsDao->getSubmission($submissionId);
        $publicationId = $submission['current_publication_id'];

        $submissionTitle = $scieloSubmissionsDao->getPublicationTitle($publicationId, $locale, $submission['locale']);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $doi = $scieloSubmissionsDao->getDoiOfPublication($publicationId);
        $dateSubmitted = $submission['date_submitted'];
        $status = $this->getStatusMessage($submission['status']);
        $sectionName = $scieloSubmissionsDao->getPublicationSection($publicationId, $locale);
        $language = $submission['locale'];
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($dateSubmitted, $submission['date_last_activity']);
        $authors = $this->retrieveAuthors($publicationId, $locale);

        return [
            'title' => $submissionTitle,
            'submitter' => $submitter,
            'submitterCountry' => $submitterCountry,
            'doi' => $doi,
            'dateSubmitted' => $dateSubmitted,
            'status' => $status,
            'sectionName' => $sectionName,
            'language' => $language,
            'daysUntilStatusChange' => $daysUntilStatusChange,
            'authors' => $authors
        ];
    }

    protected function retrieveFinalDecisionAndFinalDecisionDate($scieloSubmissionDAO, $submissionId, $locale): array
    {
        $finalDecisionWithDate = $scieloSubmissionDAO->getFinalDecisionWithDate($submissionId, $locale);
        $finalDecision = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDecision()) : '';
        $finalDecisionDate = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDateDecided()) : '';
        return [$finalDecision, $finalDecisionDate];
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
            return '';
        }

        $submitter = Repo::user()->get($userId, true);
        if (is_null($submitter)) {
            return '';
        }

        return $submitter->getFullName();
    }

    protected function retrieveSubmitterCountry($submissionId)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $userId = $scieloSubmissionsDao->getIdOfSubmitterUser($submissionId);

        if (is_null($userId)) {
            return '';
        }

        $submitter = Repo::user()->get($userId, true);
        if (is_null($submitter)) {
            return '';
        }

        $submitterCountry = $submitter->getCountryLocalized();

        return !is_null($submitterCountry) ? $submitterCountry : '';
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
            Submission::STATUS_QUEUED => 'submissions.queued',
            Submission::STATUS_PUBLISHED => 'submission.status.published',
            Submission::STATUS_DECLINED => 'submission.status.declined',
            Submission::STATUS_SCHEDULED => 'submission.status.scheduled'
        ];

        return __($statusMap[$statusKey]);
    }

    protected function retrieveAuthors($publicationId, $locale)
    {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $authorsIds = $scieloSubmissionsDao->getPublicationAuthors($publicationId);
        $submissionAuthors = [];

        foreach ($authorsIds as $authorId) {
            $author = Repo::author()->get($authorId);
            $fullName = $author->getFullName($locale);
            $country = $author->getCountryLocalized();
            $affiliation = $author->getLocalizedData('affiliation', $locale);

            $country = (!is_null($country)) ? ($country) : ('');
            $affiliation = (!is_null($affiliation)) ? ($affiliation) : ('');
            $submissionAuthors[] = new SubmissionAuthor($fullName, $country, $affiliation);
        }

        return $submissionAuthors;
    }
}
