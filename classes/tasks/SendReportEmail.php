<?php

namespace APP\plugins\reports\scieloSubmissionsReport\classes\tasks;

use APP\core\Application;
use APP\facades\Repo;
use PKP\facades\Locale;
use PKP\mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PKP\plugins\PluginRegistry;
use PKP\scheduledTask\ScheduledTask;
use APP\plugins\reports\scieloSubmissionsReport\classes\ClosedDateInterval;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionsReportFactory;

class SendReportEmail extends ScheduledTask
{
    public $plugin;

    public function __construct(array $args = [])
    {
        PluginRegistry::loadCategory('reports');
        $this->plugin = PluginRegistry::getPlugin('reports', 'scielosubmissionsreportplugin');

        parent::__construct($args);
    }

    protected function executeActions(): bool
    {
        $applicationName = Application::getName();
        $context = Application::get()->getRequest()->getContext();
        $recipientEmails = $this->getRecipientEmails($this->plugin, $context->getId());

        if ($applicationName == 'ops' && !empty($recipientEmails)) {
            $locale = Locale::getLocale();
            $this->plugin->addLocaleData($locale);

            $report = $this->getReport($applicationName, $context, $locale);
            $reportFilePath = $this->writeReportFile($context, $report);

            $email = $this->createReportEmail($context, $recipientEmails, $reportFilePath);
            Mail::send($email);
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

    private function writeReportFile($context, $report): string
    {
        $acronym = $context->getLocalizedData('acronym');
        $reportFilePath = DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . "{$acronym}_complete_report.csv";
        $csvFile = fopen($reportFilePath, 'wt');
        $report->buildCSV($csvFile);

        return $reportFilePath;
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

    private function createReportEmail($context, $recipientEmails, $reportFilePath)
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

        $email->attach($reportFilePath);

        return $email;
    }
}
