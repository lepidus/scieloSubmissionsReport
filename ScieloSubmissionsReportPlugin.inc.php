<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/ScieloSubmissionsReportPlugin.inc.php
 *
 * Copyright (c) 2019-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class ScieloSubmissionsReportPlugin
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report plugin
 */

import('lib.pkp.classes.plugins.ReportPlugin');
import('classes.submission.Submission');
import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');

class ScieloSubmissionsReportPlugin extends ReportPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && Config::getVar('general', 'installed')) {
            $this->import('ScieloSubmissionsReportForm');

            $application = substr(Application::getName(), 0, 3);
            $form = new ScieloSubmissionsReportForm($this, $application);

            $request = Application::getRequest();
            $url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/templates/scieloSubmissionsStyleSheet.css';
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->addStyleSheet('scieloSubmissionsStyleSheet', $url, array(
                'priority' => STYLE_SEQUENCE_CORE,
                'contexts' => 'backend',
            ));

            $this->addLocaleData();
            return $success;
        }
    }

    /**
     * Get the name of this plugin. The name must be unique within
     * its category.
     * @return String name of plugin
     */
    public function getName()
    {
        return 'ScieloSubmissionsReportPlugin';
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.reports.scieloSubmissionsReport.displayName');
    }

    /**
     * @copydoc Plugin::getDescriptionName()
     */
    public function getDescription()
    {
        return __('plugins.reports.scieloSubmissionsReport.description');
    }

    public function getSettingsForm($context)
    {
        $this->import('ScieloSubmissionsReportForm');
        return new ScieloSubmissionsReportForm($this);
    }

    private function validateDateInterval($startInterval, $endInterval, $errorMessage)
    {
        $dateInterval = new ClosedDateInterval($startInterval, $endInterval);
        if (!$dateInterval->isValid()) {
            echo __($errorMessage);
            return null;
        }
        return $dateInterval;
    }

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        $application = substr(Application::getName(), 0, 3);
        $form    = new ScieloSubmissionsReportForm($this, $application);
        $dateStart = date("Y-01-01");
        $dateEnd   = date("Y-m-d");
        $dates     = array($dateStart, $dateEnd);

        $form->initData();
        import('classes.statistics.StatisticsHelper');
        $requestHandler = new PKPRequest();
        if ($requestHandler->isPost($request)) {
            $postVars = $requestHandler->getUserVars($request);
            if ($postVars['generate'] === "1") {
                array_key_exists('sections', $postVars) ? $sections = $postVars['sections'] : $sections = null;
                $filterType = $postVars['selectFilterTypeDate'];

                if ($filterType == 'filterBySubmission' || $filterType == 'filterByBoth') {
                    $submissionDateInterval = $this->validateDateInterval($postVars['startSubmissionDateInterval'], $postVars['endSubmissionDateInterval'], 'plugins.reports.scieloSubmissionsReport.warning.errorSubmittedDate');
                    if(is_null($submissionDateInterval))
                        return;
                    else
                        $form->setSubmissionDateInterval($submissionDateInterval);
                }
                
                if ($filterType == 'filterByFinalDecision' || $filterType == 'filterByBoth') {
                    $finalDecisionDateInterval = $this->validateDateInterval($postVars['startFinalDecisionDateInterval'], $postVars['endFinalDecisionDateInterval'], 'plugins.reports.scieloSubmissionsReport.warning.errorDecisionDate');
                    if(is_null($finalDecisionDateInterval))
                        return;
                    else
                        $form->setFinalDecisionDateInterval($finalDecisionDateInterval);
                }

                $form->generateReport($request, $sections);
            }
        } else {
            $form->display($request, 'scieloSubmissionsReportPlugin.tpl', $dates);
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
    }
}
