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

    /**
     * @copydoc ReportPlugin::display()
     */
    public function display($args, $request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
        $form = new ScieloSubmissionsReportForm($this);
        $form->initData();
        $requestHandler = new PKPRequest();
        if ($requestHandler->isPost($request)) {
            $reportParams = $requestHandler->getUserVars($request);
            $validationResult = $form->validateReportData($reportParams);
            if ($validationResult) {
                $form->generateReport($request);
            }
        } else {
            $dateStart = date("Y-01-01");
            $dateEnd   = date("Y-m-d");
            $form->display($request, 'scieloSubmissionsReportPlugin.tpl', array($dateStart, $dateEnd));
        }
    }
}
