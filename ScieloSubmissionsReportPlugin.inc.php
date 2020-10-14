<?php

/**
 * @file plugins/reports/scieloSubmissions/ScieloSubmissionsReportPlugin.inc.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScieloSubmissionsReportPlugin
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report plugin
 */

import('lib.pkp.classes.plugins.ReportPlugin');
import('classes.submission.Submission');
class ScieloSubmissionsReportPlugin extends ReportPlugin {
	//public $form;
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);

		if ($success && Config::getVar('general', 'installed')) {

			$this->import('ScieloSubmissionsReportForm');
			$this->import('ScieloSubmissionsReportDAO');
			
			$form = new ScieloSubmissionsReportForm($this);
			$scieloSubmissionsReportDAO = new ScieloSubmissionsReportDAO();
			DAORegistry::registerDAO('ScieloSubmissionsReportDAO', $scieloSubmissionsReportDAO);
			
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
	function getName() {
		return 'SciELOSubmissionsReportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.scieloSubmissionsReport.displayName');
	}

	/**
	 * @copydoc Plugin::getDescriptionName()
	 */
	function getDescription() {
		return __('plugins.reports.scieloSubmissionsReport.description');
	}


	function getSettingsForm($context) {
		$this->import('ScieloSubmissionsReportForm');
		return new ScieloSubmissionsReportForm($this);
	}
	/**
	 * @copydoc ReportPlugin::display()
	 */
	function display($args, $request) {
		$form    = new ScieloSubmissionsReportForm($this);
		$dateStart = date("Y-01-01");
		$dateEnd   = date("Y-m-d");
		$datas     = array($dateStart, $dateEnd);

		$form->initData();
		$journal = $request->getJournal();
		$acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $journal->getLocalizedAcronym());
		import('classes.statistics.StatisticsHelper');
		$requestHandler = new PKPRequest();
		if ($requestHandler->isPost($request)) {
			$postVars = $requestHandler->getUserVars($request);
			if ($postVars['generate'] === "1") {
				array_key_exists('sessions', $postVars) ? $sessions = $postVars['sessions'] : $sessions = NULL;
				$form->generateReport($postVars['dataSubmissaoInicial'],$postVars['dataSubmissaoFinal'],$postVars['dataDecisaoInicial'],$postVars['dataDecisaoFinal'],$request, $sessions);
			}

		}
		else{
			$form->display($datas);
		}
		
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
	}

}
