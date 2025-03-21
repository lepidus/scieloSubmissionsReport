<?php

use APP\plugins\reports\scieloSubmissionsReport\classes\ClosedDateInterval;
use APP\plugins\reports\scieloSubmissionsReport\classes\ScieloSubmissionsReportFactory;
use PKP\mail\Mailable;
use Illuminate\Support\Facades\Mail;
use PKP\scheduledTask\ScheduledTask;

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
            $locale = AppLocale::getLocale();
            $this->loadLocalesForTask($plugin, $locale);

            $report = $this->getReport($application, $context, $locale);
            $reportFilePath = $this->writeReportFile($context, $report);

            $email = $this->createReportEmail($context, $recipientEmails, $reportFilePath);
            Mail::send($email);
        }

        return true;
    }

    private function loadLocalesForTask($plugin, $locale)
    {
        $plugin->addLocaleData($locale);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON);
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
        $sections = Services::get('section')->getSectionList($contextId);

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
        $email = new Mail();

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
