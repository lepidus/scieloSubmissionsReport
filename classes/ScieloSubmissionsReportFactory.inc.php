<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportDAO');
import('classes.journal.SectionDAO');

class ScieloSubmissionsReportFactory {

    public function createReport(int $contextId, array $sectionIds, string $startSubmissionDateInterval, string $endSubmissionDateInterval, string $startFinalDecisionDateInterval, string $endFinalDecisionDateInterval, string $locale) : ScieloSubmissionsReport {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach($sectionIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($locale);
        }
        
        if(!empty($startSubmissionDateInterval) && !empty($endSubmissionDateInterval)) {
            $startSubmissionDateInterval .= " 00:00:00";
            $endSubmissionDateInterval .= " 23:59:59";
        }

        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        $submissionsIds = $scieloSubmissionsReportDao->getSubmissions($contextId, $sectionIds, $startSubmissionDateInterval, $endSubmissionDateInterval);
        
        return new ScieloSubmissionsReport($sections, $submissionsIds);
    }
}

?>