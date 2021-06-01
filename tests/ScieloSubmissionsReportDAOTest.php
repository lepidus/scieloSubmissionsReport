<?php

import('lib.pkp.tests.PKPTestHelper');
import('lib.pkp.tests.DatabaseTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');


class ScieloSubmissionsReportDAOTest extends DatabaseTestCase {

    private $application;
    private $journalId;
    private $initialSubmissionDate;
    private $finalSubmissionDate;
    private $initialDecisionDate;
    private $finalDecisionDate;
    private $sections;
    private $dbDumpPath;
    private $dbTestPath;
    private $previousDatabaseDumpFile;
    private $previousDatabaseDumpCompressFile;

    protected function setUp(): void {
        $this->application = "ops";
        $this->journalId = 1;
        $this->initialSubmissionDate = null;
        $this->finalSubmissionDate = null;
        $this->initialDecisionDate = "2020-04-29";
        $this->finalDecisionDate = "2020-09-07";
        $this->sections = ["Health Sciences"];
        $this->dbDumpPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'dbdump';
        $this->dbTestPath = $this->dbDumpPath . DIRECTORY_SEPARATOR . 'scieloSubmissionsReportTest.sql.gz';
        $this->previousDatabaseDumpFile = $this->dbDumpPath . DIRECTORY_SEPARATOR . 'previousDatabase.sql';
        $this->previousDatabaseDumpCompressFile = $this->previousDatabaseDumpFile . '.gz';

        $scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
        DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);

        $this->backupCurrentDatabase();

        putenv('DATABASEDUMP=' . $this->dbTestPath);
        PKPTestHelper::restoreDB($this);

        parent::setUp();
    }

    public function backupCurrentDatabase() {
        exec('/usr/bin/mysqldump --user=' .
            escapeshellarg(Config::getVar('database', 'username')) .
            ' --password=' .
            escapeshellarg(Config::getVar('database', 'password')) .
            ' --host=' .
            escapeshellarg(Config::getVar('database', 'host')) .
            ' ' .
            escapeshellarg(Config::getVar('database', 'name')) .
            ' > ' . $this->previousDatabaseDumpFile
        );
        exec('gzip ' . $this->previousDatabaseDumpFile);
    }

    protected function getAffectedTables() {
        return PKP_TEST_ENTIRE_DB;
    }

    public function testOnlySubmissionsWithFinalDecisionBetweenTheIntervalShoudBeListed() {
        $scieloSubmissionsReportDAO = &DAORegistry::getDAO('ScieloSubmissionsReportDAO');
        $reportData = $scieloSubmissionsReportDAO->getReportWithSections(
            $this->application,
            $this->journalId,
            $this->initialSubmissionDate,
            $this->finalSubmissionDate,
            $this->initialDecisionDate,
            $this->finalDecisionDate,
            $this->sections
        );

        $numberOfReportRows = sizeof($reportData);
        $minimumNumberOfRows = 3;
        
        $expectedNumberOfSubmissions = 1;
        $resultedNumberOfSubmissions = $numberOfReportRows - $minimumNumberOfRows;
        $expectedTitle = 'Um estudo sobre a Arte da Guerra';
        $titleResult = $reportData[0][1];

        self::assertEquals($expectedNumberOfSubmissions, $resultedNumberOfSubmissions);
        self::assertEquals($expectedTitle, $titleResult);
    }

    protected function tearDown(): void {
        putenv('DATABASEDUMP=' . $this->previousDatabaseDumpCompressFile);
        parent::tearDown();
        unlink($this->previousDatabaseDumpCompressFile);
    }
}
