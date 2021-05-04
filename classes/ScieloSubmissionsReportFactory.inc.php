<?php
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import('classes.journal.SectionDAO');

class ScieloSubmissionsReportFactory {

    public function createReport(array $sectionIds, string $locale) : ScieloSubmissionsReport {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach($sectionIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($locale);
        }

        return new ScieloSubmissionsReport($sections, []);
    }
}

?>