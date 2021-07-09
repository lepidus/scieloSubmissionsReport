<?php

import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticlesDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintsDAO');
import('classes.journal.SectionDAO');

class ScieloSubmissionsReportFactory
{
    public function createReport(string $application, int $contextId, array $sectionIds, ClosedDateInterval $submissionDateInterval = null, ClosedDateInterval $finalDecisionDateInterval = null, string $locale): ScieloSubmissionsReport
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach ($sectionIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($locale);
        }

        if ($application == 'ops') {
            $scieloSubmissionsDao = new ScieloPreprintsDAO();
        } elseif ($application == 'ojs') {
            $scieloSubmissionsDao = new ScieloArticlesDAO();
        }
        $submissionsIds = $scieloSubmissionsDao->getSubmissions($locale, $contextId, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);

        return new ScieloSubmissionsReport($sections, $submissionsIds);
    }
}
