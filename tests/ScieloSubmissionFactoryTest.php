<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('classes.journal.Section');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloSubmissionFactoryTest extends DatabaseTestCase {
    
    private $locale = 'en_US';
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $title = "eXtreme Programming: A practical guide";
    private $submitter = "Don Vito Corleone";
    private $dateSubmitted = '2021-05-31 15:38:24';
    private $statusCode = STATUS_PUBLISHED;
    private $statusMessage;
    private $sectionName = "Biological Sciences";

    public function setUp() : void {
        parent::setUp();
        $sectionId = $this->createSection();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication($sectionId);
        $this->statusMessage = __('submission.status.published', [], 'en_US');
        $this->addCurrentPublicationToSubmission();
    }
    
    protected function getAffectedTables() {
        return ['submissions', 'submission_settings', 'publications', 'publication_settings', 'users', 'user_settings', 'event_log'];
    }

    private function createSubmission() : int {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
        $submission->setData('dateSubmitted', $this->dateSubmitted);
        $submission->setData('status', $this->statusCode);
        $submission->setData('locale', $this->locale);
         
        return $submissionDao->insertObject($submission);
    }

    private function createPublication($sectionId) : int {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = new Publication();
        $publication->setData('submissionId', $this->submissionId);
        $publication->setData('title', $this->title, $this->locale);
        $publication->setData('sectionId', $sectionId);
        
        return $publicationDao->insertObject($publication);
    }

    private function createSection() : int {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section = new Section();
        $section->setTitle($this->sectionName, $this->locale);
        $sectionId = $sectionDao->insertObject($section);

        return $sectionId;
    }

    private function addCurrentPublicationToSubmission() : void {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = $submissionDao->getById($this->submissionId);
        $submission->setData('currentPublicationId', $this->publicationId);
        $submissionDao->updateObject($submission);
    }

    public function testSubmissionGetsTitle() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->title, $scieloSubmission->getTitle());
    }

    public function testSubmissionGetsSubmitter() : void {
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $userSubmitter = new User();
        $userSubmitter->setUsername('the_godfather');
        $userSubmitter->setEmail('donvito@corleone.com');
        $userSubmitter->setPassword('miaumiau');
        $userSubmitter->setGivenName("Don", $this->locale);
        $userSubmitter->setFamilyName("Vito Corleone", $this->locale);
        $userSubmitterId = $userDao->insertObject($userSubmitter);

        $submissionEvent = $submissionEventLogDao->newDataObject();
        $submissionEvent->setSubmissionId($this->submissionId);
        $submissionEvent->setEventType(SUBMISSION_LOG_SUBMISSION_SUBMIT);
        $submissionEvent->setUserId($userSubmitterId);
        $submissionEvent->setDateLogged('2021-05-28 15:19:24');
        $submissionEventLogDao->insertObject($submissionEvent);

        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->submitter, $scieloSubmission->getSubmitter());
    }

    public function testSubmissionGetsDateSubmitted() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->dateSubmitted, $scieloSubmission->getDateSubmitted());
    }

    public function testSubmissionGetsStatus() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->statusMessage, $scieloSubmission->getStatus());
    }

    public function testSubmissionGetsSectionName() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->sectionName, $scieloSubmission->getSection());
    }

    public function testSubmissionGetsLanguage() : void {
        $submissionFactory = new ScieloSubmissionFactory();
        $scieloSubmission = $submissionFactory->createSubmission($this->submissionId, $this->locale);

        $this->assertEquals($this->locale, $scieloSubmission->getLanguage());
    }
}

?>