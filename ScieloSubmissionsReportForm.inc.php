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
	/*
	* Function to verify date
	* @param String data formato Y-m-d
	* @return boolean
	*/

	function validaData($data){
		$d = DateTime::createFromFormat('Y-m-d', $data);
		if($d && $d->format('Y-m-d') == $data){
			return true;
		}else{
			return false;
		}
	}
	
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

	/**
	 * Constructor
	 * @param $plugin ReviewersReport Manual payment plugin
	 */
	function __construct($plugin) {
		$this->_plugin = $plugin;
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
	function generateReport($dataSubmissaoInicial, $dataSubmissaoFinal, $dataDecisaoInicial, $dataDecisaoFinal, $request,$sessions){
		$journal = $request->getJournal();
		header('content-type: text/comma-separated-values');
		$acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $journal->getLocalizedAcronym());
		header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
		$scieloSubmissionsReportDAO = DAORegistry::getDAO('ScieloSubmissionsReportDAO');
		
		if(validaData($dataSubmissaoInicial) && 
			validaData($dataSubmissaoFinal) &&
			validaData($dataDecisaoInicial) &&
			validaData($dataDecisaoFinal) &&
			dataInicialMenorqueFinal($dataSubmissaoInicial,$dataSubmissaoFinal) &&
			dataInicialMenorqueFinal($dataDecisaoInicial,$dataDecisaoFinal)
		){
			$dadosSubmissoes = $scieloSubmissionsReportDAO->getReportWithSections($journal->getId(),$dataSubmissaoInicial,$dataSubmissaoFinal,$dataDecisaoInicial,$dataDecisaoFinal,$sessions);
			 
			$fp = fopen('php://output', 'wt');
			foreach($dadosSubmissoes as $linhaSubmissao){
				fputcsv($fp, $linhaSubmissao);
			}
			fclose($fp);
		}
		else{
			echo "Datas invalidas por favor coloque uma data correta;";
		}
	}

    function display($request= NULL, $template = NULL) {
		$templateManager = TemplateManager::getManager();
		$reviewerReportDao = DAORegistry::getDAO("ScieloSubmissionsReportDAO");
		$journalRequest = Application::getRequest();
		$journal = $journalRequest->getJournal();
		$sessions = $reviewerReportDao->getSession($journal->getId());
		$sessions_options = $reviewerReportDao->getSectionsOptions($journal->getId());
		$templateManager->assign('sessions',$sessions);
		$templateManager->assign('sessions_options',$sessions_options);
		$templateManager->assign('years', array(0=>$request[0], 1=>$request[1]));
		$templateManager->display($this->_plugin->getTemplateResource('scieloSubmissionsReportPlugin.tpl'));
	}
 }

?>