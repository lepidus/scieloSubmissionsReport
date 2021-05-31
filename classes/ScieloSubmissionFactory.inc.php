<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');
import('classes.log.SubmissionEventLogEntry');

class ScieloSubmissionFactory {
    
    public function createSubmission(int $submissionId, string $locale) : ScieloSubmission {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $publication = $submission->getCurrentPublication();

        $submissionTitle = $publication->getData('title', $locale);
        $submitter = $this->retrieveSubmitter($submissionId);
        $dateSubmitted = $submission->getData('dateSubmitted');
        $status = __($submission->getStatusKey());

        $scieloSubmission = new ScieloSubmission($submissionId, $submissionTitle, $submitter, $dateSubmitted, 0, $status, [], "", "", "", "");

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

}

?>