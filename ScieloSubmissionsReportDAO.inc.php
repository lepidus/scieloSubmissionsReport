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
    public function getReportWithSections($journalId, $dataSubmissaoInicial, $dataSubmissaoFinal, $dataDecisaoInicial, $dataDecisaoFinal, $sections) {
        $querySubmissoes = "SELECT submission_id, DATEDIFF(date_last_activity,date_submitted) AS dias_mudanca_status FROM submissions WHERE context_id = {$journalId} AND date_submitted IS NOT NULL";
        $querySubmissoes .= " AND date_submitted >= '{$dataSubmissaoInicial} 23:59:59' AND date_submitted <= '{$dataSubmissaoFinal} 23:59:59' AND date_last_activity >= '{$dataDecisaoInicial}  23:59:59' AND date_last_activity <= '{$dataDecisaoFinal} 23:59:59'";
        $resultSubmissoes = $this->retrieve($querySubmissoes);

        //Adicionar um echo para imprimir os títulos de cada coluna
        $dadosSubmissoes = array();
        while($rowSubmissao = $resultSubmissoes->FetchRow()) {
            $arraySubmissao = $this->getSubmissionArray($journalId, $rowSubmissao['submission_id'], $rowSubmissao['dias_mudanca_status'], $sections);

            if($arraySubmissao)
                $dadosSubmissoes[] = $arraySubmissao;
        }

        return $dadosSubmissoes;
    }

    private function getSubmissionArray($journalId, $submissionId, $diasMudancaStatus, $sections) {
        $submissao = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $locale = AppLocale::getLocale();
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

        $statusSubmissao = __($submissao->getStatusKey());
        $titulo = $submissao->getTitle($locale);
        $dataSubmissao = $submissao->getDateSubmitted();
        $dataDecisao = $submissao->getDateStatusModified();
        $idioma = $submissao->getLocale();
        $secao = DAORegistry::getDAO('SectionDAO')->getById( $submissao->getSectionId() );
        $nomeSecao = $secao->getTitle($locale);
        $nomeJournal = Application::getContextDAO()->getById($journalId)->getLocalizedName();

        if(!in_array($nomeSecao, $sections))
            return null;

        $moderadores = $this->obterModeradores($submissionId);
        $autores = $this->obterAutores($submissao->getAuthors());
        $arraySubmissao = [$submissionId,$titulo,$dataSubmissao,$dataDecisao,$diasMudancaStatus,$statusSubmissao,$moderadores,$nomeJournal,$nomeSecao,$idioma, $autores];

        $resultNotes = $this->retrieve("SELECT contents FROM notes WHERE assoc_type = 1048585 AND assoc_id = {$submissao->getId()}");
        $notas = "";
        if($resultNotes->NumRows() == 0) {
            $notas = 'Sem Notas';
        }
        else{
            while($note = $resultNotes->FetchRow()) {
                $note = $note[0];
                $notas .= "Nota: " . trim(preg_replace('/\s+/', ' ', $note));
            }
        }
        $arraySubmissao[] = $notas;

        return $arraySubmissao;
    }

    private function obterAutores($autores) {
        $dadosAutores = array();
        foreach($autores as $autor) {
            $nomeAutor = $autor->getLocalizedGivenName() . " " . $autor->getLocalizedFamilyName();
            $paisAutor = $autor->getCountryLocalized();
            $afiliacaoAutor = $autor->getLocalizedAffiliation();

            $dadosAutores[] = implode(", ", [$nomeAutor, $paisAutor, $afiliacaoAutor]);
        }

        return implode("; ", $dadosAutores);
    }

    private function obterModeradores($submissionId) {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $iteradorModeradores = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR);
        $idModeradores = $moderadores = array();

        while($moderador = $iteradorModeradores->next()){
            if(!in_array($moderador->getId(), $idModeradores)){
                $idModeradores[] = $moderador->getId();
                $userDao = DAORegistry::getDAO('UserDAO');
                $usuario = $userDao->getById($moderador->getUserId());
                $moderadores[] = $usuario->getFullName();
            }
        }

        return (!empty($moderadores)) ? (implode(", ", $moderadores)) : (__("plugins.reports.scieloSubmissionsReport.warning.noModerators"));
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
