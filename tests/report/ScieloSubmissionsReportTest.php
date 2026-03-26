<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests\report;

use APP\plugins\reports\scieloSubmissionsReport\classes\report\ScieloSubmissionsReport;
use APP\plugins\reports\scieloSubmissionsReport\tests\CSVFileUtils;
use PHPUnit\Framework\TestCase;

class ScieloSubmissionsReportTest extends TestCase
{
    private $report;
    private $sections = ['Biological Sciences', 'Math', 'Human Sciences'];
    private $filePath = '/tmp/test.csv';

    public function setUp(): void
    {
        $this->report = new ScieloSubmissionsReport($this->sections, []);
    }

    public function tearDown(): void
    {
        if (file_exists(($this->filePath))) {
            unlink($this->filePath);
        }
    }

    protected function createCSVReport(): void
    {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    public function testReportHasSections(): void
    {
        $this->assertEquals($this->sections, $this->report->getSections());
    }

    public function testGeneratedCsvHasUtf8Bytes(): void
    {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $byteRead = $csvFileUtils->readUTF8Bytes($csvFile);
        fclose($csvFile);

        $this->assertEquals($csvFileUtils->getExpectedUTF8BOM(), $byteRead);
    }

    public function testGeneratedCsvHasSections(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $expectedSections = implode(',', $this->sections);
        $lastRow = $csvRows[sizeof($csvRows) - 1];
        $lastCellFromLastRow = $lastRow[sizeof($lastRow) - 1];

        $this->assertEquals($expectedSections, $lastCellFromLastRow);
    }

    public function testGeneratedCsvHasSecondHeaders(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $penultimateRow = $csvRows[sizeof($csvRows) - 2];
        $expectedPenultimateRow = [__('plugins.reports.scieloSubmissionsReport.header.AverageReviewingTime'), __('section.sections')];

        $this->assertEquals($expectedPenultimateRow, $penultimateRow);
    }
}
