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
	
function dataInicialMenorqueFinal($dataStart,$dataEnd){
	if($dataStart<=$dataEnd){
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

	private $_aplicacao;

	/**
	 * Constructor
	 * @param $plugin ReviewersReport Manual payment plugin
	 */
	function __construct($plugin, $aplicacao) {
		$this->_plugin = $plugin;
		$this->_aplicacao = $aplicacao;
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

	private function imprimeCabecalhoCSV($fp) {
		$cabecalho = [
			__("plugins.reports.scieloSubmissionsReport.header.submissionId"),
			__("submission.submissionTitle"),
			__("submission.submitter"),
			__("common.dateSubmitted"),
			__("common.dateDecided"),
			__("plugins.reports.scieloSubmissionsReport.header.daysChangeStatus"),
			__("plugins.reports.scieloSubmissionsReport.header.submissionStatus"),
			__("plugins.reports.scieloSubmissionsReport.header.areaModerator"),
			__("plugins.reports.scieloSubmissionsReport.header.moderators"),
			__("plugins.reports.scieloSubmissionsReport.header.section"),
			__("common.language"),
			__("submission.authors"),
		];
		
		if($this->_aplicacao == "ops") {
			$cabecalho = array_merge($cabecalho,[
				__("plugins.reports.scieloSubmissionsReport.header.publicationStatus"),
				__("plugins.reports.scieloSubmissionsReport.header.publicationDOI"),
				__("submission.notes"),
			]);
		}
		else if($this->_aplicacao == "ojs") {
			$cabecalho = array_merge($cabecalho,[
				__("plugins.reports.scieloSubmissionsReport.header.reviews"),
			]);
		}

		fputcsv($fp, $cabecalho);
	}

	function generateReport($request, $sessions, $dataSubmissaoInicial = null, $dataSubmissaoFinal = null, $dataDecisaoInicial = null, $dataDecisaoFinal = null){
		if($dataSubmissaoInicial && !dataInicialMenorqueFinal($dataSubmissaoInicial,$dataSubmissaoFinal)){
			echo __('plugins.reports.scieloSubmissionsReport.warning.errorSubmittedDate'); 
			return;
		}
		
		if($dataDecisaoInicial && !dataInicialMenorqueFinal($dataDecisaoInicial,$dataDecisaoFinal)){
			echo __('plugins.reports.scieloSubmissionsReport.warning.errorDecisionDate'); 
			return;
		}

		$journal = $request->getJournal();
		header('content-type: text/comma-separated-values');
		$acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $journal->getLocalizedAcronym());
		header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
		$scieloSubmissionsReportDAO = DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		
		$dadosSubmissoes = $scieloSubmissionsReportDAO->obterRelatorioComSecoes($this->_aplicacao, $journal->getId(),$dataSubmissaoInicial,$dataSubmissaoFinal,$dataDecisaoInicial,$dataDecisaoFinal,$sessions);
		$fp = fopen('php://output', 'wt');
		$this->imprimeCabecalhoCSV($fp);
		foreach($dadosSubmissoes as $linhaSubmissao){
			fputcsv($fp, $linhaSubmissao);
		}
		fclose($fp);
		
	}

    function display($request= NULL, $template = NULL) {
		$templateManager = TemplateManager::getManager();
		$reviewerReportDao = DAORegistry::getDAO("ScieloSubmissionsReportDAO");
		$journalRequest = Application::getRequest();
		$journal = $journalRequest->getJournal();
		$sessions = $reviewerReportDao->obterSecoes($journal->getId());
		$sessions_options = $reviewerReportDao->obterOpcoesSecoes($journal->getId());
		$templateManager->assign('sessions',$sessions);
		$templateManager->assign('sessions_options',$sessions_options);
		$templateManager->assign('years', array(0=>$request[0], 1=>$request[1]));
		$templateManager->display($this->_plugin->getTemplateResource('scieloSubmissionsReportPlugin.tpl'));
	}
 }

?>