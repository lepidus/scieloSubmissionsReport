<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsDAO');
import('plugins.reports.scieloSubmissionsReport.classes.SubmissionAuthor');

class ScieloSubmissionFactory
{
    public function createSubmission(int $submissionId, string $locale)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $publication = $submission->getCurrentPublication();

        $submissionTitle = $publication->getData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $submitterCountry = $this->retrieveSubmitterCountry($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $status = __($submission->getStatusKey());
        $sectionName = $this->retrieveSectionName($publication, $locale);
        $language = $submission->getData('locale');
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($submission);
        $authors = $this->retrieveAuthors($publication, $locale);

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

        return $submitter->getCountryLocalized();
    }

    protected function calculateDaysUntilStatusChange($submission)
    {
        $dateSubmitted = new DateTime($submission->getData('dateSubmitted'));
        $dateLastActivity = new DateTime($submission->getData('dateLastActivity'));
        $daysUntilStatusChange = $dateLastActivity->diff($dateSubmitted)->format('%a');

        return $daysUntilStatusChange;
    }

    protected function retrieveAuthors($publication, $locale)
    {
        $authors =  $publication->getData('authors');
        $submissionAuthors = [];

        foreach ($authors as $author) {
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
