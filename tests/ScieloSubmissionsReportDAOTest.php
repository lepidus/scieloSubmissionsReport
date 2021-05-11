<?php

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.tests.PKPTestHelper');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');

class ScieloSubmissionsReportDAOTest extends DatabaseTestCase {

    private $application;
    private $journalId;
    private $initialDecisionDate;
    private $finalDecisionDate;
    private $sessions;
    private $dbDumpPath;

    protected function setUp() : void {
        
        $this->application = "ops";
        $this->journalId = 1;
        $this->initialDecisionDate = "2020-04-29";
        $this->finalDecisionDate = "2020-09-07";
        $this->sessions = ["Health Sciences"];
        $this->dbDumpPath = Core::getBaseDir() . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, ['plugins','reports','ScieloSubmissionsReportPlugin','tests','dbdump']);
        
        $scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
        DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);

		$this->getDumpAplication();

        putenv('DATABASEDUMP=' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'scieloSubmissionsReportTest.sql.gz');
        PKPTestHelper::restoreDB($this);
        
        parent::setUp();
    }

	public function getDumpAplication(){

		exec('/usr/bin/mysqldump --user=' .
            escapeshellarg(Config::getVar('database', 'username')) .
            ' --password=' .
            escapeshellarg(Config::getVar('database', 'password')) .
            ' --host=' .
            escapeshellarg(Config::getVar('database', 'host')) .
            ' ' .
            escapeshellarg(Config::getVar('database', 'name')) .
			' > ' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.sql' . 
			'| zip ' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.zip ' .
			$this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.sql'
		);
	}

    protected function getAffectedTables(){
        return PKP_TEST_ENTIRE_DB;
    }

    public function testFilterByFinalDecisionDate(){
        
        $scieloSubmissionsReportDAO =& DAORegistry::getDAO('ScieloSubmissionsReportDAO');
        $submissionsData = $scieloSubmissionsReportDAO->getReportWithSections(
            $this->application,
            $this->journalId,
            null,
            null,
            $this->initialDecisionDate,
            $this->finalDecisionDate,
            $this->sessions
        );

        $expectedLength = 4;
        $lengthResult = sizeof($submissionsData);

        $expectedTitle = 'Um estudo sobre a Arte da Guerra';
        $titleResult = $submissionsData[0][1];
      
        self::assertEquals($expectedLength, $lengthResult);
        self::assertEquals($expectedTitle, $titleResult);
    }
}
