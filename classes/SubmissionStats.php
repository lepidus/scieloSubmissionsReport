<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

class SubmissionStats
{
    private $abstractViews;
    private $pdfViews;

    public function __construct(int $abstractViews, int $pdfViews)
    {
        $this->abstractViews = $abstractViews;
        $this->pdfViews = $pdfViews;
    }

    public function getAbstractViews(): int
    {
        return $this->abstractViews;
    }

    public function getPdfViews(): int
    {
        return $this->pdfViews;
    }

    public function asRecord(): array
    {
        return [$this->abstractViews, $this->pdfViews];
    }
}
