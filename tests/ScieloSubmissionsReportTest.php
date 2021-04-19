<?php
use PHPUnit\Framework\TestCase;

final class ScieloSubmissionsReportTest extends TestCase {
    
    private $report;
    private $sections = array("Biological Sciences", "Math", "Human Sciences");
    private $submissions;
    private $UTF8Bytes;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $this->UTF8Bytes = chr(0xEF).chr(0xBB).chr(0xBF);
        $this->report = $this->createScieloSubmissionReport();
    }

    public function tearDown() : void {
        if (file_exists(($this->filePath))) 
            unlink($this->filePath);
    }

    private function createScieloSubmissionReport() {
        $this->submissions = (new ScieloSubmissionTest())->getTestSubmissions();
        return new ScieloSubmissionsReport($this->sections, $this->submissions);
    }

    private function createCSVReport() : void {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    public function testReportHasSections() : void {
        $this->assertEquals($this->sections, $this->report->getSections());
    }

    public function testReportHasScieloSubmissions() : void {
        $this->assertEquals($this->submissions, $this->report->getSubmissions());
    }

    public function testGeneratedCSVHasSubmissionsData() : void {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        fread($csvFile, 3);
        while (($line = fgetcsv($csvFile)) !== FALSE) {
            $expectedLine = ["1233","Rethinking linguistic relativity", "Atila Iamarino", "2013-09-06 19:07:02", '3', "Published", "Atila, Brasil, USP", "Biological Sciences", "en_US", "Accepted", "2013-09-14 22:00:00", '8', '8'];
            $this->assertEquals($expectedLine, $line);
        }
        fclose($csvFile);
    }

    public function testGeneratedCSVHasUTF8Bytes() : void {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $byteRead = fread($csvFile, 3);
        fclose($csvFile);
        
        $this->assertEquals($this->UTF8Bytes, $byteRead);
    }
    //testGeneratedCSVHasCommonHeaders
}