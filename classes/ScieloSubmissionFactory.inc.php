<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmission');

class ScieloSubmissionFactory {
    
    public function createSubmission(int $submissionId, string $locale) : ScieloSubmission {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $publication = $submission->getCurrentPublication();

        $submissionTitle = $publication->getData('title', $locale);

        $scieloSubmission = new ScieloSubmission($submissionId, $submissionTitle, "", "", 0, "", [], "", "", "", "");

        return $scieloSubmission;
    }
}

?>