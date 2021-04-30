<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');
class ScieloSubmissionsReportDAOTest extends PKPTestCase {

	private $application;
	private $journalId;
	private $initialDecisionDate;
	private $finalDecisionDate;
	private $sessions;

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
 
    public function testReportWithSectionsDecisionDate(){
		
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
