<?php

import('lib.pkp.tests.PKPTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');


class ScieloSubmissionsReportDAOTest extends PKPTestCase {

	private $application;
	private $journalId;

	protected function setUp() : void {
		$this->application = "ops";
		$this->journalId = 1;


		$scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
		DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);

		parent::setUp();
	}
 
    public function testReportWithSectionsDecisionDate(){

		$initialDecisionDate = "2021-01-01";
		$finalDecisionDate = "2021-04-29";
		$sessions = ["Agricultural Sciences"];
		

		$JournalDAO =& DAORegistry::getDAO('JournalDAO'); 
		$scieloSubmissionsReportDAO =& DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		$submissionsData = $scieloSubmissionsReportDAO->getReportWithSections($this->application,$this->journalId,null,null,$initialDecisionDate,$finalDecisionDate,$sessions);

		self::assertTrue(false);
	}





}
