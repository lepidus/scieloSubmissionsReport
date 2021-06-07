<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportDAO');
import('classes.journal.SectionDAO');

class ScieloSubmissionsReportFactory {

    public function createReport(string $application, int $contextId, array $sectionIds, string $startSubmissionDateInterval, string $endSubmissionDateInterval, string $startFinalDecisionDateInterval, string $endFinalDecisionDateInterval, string $locale) : ScieloSubmissionsReport {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach($sectionIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($locale);
        }
        
        $submissionDateInterval = (!empty($startSubmissionDateInterval) ? new ClosedDateInterval($startSubmissionDateInterval, $endSubmissionDateInterval) : null);
        
        $finalDecisionDateInterval = (!empty($startFinalDecisionDateInterval) ? new ClosedDateInterval($startFinalDecisionDateInterval, $endFinalDecisionDateInterval) : null);

        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        $submissionsIds = $scieloSubmissionsReportDao->getSubmissions($application, $locale, $contextId, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);
        
        return new ScieloSubmissionsReport($sections, $submissionsIds);
    }
}

?>