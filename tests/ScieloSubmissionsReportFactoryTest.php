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
    private $sectionsIds;
    private $submissionsIds;
    private $publicationsIds;
    private $startSubmissionDateInterval = '';
    private $endSubmissionDateInterval = '';

    public function setUp() : void {
        parent::setUp();
        $this->reportFactory = new ScieloSubmissionsReportFactory();
        $this->sectionsIds = $this->createTestSections();
        $this->submissionsIds = $this->createTestSubmissions();
        $this->publicationsIds = $this->createTestPublications($this->submissionsIds, $this->sectionsIds);
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
        $firstSubmission->setData('contextId', $this->contextId);
        $firstSubmission->setData('dateSubmitted', '2021-05-21 11:56:37');
        
        $secondSubmission = new Submission();
        $secondSubmission->setData('contextId', $this->contextId);
        $secondSubmission->setData('dateSubmitted', '2021-05-29 12:58:29');
        
        $thirdSubmission = new Submission();
        $thirdSubmission->setData('contextId', $this->contextId);
        $thirdSubmission->setData('dateSubmitted', '2021-06-14 04:30:08');
        
        $fourthSubmission = new Submission();
        $fourthSubmission->setData('contextId', $this->contextId);
        $fourthSubmission->setData('dateSubmitted', '2021-07-08 18:37:12');

        
        $firstSubmissionId = $submissionDao->insertObject($firstSubmission);
        $secondSubmissionId = $submissionDao->insertObject($secondSubmission);
        $thirdSubmissionId = $submissionDao->insertObject($thirdSubmission);
        $fourthSubmissionId = $submissionDao->insertObject($fourthSubmission);

        return [$firstSubmissionId, $secondSubmissionId, $thirdSubmissionId, $fourthSubmissionId];
    }

	private function createTestPublications($submissionsIds, $sectionsIds) : array {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

        $firstPublication = new Publication();
        $firstPublication->setData('submissionId', $submissionsIds[0]);
        $firstPublication->setData('sectionId', $sectionsIds[0]);

        $secondPublication = new Publication();
        $secondPublication->setData('submissionId', $submissionsIds[1]);
        $secondPublication->setData('sectionId', $sectionsIds[1]);

        $thirdPublication = new Publication();
        $thirdPublication->setData('submissionId', $submissionsIds[2]);
        $thirdPublication->setData('sectionId', $sectionsIds[1]);

        $fourthPublication = new Publication();
        $fourthPublication->setData('submissionId', $submissionsIds[3]);
        $fourthPublication->setData('sectionId', $sectionsIds[1]);

        $firstPublicationId = $publicationDao->insertObject($firstPublication);
        $secondPublicationId = $publicationDao->insertObject($secondPublication);
        $thirdPublicationId = $publicationDao->insertObject($thirdPublication);
        $fourthPublicationId = $publicationDao->insertObject($fourthPublication);

		return [$firstPublicationId, $secondPublicationId, $thirdPublicationId, $fourthPublicationId];
	}

    public function testReportHasSections() : void {
        $sectionsIds = $this->createTestSections();
        
        $report = $this->reportFactory->createReport($this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->locale);

        $expectedSections = [$this->sectionsIds[0] => "Biological Sciences", $this->sectionsIds[1] => "Math"];
        $this->assertEquals($expectedSections, $report->getSections());
    }
    
    public function testReportHasSubmissions() {
		$report = $this->reportFactory->createReport($this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->locale);

        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportFilterBySections() {
        $report = $this->reportFactory->createReport($this->contextId, [$this->sectionsIds[0]], $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->locale);

		$expectedSubmissions = [$this->submissionsIds[0]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterBySubmissionDate() {
		$this->startSubmissionDateInterval = '2021-05-23';
        $this->endSubmissionDateInterval = '2021-07-01';
        $report = $this->reportFactory->createReport($this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[1], $this->submissionsIds[2]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterByFinalDecisionDate() {
        return true;
    }
}

?>