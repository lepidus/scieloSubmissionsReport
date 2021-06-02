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
    private $firstSectionName = "Biological Sciences";
    private $secondSectionName = "Math";
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
        $this->publicationsIds = $this->createTestPublications();
    }

    protected function getAffectedTables() {
		return array("sections", "section_settings", "submissions", "submission_settings", "publications", "publication_settings", "edit_decisions");
	}

    private function createTestSections() : array {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section1 = new Section();
        $section2 = new Section();
        $section1->setTitle($this->firstSectionName, $this->locale);
        $section2->setTitle($this->secondSectionName, $this->locale);
        $firstSectionId = $sectionDao->insertObject($section1);
        $secondSectionId = $sectionDao->insertObject($section2);

        return [$firstSectionId, $secondSectionId];
    }

    private function createSubmission($dateSubmitted = null, $dateFinalDecision = null) : int {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);

        if(!is_null($dateSubmitted)) $submission->setData('dateSubmitted', $dateSubmitted);
        $submissionId = $submissionDao->insertObject($submission);

        if(!is_null($dateFinalDecision)) $this->addFinalDecision($submissionId, $dateFinalDecision);

        return $submissionId;
    }

    private function createTestSubmissions() : array {
        $firstSubmissionId = $this->createSubmission('2021-05-21 11:56:37', '2021-05-24 13:00:00');
        $secondSubmissionId = $this->createSubmission('2021-05-29 12:58:29', '2021-06-01 23:41:09');
        $thirdSubmissionId = $this->createSubmission('2021-06-14 04:30:08','2021-06-17 12:00:00');
        $fourthSubmissionId = $this->createSubmission('2021-07-08 18:37:12', '2021-07-10 15:49:00');

        return [$firstSubmissionId, $secondSubmissionId, $thirdSubmissionId, $fourthSubmissionId];
    }

    private function addFinalDecision($submissionId, $dateDecided) {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => SUBMISSION_EDITOR_DECISION_DECLINE, 'dateDecided' => $dateDecided, 'editorId' => 1]);
    }

    private function createPublication($submissionId, $sectionId, $datePublished = null) {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        
        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('sectionId', $sectionId);
        if(!is_null($datePublished)) $publication->setData('datePublished', $datePublished);

        return $publicationDao->insertObject($publication);
    }

	private function createTestPublications() : array {
        $firstPublicationId = $this->createPublication($this->submissionsIds[0], $this->sectionsIds[0]);
        $secondPublicationId = $this->createPublication($this->submissionsIds[1], $this->sectionsIds[1]);
        $thirdPublicationId = $this->createPublication($this->submissionsIds[2], $this->sectionsIds[1]);
        $fourthPublicationId = $this->createPublication($this->submissionsIds[3], $this->sectionsIds[1]);

		return [$firstPublicationId, $secondPublicationId, $thirdPublicationId, $fourthPublicationId];
	}

    public function testReportHasSections() : void {
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $expectedSections = [$this->sectionsIds[0] => $this->firstSectionName, $this->sectionsIds[1] => $this->secondSectionName];
        $this->assertEquals($expectedSections, $report->getSections());
    }
    
    public function testReportHasSubmissions() : void {
		$report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportExcludesNonSubmittedSubmissions() {
        $nonSubmittedId = $this->createSubmission();
        $this->createPublication($nonSubmittedId, $this->sectionsIds[0]);

        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $this->assertEquals($this->submissionsIds, $report->getSubmissions());
    }

    public function testReportFilterBySections() : void {
        $report = $this->reportFactory->createReport($this->application, $this->contextId, [$this->sectionsIds[0]], $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

		$expectedSubmissions = [$this->submissionsIds[0]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterByNoSectionsSelected() : void {
        $emptySections = [];
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $emptySections, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);

        $this->assertEmpty($report->getSubmissions());
    }

    public function testReportFilterBySubmissionDate() : void {
		$this->startSubmissionDateInterval = '2021-05-23';
        $this->endSubmissionDateInterval = '2021-07-01';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[1], $this->submissionsIds[2]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
    
    public function testReportFilterBySubmissionDateSubmissionAtIntervalStart() : void {
        $this->startSubmissionDateInterval = '2021-05-29';
        $this->endSubmissionDateInterval = '2021-06-02';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[1]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterBySubmissionDateSubmissionAtIntervalEnd() : void {
        $this->startSubmissionDateInterval = '2021-05-26';
        $this->endSubmissionDateInterval = '2021-05-29';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[1]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDate() : void {
        $this->startFinalDecisionDateInterval = '2021-06-15';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[2], $this->submissionsIds[3]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDateSubmissionAtIntervalStart() : void {
        $this->startFinalDecisionDateInterval = '2021-07-10';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[3]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDateSubmissionAtIntervalEnd() : void {
        $this->startFinalDecisionDateInterval = '2021-07-05';
        $this->endFinalDecisionDateInterval = '2021-07-10';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = [$this->submissionsIds[3]];
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }

    public function testReportFilterByFinalDecisionDateExcludesSubmissionsWithoutFinalDecision() : void {
        $submissionWithoutFinalDecisionId = $this->createSubmission('2021-06-14 04:30:08');
        $this->createPublication($submissionWithoutFinalDecisionId, $this->sectionsIds[0]);
        
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
		$postedSubmissionId = $this->createSubmission('2021-06-14 04:30:08');
        $this->createPublication($postedSubmissionId, $this->sectionsIds[0], '2021-06-21 14:13:20');
        
        $this->startFinalDecisionDateInterval = '2021-05-20';
        $this->endFinalDecisionDateInterval = '2021-07-12';
        $this->application = 'ops';
        $report = $this->reportFactory->createReport($this->application, $this->contextId, $this->sectionsIds, $this->startSubmissionDateInterval, $this->endSubmissionDateInterval, $this->startFinalDecisionDateInterval, $this->endFinalDecisionDateInterval, $this->locale);
        
        $expectedSubmissions = array_merge($this->submissionsIds, [$postedSubmissionId]);
        $this->assertEquals($expectedSubmissions, $report->getSubmissions());
    }
}

?>