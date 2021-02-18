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
    public function getReportWithSections($application, $journalId, $initialSubmissionDate, $finalSubmissionDate, $initialDecisionDate, $finalDecisionDate, $sections) {
        $querySubmissions = "SELECT submission_id, DATEDIFF(date_last_activity,date_submitted) AS status_change_days FROM submissions WHERE context_id = {$journalId} AND date_submitted IS NOT NULL";
        if($initialSubmissionDate){
            $querySubmissions .= " AND date_submitted >= '{$initialSubmissionDate} 23:59:59' AND date_submitted <= '{$finalSubmissionDate} 23:59:59'";
        }
        
        if($initialDecisionDate){
            $querySubmissions .= " AND date_last_activity >= '{$initialDecisionDate}  23:59:59' AND date_last_activity <= '{$finalDecisionDate} 23:59:59'";
        }
        
        $resultSubmissions = $this->retrieve($querySubmissions);

        $submissionsData = array();
        $allSubmissions = array();

        while($rowSubmission = $resultSubmissions->FetchRow()) {
            $allSubmissions[] = $rowSubmission;
            $submissionData = $this->getSubmissionData($application, $journalId, $rowSubmission['submission_id'], $rowSubmission['status_change_days'], $sections);

            if($submissionData)
                $submissionsData[] = $submissionData;
        }

        // foreach ($allSubmissions as $value) {
        //     error_log($value['submission_id']);
        // }
        $this->averageEvaluationTime($allSubmissions);

        return $submissionsData;
    }

    public function averageEvaluationTime($allSubmissions){
        $totalDays = 0;
        $totalSubmissions = array();
        $reviews = array();
        error_log('vasco ' . count($allSubmissions));
        foreach ($allSubmissions as $submissions) {
            $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissions['submission_id']);
            $evaluatedSubmission = $this->getFinalDecision($submissions['submission_id']);
            $reviews[] = $this->getReviews($submissions['submission_id']);
            if($evaluatedSubmission != ''){
                $totalSubmissions[] = $submissions['submission_id'];
                $totalDays += $this->evaluationTime($submission);
            }

        }

        $averageTime = $totalDays/ count($totalSubmissions);
        error_log('Reviews ' . count($reviews));
        error_log('SUBMISSÕES ' . count($totalSubmissions));
        error_log('DIAS ' . $totalDays);
        error_log('MEDIA ' . $averageTime);
    }

    private function getSubmissionData($application, $journalId, $submissionId, $statusChangeDays, $sections) {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $submissionData = $this->getCommonSubmissionData($application, $submission, $journalId, $statusChangeDays, $sections);
        $evaluationTime = $this->evaluationTime($submission);

        if(!$submissionData) return null;

        if($application == 'ops') {
            list($publicationStatus, $publicationDoi) = $this->getPublicationData($submission);
            $notes = $this->getNotes($submissionId);
            $submissionData = array_merge($submissionData, [$publicationStatus,$publicationDoi,$notes]);
        }
        else if($application == 'ojs') {
            list($completeReviews, $reviews) = $this->getReviews($submissionId);
            $lastDecision = $this->getLastDecision($submissionId);
            $finalDecision = $this->getFinalDecision($submissionId);

            if(!$completeReviews)
                return null;

            $submissionData = array_merge($submissionData, [$reviews], [$lastDecision], [$finalDecision]);
        }
        $submissionData = array_merge($submissionData,[$evaluationTime]);
        return $submissionData;
    }

    private function getCommonSubmissionData($application, $submission, $journalId, $statusChangeDays, $sections) {
        $locale = AppLocale::getLocale();
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

        $submissionStatus = __($submission->getStatusKey());
        $title = $submission->getTitle($locale);
        $submissionDate = $submission->getDateSubmitted();
        $decisionDate = $submission->getDateStatusModified();
        $submissionLocale = $submission->getLocale();
        $section = DAORegistry::getDAO('SectionDAO')->getById( $submission->getSectionId() );
        $sectionName = $section->getTitle($locale);
        $journalName = Application::getContextDAO()->getById($journalId)->getLocalizedName();
        list($areaModerator_JournalEditor, $moderators_SectionEditor) = $this->controllerModeratorEditor($application, $submission);
        $submissionUser = $this->getSubmisssionUser($submission->getId());
        $authors = $this->getAuthors($submission->getAuthors());

        if(!in_array($sectionName, $sections))
            return null;
        
        
        return [$submission->getId(),$title,$submissionUser,$submissionDate,$decisionDate,$statusChangeDays,$submissionStatus,$areaModerator_JournalEditor,$moderators_SectionEditor,$sectionName,$submissionLocale,$authors];
    }

    public function evaluationTime($submission){
        $submissionDate = $submission->getDateSubmitted();
        $decisionDate = $submission->getDateStatusModified();
        $dateFinal = new DateTime(preg_split('/ /',$decisionDate,-1,PREG_SPLIT_NO_EMPTY)[0]);
        $dateBegin = new DateTime(preg_split('/ /',$submissionDate,-1,PREG_SPLIT_NO_EMPTY)[0]);
        $evaluationTime = $dateFinal->diff($dateBegin);

        return $evaluationTime->format('%a');
    }

    public function getFinalDecision($submissionId){
        $editDecision = DAORegistry::getDAO('EditDecisionDAO');
        $decisionsSubmission = $editDecision->getEditorDecisions($submissionId); 
        $lastDecision = "";
        $recusedDecisionId = "4";
        $acceptedDecisionId = "1";
        $submissionAcceptedId = "7";

        foreach($decisionsSubmission as $decisions){
            $lastDecision = $decisions['decision'];
        }

        switch ($lastDecision) {
            case $acceptedDecisionId:
                return __('common.accepted');
            case $submissionAcceptedId:
                return __('common.accepted');                
            case $recusedDecisionId:
                return __('common.declined');
            default:
                return '';
        }
    }

    function newDataObject() {
		return new ArticleReportPlugin();
	}

    public function getLastDecision($submissionId){
        $report = $this->newDataObject();
        $editDecision = DAORegistry::getDAO('EditDecisionDAO');
        $decisionsSubmission = $editDecision->getEditorDecisions($submissionId); 
        $lastDecision = "";

        foreach($decisionsSubmission as $decisions){
            $lastDecision = $decisions['decision'];
        }

        $decision = $report->getDecisionMessage($lastDecision); 

        return $decision;

    }

    private function controllerModeratorEditor($application, $submission){
        if($application == 'ops')
            return $this->getModerators($submission->getId());
        else if($application == 'ojs')
            return $this->getEditors($submission->getId());
    }

    private function getSubmisssionUser($submissionId) {
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $eventsIterator = $submissionEventLogDao->getBySubmissionId($submissionId);

        while($event = $eventsIterator->next()) {
            if($event->getEventType() == SUBMISSION_LOG_SUBMISSION_SUBMIT){
                $submissionUser = $userDao->getById($event->getUserId());
                return $submissionUser->getFullName();
            }
        }
        
        return __("plugins.reports.scieloSubmissionsReport.warning.noSubmitter");
    }

    private function getPublicationData($submission) {
        $publication = $submission->getCurrentPublication();
        $relationOptions = Services::get('publication')->getRelationOptions();
        $relationId = $publication->getData('relationStatus');
        $publicationStatus = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationStatus");
        $publicationDoi = __("plugins.reports.scieloSubmissionsReport.warning.noPublicationDOI");

        if($relationId){
            foreach($relationOptions as $option){
                if($option['value'] == $relationId)
                    $publicationStatus = $option['label'];
            }

            if($publication->getData('vorDoi'))
                $publicationDoi = $publication->getData('vorDoi');
        }

        return [$publicationStatus, $publicationDoi];
    }

    private function getAuthors($authors) {
        $authorsData = array();
        foreach($authors as $author) {
            $authorName = $author->getLocalizedGivenName() . " " . $author->getLocalizedFamilyName();
            $authorCountry = $author->getCountryLocalized();
            $authorAffiliation = $author->getLocalizedAffiliation();

            $authorsData[] = implode(", ", [$authorName, $authorCountry, $authorAffiliation]);
        }

        return implode("; ", $authorsData);
    }

    private function getModerators($submissionId) {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $designatedIterator = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
        $keywords = array("moderador de área","moderador");
        $areaModerator = array();
        $moderators =  array();

        while($designated = $designatedIterator->next()){
            $moderator = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getName('pt_BR'));

            if( strstr($currentGroupName,$keywords[0]) )
                array_push($areaModerator,$moderator->getFullName());
            if( $currentGroupName == $keywords[1] )
                array_push($moderators,$moderator->getFullName());
        }
        $moderatorUsers = array();
        $moderatorUsers[] = (!empty($areaModerator)) ? (implode(",", $areaModerator)) : (__("plugins.reports.scieloSubmissionsReport.warning.noModerators"));
        $moderatorUsers[] = (!empty($moderators)) ? (implode(",", $moderators)) : (__("plugins.reports.scieloSubmissionsReport.warning.noModerators"));
        
        return $moderatorUsers; 
    }


    private function getEditors($submissionId) {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $iteratorDesignatedEditors = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
        $iteratorDesignatedManagers = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_MANAGER,5);
        $keywords = array("editor da revista","editor de seção");
        $journalEditors =  array();
        $sectionEditors = array();

        while($designated = $iteratorDesignatedManagers->next()){
            $manager = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getName('pt_BR'));
            
            if(strstr($currentGroupName,$keywords[0]))
                array_push($journalEditors,$manager->getFullName());
        }

        while($designated = $iteratorDesignatedEditors->next()){
            $editor = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getName('pt_BR'));

            if(strstr($currentGroupName,$keywords[1]))
                array_push($sectionEditors,$editor->getFullName());

        }

        $editorUsers = array();
        $editorUsers[] = (!empty($journalEditors)) ? (implode(",", $journalEditors)) : (__("plugins.reports.scieloSubmissionsReport.warning.noEditors"));
        $editorUsers[] = (!empty($sectionEditors)) ? (implode(",", $sectionEditors)) : (__("plugins.reports.scieloSubmissionsReport.warning.noEditors"));
        
        return $editorUsers; 
    }
    
    private function getNotes($submissionId) {
        $resultNotes = $this->retrieve("SELECT contents FROM notes WHERE assoc_type = 1048585 AND assoc_id = {$submissionId}");
        $notes = "";
        if($resultNotes->NumRows() == 0) {
            $notes = 'No notes';
        }
        else{
            while($note = $resultNotes->FetchRow()) {
                $note = $note[0];
                $notes .= "Note: " . trim(preg_replace('/\s+/', ' ', $note));
            }
        }
        return $notes;
    }

    private function getReviews($submissionId) {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $submissionReviews = $reviewAssignmentDao->getBySubmissionId($submissionId);
        $completeReviews = false;
        $reviews = array();

        foreach($submissionReviews as $review) {
            if($review->getDateCompleted())
                $completeReviews = true;
            $reviews[] = $review->getLocalizedRecommendation();
        }

        return [$completeReviews, implode(", ", $reviews)];
    }

    public function getSections($journalId) {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $newSections = array();
        foreach ($sections as $section) {
            $newSections[$section['title']] = $section['title'];
        }
        return $newSections;
    }

    public function getSectionsOptions($journalId) {
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
