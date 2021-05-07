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
		$this->initialDecisionDate = "2020-04-29";
		$this->finalDecisionDate = "2020-09-07";
		$this->sessions = ["Health Sciences"];
		
		$scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
		DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);


		parent::setUp();
	}


    public function testFilterByFinalDecisionDate(){

		
		$scieloSubmissionsReportDAO =& DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		$submissionsData = $scieloSubmissionsReportDAO->getReportWithSections($this->application,$this->journalId,null,null,$this->initialDecisionDate,$this->finalDecisionDate,$this->sessions);

		$expectedLength = 4;
		$lengthResult = sizeof($submissionsData);

		$expectedTitle = 'Um estudo sobre a Arte da Guerra';
		$titleResult = $submissionsData[0][1];
		
		self::assertEquals($expectedLength, $lengthResult);
		self::assertEquals($expectedTitle, $titleResult);
	}

}
