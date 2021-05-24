<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Section');
import('classes.journal.SectionDAO');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.submission.SubmissionDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class ScieloSubmissionsReportFactoryTest extends DatabaseTestCase {

    private $locale = 'en_US';
    private $contextId = 1;
    private $reportFactory;

    public function setUp() : void {
        parent::setUp();
        $this->reportFactory = new ScieloSubmissionsReportFactory();
    }

    protected function getAffectedTables() {
		return array("sections", "section_settings", "submissions", "submission_settings", "publications", "publication_settings");
	}

    private function createTestSections() : array {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section1 = new Section();
        $section2 = new Section();
        $section1->setTitle("Biological Sciences", $this->locale);
        $section2->setTitle("Math", $this->locale);
        $firstSectionId = $sectionDao->insertObject($section1);
        $secondSectionId = $sectionDao->insertObject($section2);

        return [$firstSectionId, $secondSectionId];
    }

    private function createTestSubmissions() : array {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        
		$firstSubmission = new Submission();
        $secondSubmission = new Submission();
        $firstSubmission->setData('contextId', $this->contextId);
        $secondSubmission->setData('contextId', $this->contextId);
        
        $firstSubmissionId = $submissionDao->insertObject($firstSubmission);
        $secondSubmissionId = $submissionDao->insertObject($secondSubmission);

        return [$firstSubmissionId, $secondSubmissionId];
    }

	private function createTestPublications($submissionsIds, $sectionsIds) {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

        $firstPublication = new Publication();
        $firstPublication->setData('submissionId', $submissionsIds[0]);
        $firstPublication->setData('sectionId', $sectionsIds[0]);
        $secondPublication = new Publication();
        $secondPublication->setData('submissionId', $submissionsIds[1]);
        $secondPublication->setData('sectionId', $sectionsIds[1]);

        $firstPublicationId = $publicationDao->insertObject($firstPublication);
        $secondPublicationId = $publicationDao->insertObject($secondPublication);

		return [$firstPublicationId, $secondPublicationId];
	}

    public function testReportHasSections() : void {
        $testSections = $this->createTestSections();
        
        $report = $this->reportFactory->createReport($this->contextId, $testSections, $this->locale);

        $expectedSections = [$testSections[0] => "Biological Sciences", $testSections[1] => "Math"];
        $this->assertEquals($expectedSections, $report->getSections());
    }
    
    public function testReportHasSubmissions() {
		$testSections = $this->createTestSections();
        $submissionsIds = $this->createTestSubmissions();
        $publicationsIds = $this->createTestPublications($submissionsIds, $testSections);

        $report = $this->reportFactory->createReport($this->contextId, $testSections, $this->locale);

        $this->assertEquals($submissionsIds, $report->getSubmissions());
    }

    public function testReportFilterBySections() {
        $testSections = $this->createTestSections();
        $submissionsIds = $this->createTestSubmissions();
        $publicationsIds = $this->createTestPublications($submissionsIds, $testSections);
        
		$report = $this->reportFactory->createReport($this->contextId, [$testSections[0]], $this->locale);

		$expectedSubmissions = [$submissionsIds[0]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterBySubmissionDate() {
        return true;
    }
    
    public function testReportFilterByFinalDecisionDate() {
        return true;
    }
}

?>