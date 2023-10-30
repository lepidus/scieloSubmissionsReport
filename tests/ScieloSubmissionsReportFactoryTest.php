<?php

use PKP\tests\DatabaseTestCase;
use APP\section\Section;
use APP\submission\Submission;
use APP\publication\Publication;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionsReportFactory;
use APP\plugins\reports\scieloSubmissionsReport\classes\ClosedDateInterval;

class ScieloSubmissionsReportFactoryTest extends DatabaseTestCase
{
    private $application = 'ojs';
    private $locale = 'en_US';
    private $contextId = 1;
    private $reportFactory;
    private $firstSectionName = "Biological Sciences";
    private $secondSectionName = "Math";
    private $sectionsIds;
    private $submissionsIds;
    private $publicationsIds;
    private $submissionDateInterval;
    private $finalDecisionDateInterval;
    private $includeViews = true;

    public function setUp(): void
    {
        parent::setUp();
        $this->sectionsIds = $this->createTestSections();
        $this->submissionsIds = $this->createTestSubmissions();
        $this->publicationsIds = $this->createTestPublications();
        $this->addCurrentPublicationToTestSubmissions();
    }

    protected function getAffectedTables()
    {
        return array("sections", "section_settings", "submissions", "submission_settings", "publications", "publication_settings", "edit_decisions");
    }

    private function createTestSections(): array
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section1 = new Section();
        $section2 = new Section();
        $section1->setTitle($this->firstSectionName, $this->locale);
        $section2->setTitle($this->secondSectionName, $this->locale);
        $firstSectionId = $sectionDao->insertObject($section1);
        $secondSectionId = $sectionDao->insertObject($section2);

