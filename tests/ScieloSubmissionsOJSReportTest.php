<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloArticle');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsOJSReport');
import ('plugins.reports.scieloSubmissionsReport.tests.CSVFileUtils');

class ScieloSubmissionsOJSReportTest extends TestCase {
    
    private $report;
    private $sections = array("Biological Sciences", "Math", "Human Sciences");
    private $submissions;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $this->submissions = $this->createTestArticles();
        $this->report = new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
    }

    public function tearDown() : void {
        if (file_exists(($this->filePath))) 
            unlink($this->filePath);
    }

    private function generateCSV() : void {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    private function createTestArticles() : array {
        $submittedDatesForArticles = ["2021-04-21", "2021-03-06", "2020-11-8", "2020-11-8", "2020-11-8"];
        $finalDecisionDatesForArticles = ["2021-04-23", "2021-03-08", "2020-11-15", "", "2020-11-18"];
        $noReviews = ["", ""];
        
        $article1 = new ScieloArticle(1, "Title 1", "Paola Franchesca", "Brasil", $submittedDatesForArticles[0], 1, "Posted", array(new SubmissionAuthor("Paola Franchesca", "Italy", "University of Milan")), "Fashion Design", "en_US", "Accepted", $finalDecisionDatesForArticles[0], ["Jean Paul Cardin"], "Jean Paul Cardin", ["Accept", "See comments"], "Accept");
        $article2 = new ScieloArticle(2, "Titulo 2", "Pablo Giorgio", "Brasil", $submittedDatesForArticles[1], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDatesForArticles[1], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article3 = new ScieloArticle(3, "Titulo 3", "Pablo Giorgio", "Brasil", $submittedDatesForArticles[2], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDatesForArticles[2], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article4 = new ScieloArticle(4, "Titulo 4", "Pablo Giorgio", "Brasil", $submittedDatesForArticles[3], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "", $finalDecisionDatesForArticles[3], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article5 = new ScieloArticle(5, "Titulo 5", "Pablo Giorgio", "Brasil", $submittedDatesForArticles[4], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "", $finalDecisionDatesForArticles[4], ["Richard Feynman"], "Neil Tyson", $noReviews, "Accept");

        return [$article1, $article2, $article3, $article4, $article5];
    }
    
    public function testGeneratedCSVHeadersFromOJSSubmissions() {
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $csvFileUtils->readUTF8Bytes($csvFile);

        $firstLine = fgetcsv($csvFile);
        $expectedLine = [
            __("plugins.reports.scieloSubmissionsReport.header.submissionId"),
            __("submission.submissionTitle"),
            __("submission.submitter"),
            __("plugins.reports.scieloSubmissionsReport.header.submitterCountry"),
            __("common.dateSubmitted"),
            __("plugins.reports.scieloSubmissionsReport.header.daysChangeStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.submissionStatus"),
            __("plugins.reports.scieloSubmissionsReport.header.journalEditors"),
            __("plugins.reports.scieloSubmissionsReport.header.sectionEditor"),
            __("submission.authors"),
            __("plugins.reports.scieloSubmissionsReport.header.section"),
            __("common.language"),
            __("plugins.reports.scieloSubmissionsReport.header.reviews"),
            __("plugins.reports.scieloSubmissionsReport.header.LastDecision"),
            __("plugins.reports.scieloSubmissionsReport.header.FinalDecision"),
            __("plugins.reports.scieloSubmissionsReport.header.finalDecisionDate"),
            __("plugins.reports.scieloSubmissionsReport.header.ReviewingTime"),
            __("plugins.reports.scieloSubmissionsReport.header.SubmissionAndFinalDecisionDateInterval")
        ];

        fclose($csvFile);
        $this->assertEquals($expectedLine, $firstLine);
    }

    public function testAverageReviewingTime() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, [$this->submissions[0], $this->submissions[1]]);
        
        $expectedReviewingTime = 2;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeRoundedUp() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
        
        $expectedReviewingTime = 4;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeWhenASubmissionDoesNotHaveFinalDecision() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
        
        $expectedReviewingTime = 4;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeWhenASubmissionDoesNotHaveReviews() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
        
        $expectedReviewingTime = 4;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeEmptyArticles() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, []);
        
        $expectedReviewingTime = 0;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeWithInconsiderableArticles() : void {
        $report = new ScieloSubmissionsOJSReport($this->sections, [$this->submissions[3], $this->submissions[4]]);
        
        $expectedReviewingTime = 0;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testGeneratedCSVHasArticlesData() : void {
        $this->submissions = ($this->createTestArticles())[0];
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();

        $csvFileUtils->readUTF8Bytes($csvFile);
        fgetcsv($csvFile);
        $firstLine = fgetcsv($csvFile);
        fclose($csvFile);

        $expectedLine = ["1", "Title 1", "Paola Franchesca", "2021-04-21", "1", "Posted", "Jean Paul Cardin", "Jean Paul Cardin", "Paola Franchesca, Italy, University of Milan", "Fashion Design", "en_US", "Accept,See comments", "Accept", "Accepted", "2021-04-23", "2", "2"];
        $this->assertEquals($expectedLine, $firstLine);
    }

    public function testGeneratedCSVHasAverageReviewingTime() : void {
        $this->generateCSV();
        $csvRows = array_map('str_getcsv', file($this->filePath));
        
        $lastRow = $csvRows[sizeof($csvRows)-1];
        $penultimateCellFromLastRow = $lastRow[sizeof($lastRow)-2];
        $expectedAverageReviewingTime = 4;

        $this->assertEquals($expectedAverageReviewingTime, $penultimateCellFromLastRow);
    }
}