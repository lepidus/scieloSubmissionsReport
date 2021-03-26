<?php
 /**
 * @file plugins/reports/scieloSubmissions/ScieloSubmissionsReportForm.inc.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScieloSubmissionsReportDAO
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report Form
 */
import('lib.pkp.classes.form.Form');
require('ScieloSubmissionsReportDAO.inc.php');
	
function startDateLessThanEndDate($startDate,$endDate){
	if($startDate<=$endDate){
		return true;
	}
	else{
		return false;
	}
}

class ScieloSubmissionsReportForm extends Form {
    /* @var int Associated context ID */
	private $_contextId;

	/* @var ReviewersReport  */
	private $_plugin;

	private $_application;

	/**
	 * Constructor
	 * @param $plugin ReviewersReport Manual payment plugin
	 */
	function __construct($plugin, $application) {
		$this->_plugin = $plugin;
		$this->_application = $application;
		parent::__construct($plugin->getTemplateResource('scieloSubmissionsReportPlugin.tpl'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
    }

    /**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->_contextId;
		$plugin = $this->_plugin;
        $this->setData('scieloSubmissionsReport', $plugin->getSetting($contextId, 'scieloSubmissionsReport'));
	}

	private function printCsvHeader($fp) {
		$header = [
			__("plugins.reports.scieloSubmissionsReport.header.submissionId"),
			__("submission.submissionTitle"),
			__("submission.submitter"),
			__("common.dateSubmitted"),
			__("plugins.reports.scieloSubmissionsReport.header.daysChangeStatus"),
			__("plugins.reports.scieloSubmissionsReport.header.submissionStatus"),
		];
		
		if($this->_application == "ops") {
			$header = array_merge($header,[
				__("plugins.reports.scieloSubmissionsReport.header.areaModerator"),
				__("plugins.reports.scieloSubmissionsReport.header.moderators"),
				__("plugins.reports.scieloSubmissionsReport.header.section"),
				__("common.language"),
				__("submission.authors"),
				__("plugins.reports.scieloSubmissionsReport.header.publicationStatus"),
				__("plugins.reports.scieloSubmissionsReport.header.publicationDOI"),
				__("submission.notes"),
				__("plugins.reports.scieloSubmissionsReport.header.FinalDecision"),
			]);
		}
		else if($this->_application == "ojs") {
			$header = array_merge($header,[
				__("plugins.reports.scieloSubmissionsReport.header.journalEditors"),
				__("plugins.reports.scieloSubmissionsReport.header.sectionEditor"),
				__("plugins.reports.scieloSubmissionsReport.header.section"),
				__("common.language"),
				__("submission.authors"),
				__("plugins.reports.scieloSubmissionsReport.header.reviews"),
				__("plugins.reports.scieloSubmissionsReport.header.LastDecision"),
				__("plugins.reports.scieloSubmissionsReport.header.FinalDecision"),
			]);
		}
		$header = array_merge($header,[ 
			__("plugins.reports.scieloSubmissionsReport.header.finalDecisionDate"),
			__("plugins.reports.scieloSubmissionsReport.header.ReviewingTime"),
			__("plugins.reports.scieloSubmissionsReport.header.SubmissionAndFinalDecisionDateInterval"),
		]);

		fputcsv($fp, $header);
	}

	function generateReport($request, $sessions, $initialSubmissionDate = null, $finalSubmissionDate = null, $initialDecisionDate = null, $finalDecisionDate = null){
		if($initialSubmissionDate && !startDateLessThanEndDate($initialSubmissionDate,$finalSubmissionDate)){
			echo __('plugins.reports.scieloSubmissionsReport.warning.errorSubmittedDate'); 
			return;
		}
		
		if($initialDecisionDate && !startDateLessThanEndDate($initialDecisionDate,$finalDecisionDate)){
			echo __('plugins.reports.scieloSubmissionsReport.warning.errorDecisionDate'); 
			return;
		}

		$journal = $request->getJournal();
		header('content-type: text/comma-separated-values');
		$acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $journal->getLocalizedAcronym());
		header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
		$scieloSubmissionsReportDAO = DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		
		$submissionsData = $scieloSubmissionsReportDAO->getReportWithSections($this->_application, $journal->getId(),$initialSubmissionDate,$finalSubmissionDate,$initialDecisionDate,$finalDecisionDate,$sessions);
		$fp = fopen('php://output', 'wt');
		$this->printCsvHeader($fp);
		foreach($submissionsData as $submissionLine){
			fputcsv($fp, $submissionLine);
		}
		fclose($fp);
		
	}

    function display($request= NULL, $template = NULL) {
		$templateManager = TemplateManager::getManager();
		$reviewerReportDao = DAORegistry::getDAO("ScieloSubmissionsReportDAO");
		$journalRequest = Application::getRequest();
		$journal = $journalRequest->getJournal();
		$sessions = $reviewerReportDao->getSections($journal->getId());
		$sessions_options = $reviewerReportDao->getSectionsOptions($journal->getId());
		$templateManager->assign('sessions',$sessions);
		$templateManager->assign('sessions_options',$sessions_options);
		$templateManager->assign('years', array(0=>$request[0], 1=>$request[1]));
		$templateManager->display($this->_plugin->getTemplateResource('scieloSubmissionsReportPlugin.tpl'));
	}
 }

?>