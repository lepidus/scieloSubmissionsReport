<?php

use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionsReport;

class ScieloSubmissionsOPSReport extends ScieloSubmissionsReport
{
    private $includeViews;

    public function __construct(array $sections, array $submissions, bool $includeViews = false)
    {
        parent::__construct($sections, $submissions);
        $this->includeViews = $includeViews;
    }

    public function getHeaders(): array
    {
        $headers = [
            __("plugins.reports.scieloSubmissionsReport.header.submissionId"),
            __("submission.submissionTitle"),
            __("submission.submitter"),
            __("plugins.reports.scieloSubmissionsReport.header.submitterCountry"),
            __("plugins.reports.scieloSubmissionsReport.header.submitterIsScieloJournal"),
            __("common.dateSubmitted"),
            __("plugins.reports.scieloSubmissionsReport.header.daysChangeStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.submissionStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.areaModerator"),
            __("plugins.reports.scieloSubmissionsReport.header.responsibles"),
            __("submission.authors"),
            __("plugins.reports.scieloSubmissionsReport.header.section"),
            __("common.language"),
            __("plugins.reports.scieloSubmissionsReport.header.publicationStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.publicationDOI"),
            __("submission.notes"),
            __("plugins.reports.scieloSubmissionsReport.header.FinalDecision"),
            __("plugins.reports.scieloSubmissionsReport.header.finalDecisionDate"),
            __("plugins.reports.scieloSubmissionsReport.header.ReviewingTime"),
            __("plugins.reports.scieloSubmissionsReport.header.SubmissionAndFinalDecisionDateInterval"),
        ];

        if ($this->includeViews) {
            $headers = array_merge($headers, [__("submission.abstractViews"), __("plugins.reports.scieloSubmissionsReport.header.pdfViews")]);
        }

        return $headers;
    }
}
