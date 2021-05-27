<?php
use PHPUnit\Framework\TestCase;
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloPreprint');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReport');
import ('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsOPSReport');
import ('plugins.reports.scieloSubmissionsReport.tests.CSVFileUtils');

class ScieloSubmissionsOPSReportTest extends TestCase {

    private $report;
    private $sections = array("Biological Sciences", "Math", "Human Sciences");
    private $submissions;
    private $filePath = "/tmp/test.csv";

    public function setUp() : void {
        $this->submissions = $this->createTestPreprints();
        $this->report = new ScieloSubmissionsOPSReport($this->sections, $this->submissions);
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

    private function createTestPreprints() : array {
        $submittedDateForPreprint1 = "2021-04-21";
        $finalDecisionDateForPreprint1 = "2021-04-23";
        $submittedDateForPreprint2 = "2021-03-06";
        $finalDecisionDateForPreprint2 = "2021-03-08";
        $submittedDateForPreprint3 = "2020-11-8";
        $finalDecisionDateForPreprint3 = "2020-11-15";

        $preprint1 = new ScieloPreprint(1, "Title 1", "Paola Franchesca", $submittedDateForPreprint1, 1, "Posted", array(new SubmissionAuthor("Paola Franchesca", "Italy", "University of Milan")), "Fashion Design", "en_US", "Accepted", $finalDecisionDateForPreprint1, ["Jean Paul Cardin"], "Jean Paul Cardin", "Sent to journal publication", "No DOI informed", [""]);
        $preprint2 = new ScieloPreprint(2, "Titulo 2", "Pablo Giorgio", $submittedDateForPreprint2, 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDateForPreprint2, ["Richard Feynman"], "Neil Tyson", "Sent to journal publication", "No DOI informed", [""]);
        $preprint3 = new ScieloPreprint(3, "Titulo 3", "Pablo Giorgio", $submittedDateForPreprint3, 6, "Posted", array(new SubmissionAuthor("Atila", "Brazil", "USP")), "Biological", "en_US", "Accepted", $finalDecisionDateForPreprint3, ["Richard Feynman"], "Neil Tyson", "Sent to journal publication", "No DOI informed", [""]);

        return [$preprint1, $preprint2, $preprint3];
    }
    
    public function testGeneratedCSVHeadersFromOPSSubmissions() : void {
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();
        $csvFileUtils->readUTF8Bytes($csvFile);

        $firstLine = fgetcsv($csvFile);
        $expectedLine = ["ID da submissão","Título da Submissão","Submetido por","Data de submissão","Dias até mudança de status","Estado da submissão","Moderador de área","Moderadores","Autores","Seção","Idioma","Estado de publicação","DOI da publicação","Notas","Decisão final","Data da decisão final","Tempo em avaliação","Tempo entre submissão e decisão final"];
        fclose($csvFile);

        $this->assertEquals($expectedLine, $firstLine);
    }

    public function testAverageReviewingTime() : void {
        $testPreprints = $this->createTestPreprints();
        $report = new ScieloSubmissionsOPSReport($this->sections, [$testPreprints[0], $testPreprints[1]]);
        
        $expectedReviewingTime = 2;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeRoundedUp() : void {
        $testPreprints = $this->createTestPreprints();
        $report = new ScieloSubmissionsOPSReport($this->sections, $testPreprints);
        
        $expectedReviewingTime = 4;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testAverageReviewingTimeEmptyPreprints() : void {
        $report = new ScieloSubmissionsOPSReport($this->sections, []);
        
        $expectedReviewingTime = 0;
        $this->assertEquals($expectedReviewingTime, $report->getAverageReviewingTime());
    }

    public function testGeneratedCSVHasAverageReviewingTime() : void {
        $this->generateCSV();
        $csvRows = array_map('str_getcsv', file($this->filePath));
        
        $lastRow = $csvRows[sizeof($csvRows)-1];
        $penultimateCellFromLastRow = $lastRow[sizeof($lastRow)-2];
        $expectedAverageReviewingTime = 4;

        $this->assertEquals($expectedAverageReviewingTime, $penultimateCellFromLastRow);
    }

    public function testGeneratedCSVHasPreprintData() : void {
        $this->submissions = ($this->createTestPreprints())[0];
        $this->generateCSV();
        $csvFile = fopen($this->filePath, 'r');
        $csvFileUtils = new CSVFileUtils();

        $csvFileUtils->readUTF8Bytes($csvFile);
        fgetcsv($csvFile);
        $firstLine = fgetcsv($csvFile);
        fclose($csvFile);

        $expectedLine = ["1", "Title 1", "Paola Franchesca", "2021-04-21", "1", "Posted", "Jean Paul Cardin", "Jean Paul Cardin", "Paola Franchesca, Italy, University of Milan", "Fashion Design", "en_US", "Sent to journal publication", "No DOI informed", "Note:", "Accepted", "2021-04-23", "2", "2"];
        $this->assertEquals($expectedLine, $firstLine);
    }
}