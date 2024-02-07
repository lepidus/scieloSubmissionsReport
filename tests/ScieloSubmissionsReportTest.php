<?php

use PHPUnit\Framework\TestCase;

import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import('plugins.reports.scieloSubmissionsReport.tests.CSVFileUtils');

class ScieloSubmissionsReportTest extends TestCase
{
    private $report;
    private $sections = array("Biological Sciences", "Math", "Human Sciences");
    private $filePath = "/tmp/test.csv";

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

    public function testGeneratedCSVHasUTF8Bytes(): void
    {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $byteRead = $csvFileUtils->readUTF8Bytes($csvFile);
        fclose($csvFile);

        $this->assertEquals($csvFileUtils->getExpectedUTF8BOM(), $byteRead);
    }

    public function testGeneratedCSVHasSections(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $expectedSections = implode(",", $this->sections);
        $lastRow = $csvRows[sizeof($csvRows) - 1];
        $lastCellFromLastRow = $lastRow[sizeof($lastRow) - 1];

        $this->assertEquals($expectedSections, $lastCellFromLastRow);
    }

    public function testGeneratedCSVHasSecondHeaders(): void
    {
        $this->createCSVReport();
        $csvRows = array_map('str_getcsv', file($this->filePath));

        $penultimateRow = $csvRows[sizeof($csvRows) - 2];
        $expectedPenultimateRow = [__("plugins.reports.scieloSubmissionsReport.header.AverageReviewingTime"), __("section.sections")];

        $this->assertEquals($expectedPenultimateRow, $penultimateRow);
    }
}
