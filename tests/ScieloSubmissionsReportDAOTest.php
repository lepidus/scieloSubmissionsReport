<?php

import('lib.pkp.tests.PKPTestHelper');
import('lib.pkp.tests.DatabaseTestCase');
import('plugins.reports.ScieloSubmissionsReportPlugin.ScieloSubmissionsReportDAO');


class ScieloSubmissionsReportDAOTest extends DatabaseTestCase{

    private $application;
    private $journalId;
    private $initialSubmissionDate;
    private $finalSubmissionDate;
    private $initialDecisionDate;
    private $finalDecisionDate;
    private $sections;
    private $dbDumpPath;

    protected function setUp(): void
    {
        $this->application = "ops";
        $this->journalId = 1;
        $this->initialSubmissionDate = null;
        $this->finalSubmissionDate = null;
        $this->initialDecisionDate = "2020-04-29";
        $this->finalDecisionDate = "2020-09-07";
        $this->sections = ["Health Sciences"];

        $this->dbDumpPath =
        Core::getBaseDir() .
            DIRECTORY_SEPARATOR .
            join(DIRECTORY_SEPARATOR, 
                ['plugins','reports','ScieloSubmissionsReportPlugin','tests','dbdump']
            );

        $scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
        DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);

        $this->backupCurrentDatabase();

        putenv('DATABASEDUMP=' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'scieloSubmissionsReportTest.sql.gz');
        PKPTestHelper::restoreDB($this);

        parent::setUp();
    }

    public function backupCurrentDatabase()
    {
        exec('/usr/bin/mysqldump --user=' .
            escapeshellarg(Config::getVar('database', 'username')) .
            ' --password=' .
            escapeshellarg(Config::getVar('database', 'password')) .
            ' --host=' .
            escapeshellarg(Config::getVar('database', 'host')) .
            ' ' .
            escapeshellarg(Config::getVar('database', 'name')) .
            ' > ' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.sql'
        );
        exec('zip ' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.zip ' .
            $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.sql');
    }

    protected function getAffectedTables()
    {
        return PKP_TEST_ENTIRE_DB;
    }

    public function testOnlySubmissionsWithFinalDecisionBetweenTheIntervalShoudBeListed()
    {
        $scieloSubmissionsReportDAO = &DAORegistry::getDAO('ScieloSubmissionsReportDAO');
        $submissionsData = $scieloSubmissionsReportDAO->getReportWithSections(
            $this->application,
            $this->journalId,
            $this->initialSubmissionDate,
            $this->finalSubmissionDate,
            $this->initialDecisionDate,
            $this->finalDecisionDate,
            $this->sections
        );

        $fieldsInformationsAboutFilter = 3;

        $expectedNumberOfSubmissions = 1;
        $resultedNumberOfSubmissions = sizeof($submissionsData) - $fieldsInformationsAboutFilter;
        $expectedTitle = 'Um estudo sobre a Arte da Guerra';
        $titleResult = $submissionsData[0][1];

        self::assertEquals($expectedNumberOfSubmissions, $resultedNumberOfSubmissions);
        self::assertEquals($expectedTitle, $titleResult);
    }

    protected function tearDown(): void
    {
        putenv('DATABASEDUMP=' . $this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.zip');
        parent::tearDown();
        unlink($this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.sql');
        unlink($this->dbDumpPath . DIRECTORY_SEPARATOR . 'dumpApplication.zip');
    }
}
