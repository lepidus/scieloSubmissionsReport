<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportDAO');
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

        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        list($finalDecision, $finalDecisionDate) = $scieloSubmissionsReportDao->getFinalDecisionWithDate($application, $submissionId, $locale);

        $scieloSubmission = new ScieloSubmission($submissionId, $submissionTitle, $submitter, $dateSubmitted, $daysUntilStatusChange, $status, $authors, $sectionName, $language, $finalDecision, $finalDecisionDate);

        return $scieloSubmission;
    }

    private function retrieveSubmitter($submissionId) {
        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        $userId = $scieloSubmissionsReportDao->getIdSubmitterUser($submissionId);

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