        return [$firstSectionId, $secondSectionId];
    }

    private function createSubmission($dateSubmitted = null, $dateFinalDecision = null): int
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');

        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('locale', $this->locale);
        $submission->setData('status', STATUS_PUBLISHED);

        if (!is_null($dateSubmitted)) {
            $submission->setData('dateSubmitted', $dateSubmitted);
        }
        $submissionId = $submissionDao->insertObject($submission);

        if (!is_null($dateFinalDecision)) {
            $this->addFinalDecision($submissionId, $dateFinalDecision);
        }

        return $submissionId;
    }

    private function createTestSubmissions(): array
    {
        $firstSubmissionId = $this->createSubmission('1921-05-21 11:56:37', '1921-05-24 13:00:00');
        $secondSubmissionId = $this->createSubmission('1921-05-29 12:58:29', '1921-06-01 23:41:09');
        $thirdSubmissionId = $this->createSubmission('1921-06-14 04:30:08', '1921-06-17 12:00:00');
        $fourthSubmissionId = $this->createSubmission('1921-07-08 18:37:12', '1921-07-10 15:49:00');

        return [$firstSubmissionId, $secondSubmissionId, $thirdSubmissionId, $fourthSubmissionId];
    }

    private function addFinalDecision($submissionId, $dateDecided)
    {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $editDecisionDao->updateEditorDecision($submissionId, ['editDecisionId' => null, 'decision' => SUBMISSION_EDITOR_DECISION_DECLINE, 'dateDecided' => $dateDecided, 'editorId' => 1]);
    }

    private function createPublication($submissionId, $sectionId, $datePublished = null)
    {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');

        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('sectionId', $sectionId);
        $publication->setData('title', "Generic title", $this->locale);
        if (!is_null($datePublished)) {
            $publication->setData('datePublished', $datePublished);
        }

        return $publicationDao->insertObject($publication);
    }

    private function createTestPublications(): array
    {
        $firstPublicationId = $this->createPublication($this->submissionsIds[0], $this->sectionsIds[0]);
        $secondPublicationId = $this->createPublication($this->submissionsIds[1], $this->sectionsIds[1]);
        $thirdPublicationId = $this->createPublication($this->submissionsIds[2], $this->sectionsIds[1]);
        $fourthPublicationId = $this->createPublication($this->submissionsIds[3], $this->sectionsIds[1]);

        return [$firstPublicationId, $secondPublicationId, $thirdPublicationId, $fourthPublicationId];
    }

    private function addCurrentPublicationToSubmission($submissionId, $publicationId)
    {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($submissionId);
        $submission->setData('currentPublicationId', $publicationId);
        $submissionDao->updateObject($submission);
    }

    private function addCurrentPublicationToTestSubmissions()
    {
        for ($i = 0; $i < count($this->submissionsIds); $i++) {
            $submissionId = $this->submissionsIds[$i];
            $publicationId = $this->publicationsIds[$i];
            $this->addCurrentPublicationToSubmission($submissionId, $publicationId);
        }
    }

    private function mapScieloSubmissionsToIds($scieloSubmissions)
    {
        $submissionsIds = array_map(function ($scieloSubmission) {
            return $scieloSubmission->getId();
        }, $scieloSubmissions);

        return $submissionsIds;
    }

    public function testReportHasSections(): void
    {
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSections = [$this->sectionsIds[0] => $this->firstSectionName, $this->sectionsIds[1] => $this->secondSectionName];
        $this->assertEquals($expectedSections, $report->getSections());
    }

    public function testReportHasSubmissions(): void
    {
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($this->submissionsIds, $scieloSubmissionsIds);
    }

    public function testReportExcludesNonSubmittedSubmissions()
    {
        $nonSubmittedId = $this->createSubmission();
        $publicationId = $this->createPublication($nonSubmittedId, $this->sectionsIds[0]);
        $this->addCurrentPublicationToSubmission($nonSubmittedId, $publicationId);

        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($this->submissionsIds, $scieloSubmissionsIds);
    }

    public function testReportFilterBySections(): void
    {
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, [$this->sectionsIds[0]], $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[0]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterByNoSectionsSelected(): void
    {
        $emptySections = [];
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $emptySections, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $this->assertEmpty($report->getSubmissions());
    }

    public function testReportFilterBySubmissionDate(): void
    {
        $this->submissionDateInterval = new ClosedDateInterval('1921-05-23', '1921-07-01');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[1], $this->submissionsIds[2]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterBySubmissionDateSubmissionAtIntervalStart(): void
    {
        $this->submissionDateInterval = new ClosedDateInterval('1921-05-29', '1921-06-02');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[1]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterBySubmissionDateSubmissionAtIntervalEnd(): void
    {
        $this->submissionDateInterval = new ClosedDateInterval('1921-05-26', '1921-05-29');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[1]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterByFinalDecisionDate(): void
    {
        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-06-15', '1921-07-12');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[2], $this->submissionsIds[3]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterByFinalDecisionDateSubmissionAtIntervalStart(): void
    {
        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-07-10', '1921-07-12');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[3]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterByFinalDecisionDateSubmissionAtIntervalEnd(): void
    {
        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-07-05', '1921-07-10');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = [$this->submissionsIds[3]];
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($expectedSubmissions, $scieloSubmissionsIds);
    }

    public function testReportFilterByFinalDecisionDateExcludesSubmissionsWithoutFinalDecision(): void
    {
        $submissionWithoutFinalDecisionId = $this->createSubmission('1921-06-14 04:30:08');
        $publicationId = $this->createPublication($submissionWithoutFinalDecisionId, $this->sectionsIds[0]);
        $this->addCurrentPublicationToSubmission($submissionWithoutFinalDecisionId, $publicationId);

        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-05-20', '1921-07-12');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals($this->submissionsIds, $scieloSubmissionsIds);
    }

    public function testReportFilterBySubmissionDateAndFinalDecisionDate(): void
    {
        $this->submissionDateInterval = new ClosedDateInterval('1921-05-23', '1921-07-01');
        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-06-15', '1921-07-12');
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEquals([$this->submissionsIds[2]], $scieloSubmissionsIds);
    }

    /**
     * @group OPS
     */
    public function testReportFilterByFinalDecisionDateInOPSGetsPostedSubmissions(): void
    {
        $postedSubmissionId = $this->createSubmission('1921-06-14 04:30:08');
        $publicationId = $this->createPublication($postedSubmissionId, $this->sectionsIds[0], '1921-06-21 14:13:20');
        $this->addCurrentPublicationToSubmission($postedSubmissionId, $publicationId);

        $this->finalDecisionDateInterval = new ClosedDateInterval('1921-05-20', '1921-07-12');
        $this->application = 'ops';
        $this->reportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sectionsIds, $this->submissionDateInterval, $this->finalDecisionDateInterval, $this->locale, $this->includeViews);
        $report = $this->reportFactory->createReport();

        $expectedSubmissions = array_merge($this->submissionsIds, [$postedSubmissionId]);
        $scieloSubmissionsIds = $this->mapScieloSubmissionsToIds($report->getSubmissions());
        $this->assertEmpty(array_diff($expectedSubmissions, $scieloSubmissionsIds));
    }
}
