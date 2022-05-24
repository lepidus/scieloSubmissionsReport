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
    private $application;
    private $locale;
    private $contextId;
    private $sectionsIds;
    private $submissionDateInterval;
    private $finalDecisionDateInterval;
    private $includeViews;

    public function __construct(string $application, int $contextId, array $sectionsIds, ClosedDateInterval $submissionDateInterval = null, ClosedDateInterval $finalDecisionDateInterval = null, string $locale, bool $includeViews)
    {
        $this->application = $application;
        $this->locale = $locale;
        $this->contextId = $contextId;
        $this->sectionsIds = $sectionsIds;
        $this->submissionDateInterval = $submissionDateInterval;
        $this->finalDecisionDateInterval = $finalDecisionDateInterval;
        $this->includeViews = $includeViews;
    }
    
    public function createReport(): ScieloSubmissionsReport
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sections = [];

        foreach ($this->sectionsIds as $sectionId) {
            $sections[$sectionId] = ($sectionDao->getById($sectionId))->getTitle($this->locale);
        }

        if ($this->application == 'ops') {
            $submissionsDao = new ScieloPreprintsDAO();
            $submissionFactory = new ScieloPreprintFactory($this->includeViews);
            $scieloSubmissions = $this->getScieloSubmissions($submissionsDao, $submissionFactory);
            return new ScieloSubmissionsOPSReport($sections, $scieloSubmissions, $this->includeViews);
        }
        elseif ($this->application == 'ojs') {
            $submissionsDao = new ScieloArticlesDAO();
            $submissionFactory = new ScieloArticleFactory();
            $scieloSubmissions = $this->getScieloSubmissions($submissionsDao, $submissionFactory);
            return new ScieloSubmissionsOJSReport($sections, $scieloSubmissions);
        }
    }

    private function getScieloSubmissions($submissionsDao, $submissionFactory): array 
    {
        $submissionsIds = $submissionsDao->getSubmissions($this->locale, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval);
        $scieloSubmissions = [];

        foreach($submissionsIds as $submissionId) {
            $scieloSubmissions[] = $submissionFactory->createSubmission($submissionId, $this->locale);
        }

        return $scieloSubmissions;
    }

}
