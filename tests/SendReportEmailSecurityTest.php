<?php

namespace PKP\scheduledTask;

if (!class_exists(ScheduledTask::class, false)) {
    abstract class ScheduledTask
    {
    }
}

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\plugins\reports\scieloSubmissionsReport\classes\tasks\SendReportEmail;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use stdClass;

require_once dirname(__DIR__) . '/classes/tasks/SendReportEmail.php';

class TestableSendReportEmail extends SendReportEmail
{
    private $temporaryDirectory;

    public function __construct($temporaryDirectory)
    {
        $this->temporaryDirectory = $temporaryDirectory;
    }

    protected function getReportTemporaryDirectory(): string
    {
        return $this->temporaryDirectory;
    }

    public function writeReport($report): string
    {
        return $this->writeReportFile($report);
    }

    public function sendFailingReport($report): void
    {
        $this->sendReport(null, [], $report);
    }

    protected function createReportEmail($context, $recipientEmails, $reportFilePath)
    {
        return new stdClass();
    }

    protected function sendEmail($email): void
    {
        throw new RuntimeException('Simulated mail failure');
    }
}

class SendReportEmailSecurityTest extends TestCase
{
    private $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/scielo-report-test-' . uniqid('', true);
        mkdir($this->temporaryDirectory, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->temporaryDirectory . '/*') as $path) {
            unlink($path);
        }
        rmdir($this->temporaryDirectory);
    }

    public function testProductionDirectoryUsesPkpPrivateTemporaryStorage(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/classes/tasks/SendReportEmail.php');

        $this->assertStringContainsString('new TemporaryFileManager()', $source);
        $this->assertStringNotContainsString("getLocalizedData('acronym')", $source);
        $this->assertStringContainsString("'complete_report.csv'", $source);
    }

    public function testReportUsesUniqueRestrictedFileAndClosesStream(): void
    {
        $task = new TestableSendReportEmail($this->temporaryDirectory);
        $report = new class () {
            public $stream;

            public function buildCSV($stream): void
            {
                $this->stream = $stream;
                fwrite($stream, "header\nvalue\n");
            }
        };

        $firstPath = $task->writeReport($report);
        $secondPath = $task->writeReport($report);

        $this->assertSame($this->temporaryDirectory, dirname($firstPath));
        $this->assertStringStartsWith('scielo-report-', basename($firstPath));
        $this->assertNotSame($firstPath, $secondPath);
        $this->assertSame(0600, fileperms($firstPath) & 0777);
        $this->assertSame("header\nvalue\n", file_get_contents($firstPath));
        $this->assertFalse(is_resource($report->stream));
    }

    public function testGenerationFailureRemovesTemporaryFile(): void
    {
        $task = new TestableSendReportEmail($this->temporaryDirectory);
        $report = new class () {
            public function buildCSV($stream): void
            {
                fwrite($stream, 'partial');
                throw new RuntimeException('Simulated report failure');
            }
        };

        try {
            $task->writeReport($report);
            $this->fail('The report failure should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated report failure', $exception->getMessage());
        }

        $this->assertSame([], glob($this->temporaryDirectory . '/*'));
    }

    public function testMailFailureRemovesTemporaryFile(): void
    {
        $task = new TestableSendReportEmail($this->temporaryDirectory);
        $report = new class () {
            public function buildCSV($stream): void
            {
                fwrite($stream, 'complete');
            }
        };

        try {
            $task->sendFailingReport($report);
            $this->fail('The mail failure should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated mail failure', $exception->getMessage());
        }

        $this->assertSame([], glob($this->temporaryDirectory . '/*'));
    }
}
