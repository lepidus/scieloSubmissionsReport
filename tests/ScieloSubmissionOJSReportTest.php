<?php
use PHPUnit\Framework\TestCase;

class ScieloSubmissionsOJSReportTest extends TestCase {
    
    private $report;
    private $sections = array("Biological Sciences", "Math", "Human Sciences");
    private $submissions;
    private $expected_UTF8_BOM;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $this->expected_UTF8_BOM = chr(0xEF).chr(0xBB).chr(0xBF);
        $this->report = $this->createScieloSubmissionReport();
    }

    private function createScieloSubmissionReport() : ScieloSubmissionsOJSReport {
        $this->submissions = $this->createTestArticles();
        return new ScieloSubmissionsOJSReport($this->sections, $this->submissions);
    }

    private function createCSVReport() : void {
        $csvFile = fopen($this->filePath, 'wt');
        $this->report->buildCSV($csvFile);
        fclose($csvFile);
    }

    private function readUTF8Bytes($csvFile) : string {
        return fread($csvFile, strlen($this->expected_UTF8_BOM));
    }

    private function createTestArticles() : array {
        $submittedDatesForArticles = ["2021-04-21", "2021-03-06", "2020-11-8", "2020-11-8", "2020-11-8"];
        $finalDecisionDatesForArticles = ["2021-04-23", "2021-03-08", "2020-11-15", "", "2020-11-18"];
        $noReviews = ["", ""];
        
        $article1 = new ScieloArticle(1, "Title 1", "Paola Franchesca", $submittedDatesForArticles[0], 1, "Posted", array(new SubmissionAuthor("Paola Franchesca", "Italy", "University of Milan")), "Fashion Design", "en_US", "Accepted", $finalDecisionDatesForArticles[0], ["Jean Paul Cardin"], "Jean Paul Cardin", ["Accept", "See comments"], "Accept");
        $article2 = new ScieloArticle(2, "Titulo 2", "Pablo Giorgio", $submittedDatesForArticles[1], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDatesForArticles[1], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article3 = new ScieloArticle(3, "Titulo 3", "Pablo Giorgio", $submittedDatesForArticles[2], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDatesForArticles[2], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article4 = new ScieloArticle(4, "Titulo 4", "Pablo Giorgio", $submittedDatesForArticles[3], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "", $finalDecisionDatesForArticles[3], ["Richard Feynman"], "Neil Tyson", ["Accept", "See comments"], "Accept");
        $article5 = new ScieloArticle(5, "Titulo 5", "Pablo Giorgio", $submittedDatesForArticles[4], 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "", $finalDecisionDatesForArticles[4], ["Richard Feynman"], "Neil Tyson", $noReviews, "Accept");

        return [$article1, $article2, $article3, $article4, $article5];
    }
    
    public function testGeneratedCSVHeadersFromOJSSubmissions() {
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $this->readUTF8Bytes($csvFile);

        $firstLine = fgetcsv($csvFile);
        $expectedLine = ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Editores da Revista","Editor de Seção","Autores","Seção","Idioma","Avaliações","Última decisão", "Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];

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
        $this->createCSVReport();
        $csvFile = fopen($this->filePath, 'r');
        $this->readUTF8Bytes($csvFile);
        fgetcsv($csvFile);
        $firstLine = fgetcsv($csvFile);
        fclose($csvFile);

        $expectedLine = ["1", "Title 1", "Paola Franchesca", "2021-04-21", "1", "Posted", "Jean Paul Cardin", "Jean Paul Cardin", "Paola Franchesca, Italy, University of Milan", "Fashion Design", "en_US", "Accept,See comments", "Accept", "Accepted", "2021-04-23", "2", "2"];
        $this->assertEquals($expectedLine, $firstLine);
    }
}