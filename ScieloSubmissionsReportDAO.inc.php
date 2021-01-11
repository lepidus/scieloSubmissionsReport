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
    public function obterRelatorioComSecoes($aplicacao, $journalId, $dataSubmissaoInicial, $dataSubmissaoFinal, $dataDecisaoInicial, $dataDecisaoFinal, $sections) {
        $querySubmissoes = "SELECT submission_id, DATEDIFF(date_last_activity,date_submitted) AS dias_mudanca_status FROM submissions WHERE context_id = {$journalId} AND date_submitted IS NOT NULL";
        $querySubmissoes .= " AND date_submitted >= '{$dataSubmissaoInicial} 23:59:59' AND date_submitted <= '{$dataSubmissaoFinal} 23:59:59' AND date_last_activity >= '{$dataDecisaoInicial}  23:59:59' AND date_last_activity <= '{$dataDecisaoFinal} 23:59:59'";
        $resultSubmissoes = $this->retrieve($querySubmissoes);

        //Adicionar um echo para imprimir os títulos de cada coluna
        $dadosSubmissoes = array();
        while($rowSubmissao = $resultSubmissoes->FetchRow()) {
            $dadosSubmissao = $this->obterDadosSubmissao($aplicacao, $journalId, $rowSubmissao['submission_id'], $rowSubmissao['dias_mudanca_status'], $sections);

            if($dadosSubmissao)
                $dadosSubmissoes[] = $dadosSubmissao;
        }

        return $dadosSubmissoes;
    }

    private function obterDadosSubmissao($aplicacao, $journalId, $submissionId, $diasMudancaStatus, $sections) {
        $submissao = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $dadosSubmissao = $this->obterDadosComunsSubmissao($submissao, $journalId, $diasMudancaStatus, $sections);
        
        if(!$dadosSubmissao) return null;

        if($aplicacao == 'ops') {
            list($estadoPublicacao, $doiPublicacao) = $this->obterDadosPublicacao($submissao);
            $notas = $this->obterNotas($submissionId);
            $dadosSubmissao = array_merge($dadosSubmissao, [$estadoPublicacao,$doiPublicacao,$notas]);
        }
        else if($aplicacao == 'ojs') {
            list($avaliacoesCompletas, $avaliacoes) = $this->obterAvaliacoes($submissionId);

            if(!$avaliacoesCompletas)
                return null;

            $dadosSubmissao = array_merge($dadosSubmissao, [$avaliacoes]);
        }

        return $dadosSubmissao;
    }

    private function obterDadosComunsSubmissao($submissao, $journalId, $diasMudancaStatus, $sections) {
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
        list($moderadorArea_EditorRevista, $moderadores_editorSecao) = $this->controladorModeradorEditor($submissao);
        $usuarioSubmissor = $this->obterUsuarioSubmissor($submissao->getId());
        $autores = $this->obterAutores($submissao->getAuthors());

        if(!in_array($nomeSecao, $sections))
            return null;
        
        return [$submissao->getId(),$titulo,$usuarioSubmissor,$dataSubmissao,$dataDecisao,$diasMudancaStatus,$statusSubmissao,$moderadorArea_EditorRevista,$moderadores_editorSecao,$nomeSecao,$idioma,$autores];
    }

    private function controladorModeradorEditor($submissao){
        $aplicacaoNome = Application::getName();
        $aplicacaoNomePadrao = strtolower($aplicacaoNome);
        
        if(strstr($aplicacaoNomePadrao,'ops'))
            return $this->obterModeradores($submissao->getId());
        if(strstr($aplicacaoNomePadrao,'ojs'))
            return $this->obterEditores($submissao->getId());
    }

    private function obterUsuarioSubmissor($submissionId) {
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $iteradorEventos = $submissionEventLogDao->getBySubmissionId($submissionId);

        while($evento = $iteradorEventos->next()) {
            if($evento->getEventType() == SUBMISSION_LOG_SUBMISSION_SUBMIT){
                $usuarioSubmissor = $userDao->getById($evento->getUserId());
                return $usuarioSubmissor->getFullName();
            }
        }
        
        return __("plugins.reports.scieloSubmissionsReport.warning.noSubmitter");
    }

    private function obterDadosPublicacao($submissao) {
        $publicacao = $submissao->getCurrentPublication();
        $opcoesRelacao = Services::get('publication')->getRelationOptions();
        $idRelacao = $publicacao->getData('relationStatus');
        $estadoPublicacao = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationStatus");
        $doiPublicacao = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");

        if($idRelacao){
            foreach($opcoesRelacao as $opcao){
                if($opcao['value'] == $idRelacao)
                    $estadoPublicacao = $opcao['label'];
            }

            if($publicacao->getData('vorDoi'))
                $doiPublicacao = $publicacao->getData('vorDoi');
        }

        return [$estadoPublicacao, $doiPublicacao];
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
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $iteradorDesignados = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
        $palavraChave = "área";
        $moderadorArea = array();
        $moderadores =  array();

        while($designado = $iteradorDesignados->next()){
            $moderador = $userDao->getById($designado->getUserId());
            $userGroup = $userGroupDao->getById($designado->getUserGroupId());
            $nomeGrupoAtual = $userGroup->getLocalizedName();

            if( strstr($userGroup->getLocalizedName(),$palavraChave) )
                array_push($moderadorArea,$moderador->getFullName());
            else
                array_push($moderadores,$moderador->getFullName());
        }
        $usuariosModeradores = [implode(",", $moderadorArea),implode(",", $moderadores)];

        if((empty($usuariosModeradores[0]) === true) and (empty($usuariosModeradores[1])=== false))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noModerators"), $usuariosModeradores[1]];
        if((empty($usuariosModeradores[0]) === false) and (empty($usuariosModeradores[1]) === true))
            return [$usuariosModeradores[0],__("plugins.reports.scieloSubmissionsReport.warning.noModerators")];
        if((empty($usuariosModeradores[0]) === true) and (empty($usuariosModeradores[1]) === true))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noModerators"), __("plugins.reports.scieloSubmissionsReport.warning.noModerators")];
        
        return $usuariosModeradores; 
    }

    private function obterEditores($submissionId) {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $iteradorEditoresDesignados = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
        $iteradorGerentesDesignados = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_MANAGER,5);
        $gerentesRevista =  array();
        $editoresSecao = array();

        while($designado = $iteradorGerentesDesignados->next()){
            $gerente = $userDao->getById($designado->getUserId());
            array_push($gerentesRevista,$gerente->getFullName());
        }

        while($designado = $iteradorEditoresDesignados->next()){
            $editor = $userDao->getById($designado->getUserId());
            array_push($editoresSecao,$editor->getFullName());
        }

        $usuarios = [implode(",", $gerentesRevista),implode(",", $editoresSecao)];

        if((empty($gerentesRevista) === true) and (empty($editoresSecao)=== false))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noEditors"), $usuarios[1]];
        if((empty($gerentesRevista) === false) and (empty($editoresSecao) === true))
            return [$usuarios[0],__("plugins.reports.scieloSubmissionsReport.warning.noEditors")];
        if((empty($gerentesRevista) === true) and (empty($editoresSecao) === true))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noEditors"), __("plugins.reports.scieloSubmissionsReport.warning.noEditors")];
        
        return $usuarios; 
    }
    
    private function obterNotas($submissionId) {
        $resultNotes = $this->retrieve("SELECT contents FROM notes WHERE assoc_type = 1048585 AND assoc_id = {$submissionId}");
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
        return $notas;
    }

    private function obterAvaliacoes($submissionId) {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $avaliacoesSubmissao = $reviewAssignmentDao->getBySubmissionId($submissionId);
        $avaliacoesCompletas = false;
        $avaliacoes = array();

        foreach($avaliacoesSubmissao as $avaliacao) {
            if($avaliacao->getDateCompleted())
                $avaliacoesCompletas = true;
            $avaliacoes[] = $avaliacao->getLocalizedRecommendation();
        }

        return [$avaliacoesCompletas, implode(", ", $avaliacoes)];
    }

    public function obterSecoes($journalId) {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $newSections = array();
        foreach ($sections as $section) {
            $newSections[$section['title']] = $section['title'];
        }
        return $newSections;
    }

    public function obterOpcoesSecoes($journalId) {
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
