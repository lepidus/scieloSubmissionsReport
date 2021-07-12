<?php

import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsOJSReport');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsOPSReport');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticlesDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintsDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprintFactory');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloArticleFactory');
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
            return $this->buildReportForOPS($locale, $contextId, $sections, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);
        }
        elseif ($application == 'ojs') {
            return $this->buildReportForOJS($locale, $contextId, $sections, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);
        }
    }

    private function buildReportForOJS($locale, $contextId, $sections, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval): ScieloSubmissionsOJSReport 
    {
        $scieloArticlesDao = new ScieloArticlesDAO();
        $scieloArticleFactory = new ScieloArticleFactory();

        $submissionsIds = $scieloArticlesDao->getSubmissions($locale, $contextId, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);
        $scieloSubmissions = [];

        foreach($submissionsIds as $submissionId) {
            $scieloSubmissions[] = $scieloArticleFactory->createSubmission($submissionId, $locale);
        }

        return new ScieloSubmissionsOJSReport($sections, $scieloSubmissions);
    }
    

    private function buildReportForOPS($locale, $contextId, $sections, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval): ScieloSubmissionsOPSReport 
    {
        $scieloPreprintsDao = new ScieloPreprintsDAO();
        $scieloPreprintFactory = new ScieloPreprintFactory();

        $submissionsIds = $scieloPreprintsDao->getSubmissions($locale, $contextId, $sectionIds, $submissionDateInterval, $finalDecisionDateInterval);
        $scieloSubmissions = [];

        foreach($submissionsIds as $submissionId) {
            $scieloSubmissions[] = $scieloPreprintFactory->createSubmission($submissionId, $locale);
        }

        return new ScieloSubmissionsOPSReport($sections, $scieloSubmissions);
    }
}
