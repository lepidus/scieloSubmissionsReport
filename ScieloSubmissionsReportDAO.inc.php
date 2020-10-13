<?php

/**
 * @file plugins/reports/scieloSubmissions/ScieloSubmissionsReportDAO.inc.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ScieloSubmissionsReportDAO
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report DAO
 */

import('lib.pkp.classes.db.DBRowIterator');
class ScieloSubmissionsReportDAO extends DAO
{

    /**
     * Get the submission report data.
     * @param $journalId int
     * @return array
     */
    public function getReportWithSections($journalId, $dataStart, $dataEnd, $sections)
    {
        $locale = AppLocale::getLocale();
        $this->getReports($journalId, $dataStart, $dataEnd, $dataStart, $dataEnd);
        /*if (is_null($sections)) {
            $queryResult = " SELECT submission_id AS Id, ss.setting_value AS 'Seção', ";
            $queryResult.= " CASE STATUS WHEN '1' THEN 'Avaliação' WHEN '4' THEN 'Rejeitado' WHEN '3' THEN 'Publicado' END AS Status, ";
            $queryResult.= " CASE stage_id WHEN '1' THEN 'Submissão' WHEN '3' THEN 'Avaliação' WHEN '4' THEN 'Edição de texto' WHEN '5' THEN 'Editoração' END AS 'Estágio', ";
            $queryResult.= " date_submitted AS 'Data de submissão', date_last_activity 'Data da modificação de Status' , DATEDIFF(date_last_activity,date_submitted) AS 'Dias até a última mudança de status' ";
            $queryResult.= " FROM submissions s JOIN section_settings AS ss WHERE date_submitted BETWEEN ";
            $queryResult.= " '{$dataStart}' AND '{$dataEnd}' AND ss.setting_name = 'title' AND ss.locale = '{$locale}' AND s.section_id = ss.section_id AND s.context_id = '{$journalId}' ORDER BY date_submitted ASC ";

            $result = $this->retrieve($queryResult);
            echo $result;
        } else {

            $newSections = "'" . implode("','", $sections) . "'";

            $queryResult = " SELECT submission_id AS Id, ss.setting_value AS 'Seção', ";
            $queryResult.= " CASE STATUS WHEN '1' THEN 'Avaliação' WHEN '4' THEN 'Rejeitado' WHEN '3' THEN 'Publicado' END AS Status, ";
            $queryResult.= " CASE stage_id WHEN '1' THEN 'Submissão' WHEN '3' THEN 'Avaliação' WHEN '4' THEN 'Edição de texto' WHEN '5' THEN 'Editoração' END AS 'Estágio', ";
            $queryResult.= " date_submitted AS 'Data de submissão', date_last_activity 'Data da modificação de Status', DATEDIFF(date_last_activity,date_submitted) AS 'Dias até a última mudança de status' ";
            $queryResult.= " FROM submissions s JOIN section_settings AS ss WHERE date_submitted BETWEEN ";
            $queryResult.= " '{$dataStart}' AND '{$dataEnd}' AND ss.setting_name = 'title' ";
            $queryResult.= " AND ss.locale = '{$locale}' AND s.section_id = ss.section_id  AND ss.setting_value IN ({$newSections}) AND s.context_id = {$journalId} ORDER BY date_submitted ASC ";
            $result = $this->retrieve($queryResult);
            echo $result;   
        }*/
    }

    public function getReports($journalId, $dataSubmissaoInicial, $dataSubmissaoFinal, $dataDecisaoInicial, $dataDecisaoFinal) {
        $querySubmissoes = "SELECT submission_id, DATEDIFF(date_last_activity,date_submitted) AS dias_mudanca_status FROM submissions WHERE context_id = {$journalId} AND date_submitted IS NOT NULL";
        $querySubmissoes .= " AND date_submitted > '{$dataSubmissaoInicial}' AND date_submitted < '{$dataSubmissaoFinal}' AND date_last_activity > '{$dataDecisaoInicial}' AND date_last_activity < '{$dataDecisaoFinal}'";
        
        $resultSubmissoes = $this->retrieve($querySubmissoes);

        //Adicionar um echo para imprimir os títulos de cada coluna

        while($rowSubmissao = $resultSubmissoes->FetchRow()) {
            echo $this->getSubmissionString($journalId, $rowSubmissao['submission_id'], $rowSubmissao['dias_mudanca_status']) . "\n";
        }
    }

    private function getSubmissionString($journalId, $submissionId, $diasMudancaStatus) {
        $submissao = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $locale = AppLocale::getLocale();
        
        $statusSubmissao = __($submissao->getStatusKey());
        $titulo = $submissao->getTitle($locale);
        $dataSubmissao = $submissao->getDateSubmitted();
        $dataDecisao = $submissao->getDateStatusModified();
        $idioma = $submissao->getLocale();
        $secao = DAORegistry::getDAO('SectionDAO')->getById( $submissao->getSectionId() );
        $nomeSecao = $secao->getTitle($locale);
        $nomeJournal = Application::getContextDAO()->getById($journalId)->getLocalizedName();

        $resultNotes = $this->retrieve("SELECT contents FROM notes WHERE assoc_type = 1048585 AND assoc_id = {$submissao->getId()}");
        $notas = "";
        if($resultNotes->numRows == 0) {
            $notas = 'Sem Notas';
        }
        else{
            while($note = $resultNotes->FetchRow()) {
                $note = $note[0];
                $notas .= "Nota: " . trim(preg_replace('/\s+/', ' ', $note));
            }

        }

        $listaAutores = array();
        foreach($submissao->getAuthors() as $autor) {
            $nomeAutor = "Autor: " .  $autor->getFullName(true, false, $locale);
            $paisAutor = "País: " . $autor->getCountryLocalized();
            $afiliacaoAutor = "Afiliação: " . $autor->getLocalizedAffiliation();

            $listaAutores[] = "{$nomeAutor},{$paisAutor},{$afiliacaoAutor}";
        }
        $stringAutores = $this->stringVirgulas($listaAutores);

        return $this->stringVirgulas([
            $submissionId,$titulo,$dataSubmissao,$dataDecisao,$diasMudancaStatus,$statusSubmissao,$nomeJournal,$nomeSecao,$idioma,$stringAutores,$notas
        ]);
    }

    private function stringVirgulas($lista) {
        $retorno = "";
        $primeiro = true;

        foreach($lista as $elemento) {
            if($primeiro)
                $primeiro = false;
            else
                $retorno .= ",";

            $retorno .= $elemento;
        }

        return $retorno;
    }

    public function getSession($journalId)
    {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $newSections = array();
        foreach ($sections as $section) {
            $newSections[$section['title']] = $section['title'];
        }
        return $newSections;
    }

    public function getSectionsOptions($journalId)
    {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $sectionDao = DAORegistry::getDAO('SectionDAO');

        $newSectionsOptions = array();
        
        foreach ($sections as $section) {
            $sectionObject = $sectionDao->getById($section['id'], $journalId);
            if($sectionObject->getMetaReviewed() == 1){
                $newSectionsOptions[$sectionObject->getLocalizedTitle()] = $sectionObject->getLocalizedTitle();
            }
        }

        return $newSectionsOptions;
    }
}
