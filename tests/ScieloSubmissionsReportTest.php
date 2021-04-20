<?php
use PHPUnit\Framework\TestCase;

class ScieloSubmissionsReportTest extends TestCase {
    
    private $report;
    protected $sections = array("Biological Sciences", "Math", "Human Sciences");
    protected $submissions;
    protected $UTF8Bytes;
    protected $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $this->UTF8Bytes = chr(0xEF).chr(0xBB).chr(0xBF);
        $this->report = $this->createScieloSubmissionReport();
    }

    public function tearDown() : void {
        if (file_exists(($this->filePath))) 
            unlink($this->filePath);
    }

    protected function createScieloSubmissionReport() {
        $this->submissions = (new ScieloSubmissionTest())->getTestSubmissions();
        return new ScieloSubmissionsReport($this->sections, $this->submissions);
    }

    protected function createCSVReport() : void {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    protected function readUTF8Bytes($csvFile) {
        return fread($csvFile, strlen($this->UTF8Bytes));
    }

    public function testReportHasSections() : void {
        $this->assertEquals($this->sections, $this->report->getSections());
    }

    public function testReportHasScieloSubmissions() : void {
        $this->assertEquals($this->submissions, $this->report->getSubmissions());
    }

    public function testGeneratedCSVHasCommonSubmissionsData() : void {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $this->readUTF8Bytes($csvFile);
        $line = fgetcsv($csvFile);
        while (($line = fgetcsv($csvFile)) !== FALSE) {
            $expectedLine = ["1233","Rethinking linguistic relativity", "Atila Iamarino", "2013-09-06 19:07:02", '3', "Published", "Atila, Brasil, USP", "Biological Sciences", "en_US", "Accepted", "2013-09-14 22:00:00", '8', '8'];
            $this->assertEquals($expectedLine, $line);
        }
        fclose($csvFile);
    }

    public function testGeneratedCSVHasUTF8Bytes() : void {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $byteRead = $this->readUTF8Bytes($csvFile);
        fclose($csvFile);
        
        $this->assertEquals($this->UTF8Bytes, $byteRead);
    }
}