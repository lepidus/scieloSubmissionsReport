<?php
import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionFactory');

class ScieloSubmissionFactoryTest extends DatabaseTestCase {
    
    private $locale = 'en_US';
    private $contextId = 1;
    private $submissionId;
    private $publicationId;
    private $title = "eXtreme Programming: A practical guide";

    public function setUp() : void {
        parent::setUp();
        $this->submissionId = $this->createSubmission();
        $this->publicationId = $this->createPublication();
        $this->addCurrentPublicationToSubmission();
    }
    
    protected function getAffectedTables() {
        return ['submissions', 'submission_settings', 'publications', 'publication_settings'];
    }

    private function createSubmission() : int {
        $submissionDao = DAORegistry::getDAO('SubmissionDAO');
        $submission = new Submission();
        $submission->setData('contextId', $this->contextId);
         
        return $submissionDao->insertObject($submission);
    }

    private function createPublication() : int {
        $publicationDao = DAORegistry::getDAO('PublicationDAO');
        $publication = new Publication();
        $publication->setData('submissionId', $this->submissionId);
        $publication->setData('title', $this->title, $this->locale);
        
        return $publicationDao->insertObject($publication);
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
}

?>