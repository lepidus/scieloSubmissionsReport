<?php

/**
 * @file plugins/reports/submissions/SubmissionReportPlugin.inc.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionReportPlugin
 * @ingroup plugins_reports_article
 *
 * @brief Submission report plugin
 */

import('lib.pkp.classes.plugins.ReportPlugin');
import('classes.submission.Submission');
class SubmissionReportPlugin extends ReportPlugin {
	//public $form;
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		$success = parent::register($category, $path, $mainContextId);

		if ($success && Config::getVar('general', 'installed')) {

			$this->import('SubmissionReportForm');
			$this->import('SubmissionReportDAO');
			
			$form = new SubmissionReportForm($this);
			$submissionReportDAO = new SubmissionReportDAO();
			DAORegistry::registerDAO('SubmissionReportDAO', $submissionReportDAO);
			
			$request = Application::getRequest();
			$url = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/templates/submissionStyleSheet.css';
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->addStyleSheet('submissionStyleSheet', $url, array(
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
		return 'SubmissionReportPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.reports.submissions.displayName');
	}

	/**
	 * @copydoc Plugin::getDescriptionName()
	 */
	function getDescription() {
		return __('plugins.reports.submissions.description');
	}


	function getSettingsForm($context) {
		$this->import('SubmissionReportForm');
		return new SubmissionReportForm($this);
	}
	/**
	 * @copydoc ReportPlugin::display()
	 */
	function display($args, $request) {
		$form    = new SubmissionReportForm($this);
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
				$form->generateReport($postVars['dataInicio'],$postVars['dataFim'],$request, $sessions);
			}

		}
		else{
			$form->display($datas);
		}
		
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_PKP_READER);
	}

}
