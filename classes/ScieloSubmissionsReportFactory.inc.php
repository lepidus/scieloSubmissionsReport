<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportDAO');
import('classes.journal.SectionDAO');

class ScieloSubmissionsReportFactory {

    public function createReport(int $contextId, array $sectionIds, string $locale) : ScieloSubmissionsReport {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach($sectionIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($locale);
        }
        
        $scieloSubmissionsReportDao = new ScieloSubmissionsReportDAO();
        $submissionsIds = $scieloSubmissionsReportDao->getSubmissions($contextId);
        
        return new ScieloSubmissionsReport($sections, $submissionsIds);
    }
}

?>