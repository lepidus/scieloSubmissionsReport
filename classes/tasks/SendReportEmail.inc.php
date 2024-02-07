<?php

import('lib.pkp.classes.mail.Mail');
import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class SendReportEmail extends ScheduledTask
{
    public function executeActions()
    {
        $application = substr(Application::getName(), 0, 3);
        $context = Application::get()->getRequest()->getContext();
        PluginRegistry::loadCategory('reports');
        $plugin = PluginRegistry::getPlugin('reports', 'scielosubmissionsreportplugin');

        $recipientEmail = $plugin->getSetting($context->getId(), 'recipientEmail');

        if ($application == 'ops' && !is_null($recipientEmail)) {
            $locale = AppLocale::getLocale();
            $this->loadLocalesForTask($plugin, $locale);

            $report = $this->getReport($application, $context, $locale);
            $reportFilePath = $this->writeReportFile($context, $report);

            $email = $this->createReportEmail($context, $recipientEmail, $reportFilePath);
            $email->send();
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
        $endDate = date("Y-m-d");
        $submissionDateInterval = new ClosedDateInterval($beginningDate, $endDate);

        $reportFactory = new ScieloSubmissionsReportFactory($application, $context->getId(), $sectionsIds, $submissionDateInterval, null, $locale, $includeViews);
        return $reportFactory->createReport();
    }

    private function writeReportFile($context, $report): string
    {
        $acronym = $context->getLocalizedData('acronym');
        $reportFilePath = DIRECTORY_SEPARATOR . "tmp" .  DIRECTORY_SEPARATOR . "{$acronym}_complete_report.csv";
        $csvFile = fopen($reportFilePath, 'wt');
        $report->buildCSV($csvFile);

        return $reportFilePath;
    }

    private function getAllSectionsIds($contextId)
    {
        $sections = Services::get('section')->getSectionList($contextId);

        $sectionsIds = array();
        foreach ($sections as $section) {
            $sectionsIds[] = $section['id'];
        }
        return $sectionsIds;
    }

    private function createReportEmail($context, $recipientEmail, $reportFilePath)
    {
        $email = new Mail();

        $fromName = $context->getLocalizedData('name');
        $fromEmail = $context->getData('contactEmail');
        $email->setFrom($fromEmail, $fromName);

        $email->setRecipients([
            [
                'name' => '',
                'email' => $recipientEmail,
            ],
        ]);

        $subject = __('plugins.reports.scieloSubmissionsReport.reportEmail.subject', ['contextName' => $fromName]);
        $body = __('plugins.reports.scieloSubmissionsReport.reportEmail.body', ['contextName' => $fromName]);
        $email->setSubject($subject);
        $email->setBody($body);

        $email->addAttachment($reportFilePath);

        return $email;
    }
}
