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

        if($application == 'ops' && !is_null($recipientEmail)) {
            $sectionsIds = $this->getAllSectionsIds($context->getId());
            $locale = 'pt_BR';
            $plugin->addLocaleData($locale);
            AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION);
            $includeViews = true;

            $beginningDate = '2020-04-01';
            $endDate = date("Y-m-d");
            $submissionDateInterval = new ClosedDateInterval($beginningDate, $endDate);
            
            $reportFactory = new ScieloSubmissionsReportFactory($application, $context->getId(), $sectionsIds, $submissionDateInterval, null, $locale, $includeViews);
            $report = $reportFactory->createReport();

            $acronym = $context->getLocalizedData('acronym', $locale);
            $reportFilePath = DIRECTORY_SEPARATOR . "tmp" .  DIRECTORY_SEPARATOR . "{$acronym}_complete_report.csv";
            $csvFile = fopen($reportFilePath, 'wt');
            $report->buildCSV($csvFile);

            $email = $this->createReportEmail($context, $locale, $recipientEmail, $reportFilePath);
            $email->send();
        }
        
        return true;
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

    private function createReportEmail($context, $locale, $recipientEmail, $reportFilePath)
    {
        $email = new Mail();

        $fromName = $context->getLocalizedData('name', $locale);
        $fromEmail = $context->getData('contactEmail');
        $email->setFrom($fromEmail, $fromName);

        $email->setRecipients([
            [
                'name' => '',
                'email' => $recipientEmail,
            ],
        ]);
        
        $subject = __('plugins.reports.scieloSubmissionsReport.reportEmail.subject', ['contextName' => $fromName], $locale);
        $body = __('plugins.reports.scieloSubmissionsReport.reportEmail.body', ['contextName' => $fromName], $locale);
        $email->setSubject($subject);
        $email->setBody($body);

        $email->addAttachment($reportFilePath);

        return $email;
    }

    private function getEmailRecipient($context): ?string
    {
        PluginRegistry::loadCategory('reports');
        $plugin = PluginRegistry::getPlugin('reports', 'scielosubmissionsreportplugin');
        return $plugin->getSetting($context->getId(), 'recipientEmail');
    }
}
