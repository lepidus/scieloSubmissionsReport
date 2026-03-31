<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class ScieloSubmissionsReport
{
    private $sections;
    protected $submissions;
    private $UTF8_BOM;

    public function __construct(array $sections, array $submissions)
    {
        $this->sections = $sections;
        $this->submissions = $submissions;
        $this->UTF8_BOM = chr(0xEF) . chr(0xBB) . chr(0xBF);
    }

    public function getSections(): array
    {
        return $this->sections;
    }

    public function getSubmissions(): array
    {
        return $this->submissions;
    }

    protected function filterWithAverageReviewingTimeOnly()
    {
        $submissions = [];

        foreach ($this->submissions as $submission) {
            if (!empty($submission->getFinalDecision())) {
                $submissions[] = $submission;
            }
        }
        return $submissions;
    }

    public function getAverageReviewingTime(): int
    {
        $submissionsToUse = $this->filterWithAverageReviewingTimeOnly();
        if (empty($submissionsToUse)) {
            return 0;
        }

        $totalReviewingTime = 0;

        foreach ($submissionsToUse as $submission) {
            $totalReviewingTime += $submission->getTimeUnderReview();
        }

        return round($totalReviewingTime / count($submissionsToUse));
    }

    public function getHeaders(): array
    {
        return [];
    }

    private function getSecondHeaders(): array
    {
        return [
            __('plugins.reports.scieloSubmissionsReport.header.AverageReviewingTime'),
            __('section.sections')
        ];
    }

    public function buildCSV($fileDescriptor): void
    {
        fprintf($fileDescriptor, $this->UTF8_BOM);
        fputcsv($fileDescriptor, $this->getHeaders());

        foreach ($this->submissions as $submission) {
            fputcsv($fileDescriptor, $submission->asRecord());
        }

        $blankLine = ['', '', ''];
        fputcsv($fileDescriptor, $blankLine);
        fputcsv($fileDescriptor, $this->getSecondHeaders());
        $sections = implode(',', $this->getSections());
        fputcsv($fileDescriptor, [$this->getAverageReviewingTime(), $sections]);
    }
}
