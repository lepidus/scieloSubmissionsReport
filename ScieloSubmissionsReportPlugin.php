<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/ScieloSubmissionsReportPlugin.inc.php
 *
 * Copyright (c) 2019-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @class ScieloSubmissionsReportPlugin
 *
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report plugin
 */

namespace APP\plugins\reports\scieloSubmissionsReport;

use APP\plugins\reports\scieloSubmissionsReport\classes\form\ScieloSubmissionsReportSettingsForm;
use PKP\config\Config;
use PKP\core\JSONMessage;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\Hook;
use PKP\plugins\ReportPlugin;

class ScieloSubmissionsReportPlugin extends ReportPlugin
{
    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && Config::getVar('general', 'installed')) {
            $this->addLocaleData();

            Hook::add('AcronPlugin::parseCronTab', [$this, 'addPluginTasksToCrontab']);
        }
        return $success;
    }

    public function getName()
    {
        return 'scielosubmissionsreportplugin';
    }

    public function getDisplayName()
    {
        return __('plugins.reports.scieloSubmissionsReport.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.scieloSubmissionsReport.description');
    }

    public function addPluginTasksToCrontab($hookName, $args)
    {
        $taskFilesPath = &$args[0];
        $taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';
        return false;
    }

    public function display($args, $request)
    {
        $form = new ScieloSubmissionsReportForm($this);
        $form->initData();
        if ($request->isPost($request)) {
            $reportParams = $request->getUserVars();
            $validationResult = $form->validateReportData($reportParams);
            if ($validationResult) {
                $form->generateReport($request);
            }
        } else {
            $dateStart = date('Y-01-01');
            $dateEnd = date('Y-m-d');
            $form->display($request, 'scieloSubmissionsReportPlugin.tpl', [$dateStart, $dateEnd]);
        }
    }

    public function getActions($request, $actionArgs)
    {
        $router = $request->getRouter();
        $actions = array_merge(
            $this->getEnabled() ? [
                new LinkAction(
                    'pluginSettings',
                    new AjaxModal(
                        $router->url($request, null, null, 'manage', null, ['verb' => 'pluginSettings', 'plugin' => $this->getName(), 'category' => 'reports']),
                        __('plugins.reports.scieloSubmissionsReport.settings.title')
                    ),
                    __('manager.plugins.settings'),
                )
            ] : [],
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
