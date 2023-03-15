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

    public function getName()
    {
        return 'scielosubmissionsreportplugin';
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

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        import('lib.pkp.classes.linkAction.request.AjaxModal');
        $actions =  array_merge(
            $this->getEnabled()?array(
                new LinkAction(
                    'pluginSettings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, array('verb' => 'pluginSettings', 'plugin' => $this->getName(), 'category' => 'reports')),
                        __('plugins.reports.scieloSubmissionsReport.settings.title')
                    ),
                    __('manager.plugins.settings'),
                )
            ):array(),
            parent::getActions($request, $actionArgs)
        );
        return $actions;
    }

    public function manage($args, $request)
    {
        $context = $request->getContext();
        $contextId = ($context == null) ? 0 : $context->getId();

        switch ($request->getUserVar('verb')) {
            case 'pluginSettings':
                $this->import('classes.form.ScieloSubmissionsReportSettingsForm');
                $form = new ScieloSubmissionsReportSettingsForm($this, $contextId);
                if ($request->getUserVar('save')) {
                    $form->readInputData();
                    $form->execute();
                    return new JSONMessage(true);
                } else {
                    $form->initData();
                }
                return new JSONMessage(true, $form->fetch($request));
        }
        return parent::manage($args, $request);
    }
}
