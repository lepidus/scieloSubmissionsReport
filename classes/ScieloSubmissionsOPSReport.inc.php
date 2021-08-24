<?php

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');

class ScieloSubmissionsOPSReport extends ScieloSubmissionsReport 
{

    protected function getHeaders(): array 
    {
        return [
            __("plugins.reports.scieloSubmissionsReport.header.submissionId"),
            __("submission.submissionTitle"),
            __("submission.submitter"),
            __("plugins.reports.scieloSubmissionsReport.header.submitterCountry"),
            __("common.dateSubmitted"),
            __("plugins.reports.scieloSubmissionsReport.header.daysChangeStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.submissionStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.areaModerator"),
            __("plugins.reports.scieloSubmissionsReport.header.moderators"),
            __("submission.authors"),
            __("plugins.reports.scieloSubmissionsReport.header.section"),
            __("common.language"),
            __("plugins.reports.scieloSubmissionsReport.header.publicationStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.publicationDOI"),
            __("submission.notes"),
            __("plugins.reports.scieloSubmissionsReport.header.FinalDecision"),
            __("plugins.reports.scieloSubmissionsReport.header.finalDecisionDate"),
            __("plugins.reports.scieloSubmissionsReport.header.ReviewingTime"),
            __("plugins.reports.scieloSubmissionsReport.header.SubmissionAndFinalDecisionDateInterval")
        ];
    }
}