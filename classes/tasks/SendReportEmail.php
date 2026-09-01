<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes\tasks;

use APP\core\Application;
use APP\facades\Repo;
use PKP\file\TemporaryFileManager;
use PKP\facades\Locale;
use PKP\mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use APP\plugins\reports\scieloSubmissionsReport\classes\ClosedDateInterval;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionsReportFactory;
use RuntimeException;
use Throwable;

class SendReportEmail extends ScheduledTask
{
    public function executeActions()
    {
        $application = substr(Application::getName(), 0, 3);
        $context = Application::get()->getRequest()->getContext();
        PluginRegistry::loadCategory('reports');
        $plugin = PluginRegistry::getPlugin('reports', 'scielosubmissionsreportplugin');

        $recipientEmails = $this->getRecipientEmails($plugin, $context->getId());

        if ($application == 'ops' && !empty($recipientEmails)) {
            $locale = Locale::getLocale();
            $plugin->addLocaleData($locale);

            $report = $this->getReport($application, $context, $locale);
            $this->sendReport($context, $recipientEmails, $report);
        }

        return true;
    }

    private function getReport($application, $context, $locale)
    {
        $sectionsIds = $this->getAllSectionsIds($context->getId());
        $includeViews = true;
        $beginningDate = '2020-04-01';
        $endDate = date('Y-m-d');
        $submissionDateInterval = new ClosedDateInterval($beginningDate, $endDate);

        $reportFactory = new ScieloSubmissionsReportFactory($application, $context->getId(), $sectionsIds, $submissionDateInterval, null, $locale, $includeViews);
        return $reportFactory->createReport();
    }

    protected function sendReport($context, $recipientEmails, $report): void
    {
        $reportFilePath = $this->writeReportFile($report);

        try {
            $email = $this->createReportEmail($context, $recipientEmails, $reportFilePath);
            $this->sendEmail($email);
        } finally {
            $this->deleteReportFile($reportFilePath);
        }
    }

    protected function getReportTemporaryDirectory(): string
    {
        $temporaryFileManager = new TemporaryFileManager();
        $temporaryDirectory = $temporaryFileManager->getBasePath();
        if (!is_dir($temporaryDirectory)) {
            $temporaryFileManager->mkdirtree($temporaryDirectory);
        }
        if (!is_dir($temporaryDirectory)) {
            throw new RuntimeException('Unable to prepare the report temporary directory.');
        }

        return $temporaryDirectory;
    }

    protected function writeReportFile($report): string
    {
        $reportFilePath = tempnam($this->getReportTemporaryDirectory(), 'scielo-report-');
        if ($reportFilePath === false) {
            throw new RuntimeException('Unable to create the temporary report file.');
        }

        $csvFile = fopen($reportFilePath, 'wb');
        if ($csvFile === false) {
            $this->deleteReportFile($reportFilePath);
            throw new RuntimeException('Unable to open the temporary report file.');
        }

        try {
            $report->buildCSV($csvFile);
            if (!fclose($csvFile)) {
                $csvFile = null;
                throw new RuntimeException('Unable to close the temporary report file.');
            }
            $csvFile = null;
        } catch (Throwable $exception) {
            if (is_resource($csvFile)) {
                fclose($csvFile);
            }
            $this->deleteReportFile($reportFilePath);
            throw $exception;
        }

        return $reportFilePath;
    }

    protected function deleteReportFile($reportFilePath): void
    {
        if (file_exists($reportFilePath) && !unlink($reportFilePath)) {
            throw new RuntimeException('Unable to remove the temporary report file.');
        }
    }

    private function getAllSectionsIds($contextId)
    {
        $sections = Repo::section()->getSectionList($contextId);

        $sectionsIds = [];
        foreach ($sections as $section) {
            $sectionsIds[] = $section['id'];
        }
        return $sectionsIds;
    }

    private function getRecipientEmails($plugin, $contextId)
    {
        $recipientEmailSetting = $plugin->getSetting($contextId, 'recipientEmail');
        if (is_null($recipientEmailSetting)) {
            return [];
        }

        $recipientEmails = array_map(function ($email) {
            return ['name' => '', 'email' => trim($email)];
        }, explode(',', $recipientEmailSetting));

        return $recipientEmails;
    }

    protected function createReportEmail($context, $recipientEmails, $reportFilePath)
    {
        $email = new Mailable();

        $fromName = $context->getLocalizedData('name');
        $fromEmail = $context->getData('contactEmail');

        $email->from($fromEmail, $fromName);
        $email->to($recipientEmails);

        $subject = __('plugins.reports.scieloSubmissionsReport.reportEmail.subject', ['contextName' => $fromName]);
        $body = __('plugins.reports.scieloSubmissionsReport.reportEmail.body', ['contextName' => $fromName]);
        $email->subject($subject);
        $email->body($body);

        $email->attach($reportFilePath, ['as' => 'complete_report.csv', 'mime' => 'text/csv']);

        return $email;
    }

    protected function sendEmail($email): void
    {
        Mail::send($email);
    }
}
