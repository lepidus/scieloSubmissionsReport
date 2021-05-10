<?php

/**
 * @file plugins/reports/scieloSubmissions/ScieloSubmissionsReportDAO.inc.php
 *
 * Copyright (c) 2019-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
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
        $querySubmissions = "SELECT submission_id, DATEDIFF(date_last_activity,date_submitted) AS status_change_days FROM submissions WHERE context_id = ? AND date_submitted IS NOT NULL";
        $paramsForQuery = [$journalId];
        if($initialSubmissionDate){
            $querySubmissions .= " AND date_submitted >= ? AND date_submitted <= ?";
            $paramsForQuery = array_merge($paramsForQuery, [$initialSubmissionDate.' 00:00:00', $finalSubmissionDate.' 23:59:59']);
        }
        
        $resultSubmissions = $this->retrieve($querySubmissions, $paramsForQuery);
        $submissionsData = array();
        $allSubmissions = array();

        
        while($rowSubmission = $resultSubmissions->FetchRow()) {
            $submissionData = $this->getSubmissionData($application, $journalId, $rowSubmission['submission_id'], $rowSubmission['status_change_days'], $sections, $initialDecisionDate, $finalDecisionDate);

            if($submissionData){
                $submissionsData[] = $submissionData;
                $allSubmissions[] = $rowSubmission;
            }
        }

        $submissionsData[] = ["", ""];
        $submissionsData[] = [
            __("plugins.reports.scieloSubmissionsReport.header.AverageReviewingTime"),
            __("section.sections"),
        ];
        $submissionsData[] = [
            $this->averageReviewingTime($allSubmissions, $application),
            implode(", ", $sections),
        ];

        return $submissionsData;
    }

    public function averageReviewingTime($allSubmissions, $application){
        $totalDays = 0;
        
        if ($application == 'ojs'){
            $totalSubmissions = 0;
        }else{
            $totalSubmissions = count($allSubmissions);
        }

        foreach ($allSubmissions as $submissionRow) {
            $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionRow['submission_id']);

            if ($application == 'ojs'){
                $finalDecision = $this->getFinalDecision($submission, $application);
                list($completeReviews, $reviews) = $this->getReviews($submissionRow['submission_id']);
                if ($finalDecision && $completeReviews) {
                    $totalDays += $this->getReviewingTime($submission, $application);
                    $totalSubmissions += 1;
                }
            } else{
                $totalDays += $this->getReviewingTime($submission, $application);
            }
        }
        if($totalDays == 0 || $totalSubmissions == 0)
            return 0;
        
        return round($totalDays / $totalSubmissions);
    }

    private function getSubmissionData($application, $journalId, $submissionId, $statusChangeDays, $sections, $initialDecisionDate, $finalDecisionDate) {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $submissionData = $this->getCommonSubmissionData($application, $submission, $journalId, $statusChangeDays, $sections);

        if(!$submissionData) return null;

        if($application == 'ops') {
            list($publicationStatus, $publicationDoi) = $this->getPublicationData($submission);
            $notes = $this->getNotes($submissionId);
            $finalDecision = $this->getFinalDecision($submission, $application);
            $finalDecisionDate = $this->getFinalDecisionDate($submission, $application);
            $reviewingTime = $this->getReviewingTime($submission, $application);
            $submissionInterval = ($finalDecision) ? ($this->getReviewingTime($submission, $application)) : ("");

            $submissionData = array_merge($submissionData, [$publicationStatus,$publicationDoi,$notes,$finalDecision,$finalDecisionDate,$reviewingTime,$submissionInterval]);
        }
        else if($application == 'ojs') {
            list($completeReviews, $reviews) = $this->getReviews($submissionId);
            
            $lastDecision = $this->getLastDecision($submissionId);
            $finalDecision = $this->getFinalDecision($submission, $application);
            $finalDecisionDate = $this->getFinalDecisionDate($submission, $application);
            $reviewingTime = $this->getReviewingTime($submission, $application);
            $submissionInterval = ($finalDecision) ? ($this->getReviewingTime($submission, $application)) : ("");

            $submissionData = array_merge($submissionData, [$reviews,$lastDecision,$finalDecision,$finalDecisionDate,$reviewingTime,$submissionInterval]);

        }

        if (!empty($initialDecisionDate) && !empty($finalDecisionDate)) {
            $initialDate = strtotime($initialDecisionDate);
            $finalDate = strtotime($finalDecisionDate);

            if ($finalDecisionDate == '') return null;
            if (($initialDate > strtotime($finalDecisionDate)) ||  (strtotime($finalDecisionDate) > $finalDate)) return null;
        }
        
        return $submissionData;
    }

    private function getCommonSubmissionData($application, $submission, $journalId, $statusChangeDays, $sections) {
        $locale = AppLocale::getLocale();
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

        $submissionStatus = __($submission->getStatusKey());
        $title = $submission->getTitle($locale);
        $submissionDate = $submission->getDateSubmitted();
        $submissionLocale = $submission->getLocale();
        $section = DAORegistry::getDAO('SectionDAO')->getById( $submission->getSectionId() );
        $sectionName = $section->getTitle($locale);
        $journalName = Application::getContextDAO()->getById($journalId)->getLocalizedName();
        list($areaModerator_JournalEditor, $moderators_SectionEditor) = $this->controllerModeratorEditor($application, $submission);
        $submissionUser = $this->getSubmisssionUser($submission->getId());
        $authors = $this->getAuthors($submission->getAuthors());

        if(!in_array($sectionName, $sections))
            return null;
                
        return [$submission->getId(),$title,$submissionUser,$submissionDate,$statusChangeDays,$submissionStatus,$areaModerator_JournalEditor,$moderators_SectionEditor,$sectionName,$submissionLocale,$authors];
    }

    public function getReviewingTime($submission, $application){
        $submissionDate = $submission->getDateSubmitted();
        $finalDecisionDate = $this->getFinalDecisionDate($submission, $application);
        
        $dateFinal = new DateTime(trim($finalDecisionDate));
        $dateBegin = new DateTime(trim($submissionDate));
        
        $reviewingTime = $dateFinal->diff($dateBegin);
        return $reviewingTime->format('%a');
    }

    public function getFinalDecision($submission, $application){
        if($application == 'ops') {
            $publication = $submission->getData('publications')[0];
            if ($publication->getData('datePublished')) {
                return __('common.accepted');
            }
            else {
                $editDecision = DAORegistry::getDAO('EditDecisionDAO');
                $decisionsSubmission = $editDecision->getEditorDecisions($submission->getId());
                foreach($decisionsSubmission as $decision){
                    if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE || $decision['decision'] == SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE){
                        return __('common.declined');
                    }
                }
            }
        }
        else if($application == 'ojs') {
            $editDecision = DAORegistry::getDAO('EditDecisionDAO');
            $decisionsSubmission = $editDecision->getEditorDecisions($submission->getId()); 

            foreach($decisionsSubmission as $decision){
                if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT)
                    return __('common.accepted');                
                else if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE)
                    return __('common.declined');
            }
        }
        
        return "";        
    }

    public function getFinalDecisionDate($submission, $application) {
        if($application == 'ops') {
            $publication = $submission->getData('publications')[0];
            if ($publication->getData('datePublished')) {
                return $publication->getData('datePublished');
            }
            else {
                $editDecision = DAORegistry::getDAO('EditDecisionDAO');
                $decisionsSubmission = $editDecision->getEditorDecisions($submission->getId());
                foreach($decisionsSubmission as $decision){
                    if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE || $decision['decision'] == SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE){
                        $finalDecisionDate = new DateTime($decision['dateDecided']);
                        return $finalDecisionDate->format('Y-m-d');
                    }
                }
            }
        }
        else if($application == 'ojs') {
            $editDecision = DAORegistry::getDAO('EditDecisionDAO');
            $decisionsSubmission = $editDecision->getEditorDecisions($submission->getId()); 
            foreach($decisionsSubmission as $decision){
                if ($decision['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT || $decision['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE){
                    return $decision['dateDecided'];
                }
            } 
        }
        
        return "";
    }
    
    public function getLastDecision($submissionId){
        $report = new ArticleReportPlugin();
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
        $resultNotes = $this->retrieve("SELECT contents FROM notes WHERE assoc_type = 1048585 AND assoc_id = ?", [$submissionId]);
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
