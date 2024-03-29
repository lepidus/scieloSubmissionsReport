<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class ScieloSubmissionsOJSReport extends ScieloSubmissionsReport
{
    public function getHeaders(): array
    {
        return [
            __('plugins.reports.scieloSubmissionsReport.header.submissionId'),
            __('submission.submissionTitle'),
            __('submission.submitter'),
            __('plugins.reports.scieloSubmissionsReport.header.submitterCountry'),
            __('common.dateSubmitted'),
            __('plugins.reports.scieloSubmissionsReport.header.daysChangeStatus'),
            __('plugins.reports.scieloSubmissionsReport.header.submissionStatus'),
            __('plugins.reports.scieloSubmissionsReport.header.journalEditors'),
            __('plugins.reports.scieloSubmissionsReport.header.sectionEditor'),
            __('submission.authors'),
            __('plugins.reports.scieloSubmissionsReport.header.section'),
            __('common.language'),
            __('plugins.reports.scieloSubmissionsReport.header.reviews'),
            __('plugins.reports.scieloSubmissionsReport.header.LastDecision'),
            __('plugins.reports.scieloSubmissionsReport.header.FinalDecision'),
            __('plugins.reports.scieloSubmissionsReport.header.finalDecisionDate'),
            __('plugins.reports.scieloSubmissionsReport.header.ReviewingTime'),
            __('plugins.reports.scieloSubmissionsReport.header.SubmissionAndFinalDecisionDateInterval')
        ];
    }

    protected function filterWithAverageReviewingTimeOnly()
    {
        $submissions = [];

        foreach ($this->submissions as $submission) {
            if (!empty($submission->getFinalDecision()) && $submission->hasReviews()) {
                $submissions[] = $submission;
            }
        }
        return $submissions;
    }
}
