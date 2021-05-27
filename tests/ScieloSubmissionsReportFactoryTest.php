<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Section');
import('classes.journal.SectionDAO');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.submission.SubmissionDAO');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class ScieloSubmissionsReportFactoryTest extends DatabaseTestCase {

    private $application = 'ojs';
    private $locale = 'en_US';
    private $contextId = 1;
    private $reportFactory;
    private $sectionsIds;
    private $submissionsIds;
    private $publicationsIds;
    private $startSubmissionDateInterval = '';
    private $endSubmissionDateInterval = '';
    private $startFinalDecisionDateInterval = '';
    private $endFinalDecisionDateInterval = '';

    public function setUp() : void {
        parent::setUp();
        $this->reportFactory = new ScieloSubmissionsReportFactory();
        $this->sectionsIds = $this->createTestSections();
        $this->submissionsIds = $this->createTestSubmissions();
        $this->publicationsIds = $this->createTestPublications($this->submissionsIds, $this->sectionsIds);
    }

    protected function getAffectedTables() {
		return array("sections", "section_settings", "submissions", "submission_settings", "publications", "publication_settings", "edit_decisions");
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

        $this->addFinalDecision($firstSubmissionId, '2021-05-24 13:00:00');
        $this->addFinalDecision($secondSubmissionId, '2021-06-01 23:41:09');
        $this->addFinalDecision($thirdSubmissionId, '2021-06-17 12:00:00');
        $this->addFinalDecision($fourthSubmissionId, '2021-07-10 15:49:00');

        return [$firstSubmissionId, $secondSubmissionId, $thirdSubmissionId, $fourthSubmissionId];
    }

    private function addFinalDecision($submissionId, $dateDecided) {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => SUBMISSION_EDITOR_DECISION_DECLINE, 'dateDecided' => $dateDecided, 'editorId' => 1]);
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
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $expectedSections = [$this->sectionsIds[0] => "Biological Sciences", $this->sectionsIds[1] => "Math"];
        $this->assertEquals($expectedSections, $report->getSections());
    }
    
    public function testReportHasSubmissions() : void {
		$report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportExcludesNonSubmittedSubmissions() {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

		$nonSubmittedSubmission = new Submission();
        $nonSubmittedSubmission->setData('contextId', $this->contextId);
        $nonSubmittedId = $submissionDao->insertObject($nonSubmittedSubmission);
        
        $nonSubmittedPublication = new Publication();
        $nonSubmittedPublication->setData('submissionId', $nonSubmittedId);
        $nonSubmittedPublication->setData('sectionId', $this->sectionsIds[0]);
        $publicationDao->insertObject($nonSubmittedPublication);

        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportFilterBySections() : void {
        $report = $this->reportFactory->createReport($this->application, $this->contextId, [$this->sectionsIds[0]], $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

		$expectedSubmissions = [$this->submissionsIds[0]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterBySubmissionDate() : void {
		$this->startSubmissionDateInterval = '2021-05-23';
        $this->endSubmissionDateInterval = '2021-07-01';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[1], $this->submissionsIds[2]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterByFinalDecisionDate() : void {
        $this->startFinalDecisionDateInterval = '2021-06-15';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[2], $this->submissionsIds[3]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDateExcludesSubmissionsWithoutFinalDecision() : void {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

		$submissionWithoutFinalDecision = new Submission();
        $submissionWithoutFinalDecision->setData('contextId', $this->contextId);
        $submissionWithoutFinalDecision->setData('dateSubmitted', '2021-06-14 04:30:08');
        $submissionWithoutFinalDecisionId = $submissionDao->insertObject($submissionWithoutFinalDecision);
        
        $publicationWithoutFinalDecision = new Publication();
        $publicationWithoutFinalDecision->setData('submissionId', $submissionWithoutFinalDecisionId);
        $publicationWithoutFinalDecision->setData('sectionId', $this->sectionsIds[0]);
        $publicationDao->insertObject($publicationWithoutFinalDecision);
        
        $this->startFinalDecisionDateInterval = '2021-05-20';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportFilterBySubmissionDateAndFinalDecisionDate() : void {
        $this->startSubmissionDateInterval = '2021-05-23';
        $this->endSubmissionDateInterval = '2021-07-01';
        $this->startFinalDecisionDateInterval = '2021-06-15';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $this->assertEquals([$this->submissionsIds[2]], $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDateInOPSGetsPostedSubmissions() : void {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

		$postedSubmission = new Submission();
        $postedSubmission->setData('contextId', $this->contextId);
        $postedSubmission->setData('dateSubmitted', '2021-06-14 04:30:08');
        $postedSubmissionId = $submissionDao->insertObject($postedSubmission);
        
        $postedPublication = new Publication();
        $postedPublication->setData('submissionId', $postedSubmissionId);
        $postedPublication->setData('sectionId', $this->sectionsIds[0]);
        $postedPublication->setData('datePublished', '2021-06-21 14:13:20');
        $publicationDao->insertObject($postedPublication);
        
        $this->startFinalDecisionDateInterval = '2021-05-20';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $this->application = 'ops';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = array_merge($this->submissionsIds, [$postedSubmissionId]);
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
}

?>