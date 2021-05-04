<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('lib.pkp.classes.user.User');

class ScieloSubmissionsReportDAOTest extends DatabaseTestCase {

	private $application;
	private $journalId;
	private $initialDecisionDate;
	private $finalDecisionDate;
	private $sessions;

	
	protected function setUp() : void {
		
		$this->application = "ops";
		$this->journalId = 1;
		$this->initialDecisionDate = "2021-03-01";
		$this->finalDecisionDate = "2021-05-31";
		$this->sessions = ["Health Sciences"];
		
		$scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
		DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);
		
		parent::setUp();
	}
	
	public function testCreateSubmission(){
		// Create Submission
		$submission = new Submission();
		$submission->setContextId($this->journalId);
		$submission->setStatus(STATUS_DECLINED);
		$submission->setSubmissionProgress(1);
		$submission->stampStatusModified();
		$submission->setStageId(STATUS_DECLINED);
		$submission->setData('dateSubmitted', date('2021-03-15 16:00:00'));
		
		// Insert Submission
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); 
		$submissionId = $submissionDao->insertObject($submission);
		$newSubmission = $submissionDao->getById($submissionId);
		
		//Create Publication
		$publication = new Publication();
		$this->setPublicationData($publication, $newSubmission);
		$publication->setData('status', STATUS_QUEUED);
		$publication->setData('version', 1);
		$publication->setData('sectionId', 3);

		// Insert publication
		$publicationDao = DAORegistry::getDAO('PublicationDAO');
		$publicationId = $publicationDao->insertObject($publication);
		$newPublication = $publicationDao->getById($publicationId);

		$newSubmission->_data = array_merge($newSubmission->_data, ['currentPublicationId' => $newPublication->getId()]);
		$submissionDao->updateObject($newSubmission);

		self::assertTrue(is_integer($submissionId));
		self::assertTrue(is_integer($publicationId));
		self::assertTrue(is_integer(($submissionDao->getById($submissionId))->getSectionId()));

		return $submissionId;
	}

	function setPublicationData($publication, $submission) {
		$locale = $publication->getData('locale');
		$publication->setData('submissionId', $submission->getId());
		$publication->setData('locale', $locale);
		$publication->setData('language', PKPString::substr($locale, 0, 2));
	}

	/**
     * @depends testCreateSubmission
     */
	public function testEditorDecisionSettings(int $submissionId) {
		$user = new User();
		
		$editorDecision = array(
				'editDecisionId' => null,
				'editorId' => $user->getId(),
				'decision' => 4,
				'dateDecided' => strtotime('2021-03-30 16:00:00')
			);
			
		$editDecisionDAO = DAORegistry::getDAO('EditDecisionDAO');
		$editDecisionDAO->updateEditorDecision($submissionId, $editorDecision);

		$decisionsSubmission = $editDecisionDAO->getEditorDecisions($submissionId);
		self::assertTrue(is_string($decisionsSubmission[0]['decision']));
	}

    public function testFilterByFinalDecisionDate(){
		
		$scieloSubmissionsReportDAO =& DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		$submissionsData = $scieloSubmissionsReportDAO->getReportWithSections($this->application,$this->journalId,null,null,$this->initialDecisionDate,$this->finalDecisionDate,$this->sessions);
		
		for ($index = 0; $index < (sizeof($submissionsData)-3) ; $index++) {
			if ($submissionsData[$index][15] == '') self::assertTrue(false);

			if ((strtotime($this->initialDecisionDate) > strtotime($submissionsData[$index][15])) ||  (strtotime($submissionsData[$index][15]) > strtotime($this->finalDecisionDate)))
				self::assertTrue(false);
		}

		self::assertTrue(true);
	}
	
	protected function getAffectedTables() {
		return array('submissions','edit_decisions','publications');
	}

}
