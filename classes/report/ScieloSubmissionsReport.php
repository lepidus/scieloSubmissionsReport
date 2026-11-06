<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes\report;

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
        return [
            'ID da submissão',
            'Título da Submissão',
            'Submetido por',
            'Data de submissão',
            'Dias até mudança de status',
            'Estado da submissão',
            'Editores da Revista',
            'Editor de Seção',
            'Autores',
            'Seção',
            'Idioma',
            'Avaliações',
            'Última decisão',
            'Decisão final',
            'Data da decisão final',
            'Tempo em avaliação',
            'Tempo entre submissão e decisão final'
        ];
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
        $this->writeCSVRow($fileDescriptor, $this->getHeaders());

        foreach ($this->submissions as $submission) {
            $this->writeCSVRow($fileDescriptor, $submission->asRecord());
        }

        $blankLine = ['', '', ''];
        $this->writeCSVRow($fileDescriptor, $blankLine);
        $this->writeCSVRow($fileDescriptor, $this->getSecondHeaders());
        $sections = implode(',', $this->getSections());
        $this->writeCSVRow($fileDescriptor, [$this->getAverageReviewingTime(), $sections]);
    }

    private function writeCSVRow($fileDescriptor, array $row): void
    {
        fputcsv($fileDescriptor, array_map([$this, 'neutralizeSpreadsheetFormula'], $row));
    }

    /**
     * Prefixes with an apostrophe the text cells that a spreadsheet would
     * evaluate as a formula, such as titles and names typed by users.
     */
    private function neutralizeSpreadsheetFormula($value)
    {
        if (!is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
