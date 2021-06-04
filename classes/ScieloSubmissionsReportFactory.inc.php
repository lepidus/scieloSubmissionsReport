<?php
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
        
        if(!empty($startSubmissionDateInterval) && !empty($endSubmissionDateInterval)) {
            $startSubmissionDateInterval .= " 00:00:00";
            $endSubmissionDateInterval .= " 23:59:59";
        }

        $startFinalDecisionDateInterval = (!empty($startFinalDecisionDateInterval) ? new DateTime($startFinalDecisionDateInterval .' 00:00:00') : null);
        $endFinalDecisionDateInterval = (!empty($endFinalDecisionDateInterval) ? new DateTime($endFinalDecisionDateInterval .' 23:59:59') : null);

        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        $submissionsIds = $scieloSubmissionsReportDao->getSubmissions($application, $locale, $contextId, $sectionIds, $startSubmissionDateInterval, $endSubmissionDateInterval, $startFinalDecisionDateInterval, $endFinalDecisionDateInterval);
        
        return new ScieloSubmissionsReport($sections, $submissionsIds);
    }
}

?>