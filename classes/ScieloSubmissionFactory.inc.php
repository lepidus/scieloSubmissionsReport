<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsDAO');
import('classes.log.SubmissionEventLogEntry');

class ScieloSubmissionFactory {
    
    public function createSubmission(string $application, int $submissionId, string $locale) : ScieloSubmission {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $publication = $submission->getCurrentPublication();

        $submissionTitle = $publication->getData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $status = __($submission->getStatusKey());
        $sectionId = $publication->getData('sectionId');
        $section = DAORegistry::getDAO('SectionDAO')->getById($sectionId);
        $sectionName = $section->getTitle($locale);
        $language = $submission->getData('locale');
        $daysUntilStatusChange = $this->calculateDaysUntilStatusChange($submission);
        $authors = $this->retrieveAuthors($publication, $locale);

        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $finalDecisionWithDate = $scieloSubmissionsDao->getFinalDecisionWithDate($application, $submissionId, $locale);

        $finalDecision = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDecision()) : "";
        $finalDecisionDate = (!is_null($finalDecisionWithDate)) ? ($finalDecisionWithDate->getDateDecided()) : "";

        if ($application == 'ops') {
            $publicationStatus = $publication->getData('status');
            $publicationDOI = $scieloSubmissionsDao->getPublicationDOIBySubmission($submission);
            list($sectionModerator, $moderators) = $scieloSubmissionsDao->getAllModeratorsBySubmissionId($submissionId);
            $notes = $scieloSubmissionsDao->getSubmissionNotes($submissionId);

            $scieloPreprint = new ScieloPreprint(
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
            return $scieloPreprint;
        }
        if ($application == 'ojs') {
            list($editors, $sectionEditor) = $scieloSubmissionsDao->getEditors($submissionId);
            $reviews = array();
            $lastDecision = $scieloSubmissionsDao->getLastDecision($submissionId);

            $scieloArticle = new ScieloArticle(
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
            return $scieloArticle;
        }

        $scieloSubmission = new ScieloSubmission($submissionId, $submissionTitle, $submitter, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $sectionName, $language, $finalDecision, $finalDecisionDate);
        return $scieloSubmission;
    }

    private function retrieveSubmitter($submissionId) {
        $scieloSubmissionsDao = new ScieloSubmissionsDAO();
        $userId = $scieloSubmissionsDao->getIdSubmitterUser($submissionId);

        if(is_null($userId)) return "";
        
        $userDao = DAORegistry::getDAO('UserDAO');
        $submitter = $userDao->getById($userId);
        
        return $submitter->getFullName();
    }

    private function calculateDaysUntilStatusChange($submission) {
        $dateSubmitted = new DateTime($submission->getData('dateSubmitted'));
        $dateLastActivity = new DateTime($submission->getData('dateLastActivity'));
        $daysUntilStatusChange = $dateLastActivity->diff($dateSubmitted)->format('%a');

        return $daysUntilStatusChange;
    }

    private function retrieveAuthors($publication, $locale){
        $authors =  $publication->getData('authors');
        $submissionAuthors = [];

        foreach ($authors as $author){
            $fullName = $author->getFullName($locale);
            $country = $author->getCountryLocalized();
            $affiliation = $author->getAffiliation($locale);
            $submissionAuthors[] = new SubmissionAuthor($fullName, $country, $affiliation);
        }
    
        return $submissionAuthors;
    }

}

?>