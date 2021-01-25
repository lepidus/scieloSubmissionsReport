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
        while($rowSubmission = $resultSubmissions->FetchRow()) {
            $submissionData = $this->getSubmissionData($application, $journalId, $rowSubmission['submission_id'], $rowSubmission['status_change_days'], $sections);

            if($submissionData)
                $submissionsData[] = $submissionData;
        }

        return $submissionsData;
    }

    private function getSubmissionData($application, $journalId, $submissionId, $statusChangeDays, $sections) {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $submissionData = $this->getCommonSubmissionData($submission, $journalId, $statusChangeDays, $sections);
        
        if(!$submissionData) return null;

        if($application == 'ops') {
            list($publicationStatus, $publicationDoi) = $this->getPublicationData($submission);
            $notes = $this->getNotes($submissionId);
            $submissionData = array_merge($submissionData, [$publicationStatus,$publicationDoi,$notes]);
        }
        else if($application == 'ojs') {
            list($completeReviews, $reviews) = $this->getReviews($submissionId);

            if(!$completeReviews)
                return null;

            $submissionData = array_merge($submissionData, [$reviews]);
        }

        return $submissionData;
    }

    private function getCommonSubmissionData($submission, $journalId, $statusChangeDays, $sections) {
        $locale = AppLocale::getLocale();
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);

        $submissionStatus = __($submission->getStatusKey());
        $title = $submission->getTitle($locale);
        $submissionDate = $submission->getDateSubmitted();
        $decisionDate = $submission->getDateStatusModified();
        $locale = $submission->getLocale();
        $section = DAORegistry::getDAO('SectionDAO')->getById( $submission->getSectionId() );
        $sectionName = $section->getTitle($locale);
        $journalName = Application::getContextDAO()->getById($journalId)->getLocalizedName();
        list($areaModerator_JournalEditor, $moderators_SectionEditor) = $this->controllerModeratorEditor($submission);
        $submissionUser = $this->getSubmisssionUser($submission->getId());
        $authors = $this->getAuthors($submission->getAuthors());

        if(!in_array($sectionName, $sections))
            return null;
        
        return [$submission->getId(),$title,$submissionUser,$submissionDate,$decisionDate,$statusChangeDays,$submissionStatus,$areaModerator_JournalEditor,$moderators_SectionEditor,$sectionName,$locale,$authors];
    }

    private function controllerModeratorEditor($submission){
        $applicationName = Application::getName();
        $defaultApplicationName = strtolower($applicationName);
        
        if(strstr($defaultApplicationName,'ops'))
            return $this->getModerators($submission->getId());
        if(strstr($defaultApplicationName,'ojs'))
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
        $keywords = array("area moderator","moderator");
        $areaModerator = array();
        $moderators =  array();

        while($designated = $designatedIterator->next()){
            $moderator = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getLocalizedName());

            if( strstr($currentGroupName,$keywords[0]) )
                array_push($areaModerator,$moderator->getFullName());
            if( $currentGroupName == $keywords[1] )
                array_push($moderators,$moderator->getFullName());
        }
        $moderatorUsers = [implode(",", $areaModerator),implode(",", $moderators)];

        if((empty($moderatorUsers[0]) === true) and (empty($moderatorUsers[1])=== false))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noModerators"), $moderatorUsers[1]];
        if((empty($moderatorUsers[0]) === false) and (empty($moderatorUsers[1]) === true))
            return [$moderatorUsers[0],__("plugins.reports.scieloSubmissionsReport.warning.noModerators")];
        if((empty($moderatorUsers[0]) === true) and (empty($moderatorUsers[1]) === true))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noModerators"), __("plugins.reports.scieloSubmissionsReport.warning.noModerators")];
        
        return $moderatorUsers; 
    }


    private function getEditors($submissionId) {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $iteratorDesignatedEditors = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
        $iteratorDesignatedManagers = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_MANAGER,5);
        $keywords = array("journal editor","section editor");
        $journalEditors =  array();
        $sectionEditors = array();

        while($designated = $iteratorDesignatedManagers->next()){
            $manager = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getLocalizedName());
            
            if(strstr($currentGroupName,$keywords[0]))
                array_push($journalEditors,$manager->getFullName());
        }

        while($designated = $iteratorDesignatedEditors->next()){
            $editor = $userDao->getById($designated->getUserId());
            $userGroup = $userGroupDao->getById($designated->getUserGroupId());
            $currentGroupName = strtolower($userGroup->getLocalizedName());

            if(strstr($currentGroupName,$keywords[1]))
                array_push($sectionEditors,$editor->getFullName());

        }

        $users = [implode(",", $journalEditors),implode(",", $sectionEditors)];

        if((empty($journalEditors) === true) and (empty($sectionEditors)=== false))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noEditors"), $users[1]];
        if((empty($journalEditors) === false) and (empty($sectionEditors) === true))
            return [$users[0],__("plugins.reports.scieloSubmissionsReport.warning.noEditors")];
        if((empty($journalEditors) === true) and (empty($sectionEditors) === true))
            return [__("plugins.reports.scieloSubmissionsReport.warning.noEditors"), __("plugins.reports.scieloSubmissionsReport.warning.noEditors")];
        
        return $users; 
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
