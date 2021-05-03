<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');
import('classes.submission.Submission');
import('lib.pkp.classes.user.User');


class ScieloSubmissionsReportDAOTest extends DatabaseTestCase {

	private $application;
	private $journalId;
	private $initialDecisionDate;
	private $finalDecisionDate;
	private $sessions;

	private $submissionId;

	
	protected function setUp() : void {
		
		$this->application = "ops";
		$this->journalId = 1;
		$this->initialDecisionDate = "2020-07-01";
		$this->finalDecisionDate = "2020-07-31";
		$this->sessions = ["Health Sciences"];
		
		$scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
		DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);
		
		parent::setUp();
	}
		
	protected function getAffectedTables() {
		return array('submissions','edit_decisions');
	}
	
	public function testCreateSubmission(){
		$submission = new Submission();
		$submission->setContextId($this->journalId);
		$submission->setStatus(STATUS_DECLINED);
		$submission->setSubmissionProgress(1);
		$submission->stampStatusModified();
		$submission->setStageId(STATUS_DECLINED);
		//$submission->setData('seriesId', $seriesId = current(array_keys($seriesOptions)));
		//$submission->setLocale($this->getDefaultFormLocale());
		
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); 
		
		self::assertTrue(is_integer($this->submissionId = $submissionDao->insertObject($submission)));
	}

	public function testEditorDecisionSettings(){
		$user = new User();
		
		$editorDecision = array(
				'editDecisionId' => null,
				'editorId' => $user->getId(),
				'decision' => 4,
				'dateDecided' => strtotime('2021-03-30 16:00:00')
			);
			
		$editDecisionDAO = DAORegistry::getDAO('EditDecisionDAO');
		$editDecisionDAO->updateEditorDecision($this->submissionId, $editorDecision);

		$decisionsSubmission = $editDecisionDAO->getEditorDecisions($this->submissionId);
		self::assertTrue(is_integer($decisionsSubmission[0]['decision']));
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





}
