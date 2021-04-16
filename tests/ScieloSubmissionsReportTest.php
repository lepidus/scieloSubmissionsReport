<?php
use PHPUnit\Framework\TestCase;

final class ScieloSubmissionsReportTest extends TestCase {

    public function testReportHasSections() : void {
        $sections = array("Biological Sciences", "Math", "Human Sciences");
        $submissions = array();
        $report = new ScieloSubmissionsReport($sections, $submissions);
        $this->assertEquals($sections, $report->getSections());
    }

    public function testReportHasScieloSubmissions() : void {
        $sections = array();
        $submissions = (new ScieloSubmissionTest())->getTestSubmissions();
        $report = new ScieloSubmissionsReport($sections, $submissions);
        $this->assertEquals($submissions, $report->getSubmissions());
    }

}