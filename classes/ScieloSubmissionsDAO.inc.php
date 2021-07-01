<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloSubmissionsDAO.inc.php
 *
 * @class ScieloSubmissionsDAO
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving submissions and other data
 */

import('lib.pkp.classes.db.DAO');
import('classes.log.SubmissionEventLogEntry');
import ('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import ('plugins.reports.scieloSubmissionsReport.classes.FinalDecision');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ScieloSubmissionsDAO extends DAO {  

    public function getSubmissions($application, $locale, $contextId, $sectionsIds, $submissionDateInterval, $finalDecisionDateInterval) {
		$query = Capsule::table('submissions')
		->join('publications', 'submissions.submission_id', '=', 'publications.submission_id')
		->where('submissions.context_id', $contextId)
		->whereNotNull('submissions.date_submitted')
		->whereIn('publications.section_id', $sectionsIds)
		->select('submissions.submission_id');
		
		if(!is_null($submissionDateInterval)){
			$query = $query->where('submissions.date_submitted', '>=', $submissionDateInterval->getBeginningDate())
			->where('submissions.date_submitted', '<=', $submissionDateInterval->getEndDate());
		}

		$result = $query->get();

		$submissions = array();
		foreach($result->toArray() as $row) {
			$submissionId = $this->_submissionFromRow(get_object_vars($row));

			if(!is_null($finalDecisionDateInterval)){
				$finalDecisionWithDate = $this->getFinalDecisionWithDate($application, $submissionId, $locale);

				if(!is_null($finalDecisionWithDate)){
					$finalDecisionDate = $finalDecisionWithDate->getDateDecided();
					if($finalDecisionDateInterval->isInsideInterval($finalDecisionDate)){
						$submissions[] = $submissionId;
					}
				}
			}
			else {
				$submissions[] = $submissionId;
			}
		}

        return $submissions;
	}

	public function getFinalDecisionWithDate($application, $submissionId, $locale) {
		if($application == 'ops') {
			$submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
            $publication = $submission->getData('publications')[0];
            if ($publication->getData('datePublished')) {
                return new FinalDecision(__('common.accepted', [], $locale), $publication->getData('datePublished'));
            }
		}

		$possibleFinalDecisions = [SUBMISSION_EDITOR_DECISION_ACCEPT, SUBMISSION_EDITOR_DECISION_DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

		$result = Capsule::table('edit_decisions')
		->where('submission_id', $submissionId)
		->whereIn('decision', $possibleFinalDecisions)
		->orderBy('date_decided', 'asc')
		->first();

		if(is_null($result)) return null;

		$finalDecisionWithDate = $this->_finalDecisionFromRow(get_object_vars($result), $locale);

		return $finalDecisionWithDate;
	}

	public function getIdSubmitterUser($submissionId) {
		$result = Capsule::table('event_log')
        ->where('event_type', SUBMISSION_LOG_SUBMISSION_SUBMIT)
        ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
        ->where('assoc_id', $submissionId)
        ->select('user_id')
		->get();
		$result = $result->toArray();

		if(empty($result)) return null;

		$userId = get_object_vars($result[0])['user_id'];
		return $userId;
	}

	private function _submissionFromRow($row) {
		return $row['submission_id'];
	}

	private function _finalDecisionFromRow($row, $locale) {
		$dateDecided = $row['date_decided'];
		$decision = "";
		
		if($row['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT)
			$decision = __('common.accepted', [], $locale);
		else if($row['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE || $row['decision'] == SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE)
			$decision = __('common.declined', [], $locale);
		
		return new FinalDecision($decision, $dateDecided);
	}

	public function getPublicationDOIBySubmission($submission) : string {
		$publication = $submission->getCurrentPublication();
        $relationId = $publication->getData('relationStatus');
		$publicationDOI = $publication->getData('vorDoi');
        return ($relationId && $publicationDOI) ? $publicationDOI : "";
	}

	public function getAllModeratorsBySubmissionId($submissionId) : array {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

		$submissionStageId = 5;
		$sectionModerator = "";
        $moderatorUsers =  array(); 
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, $submissionStageId);

		while($stageAssignment = $stageAssignmentsResults->next()) {
			$user = $userDao->getById($stageAssignment->getUserId(), false);
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));
			
			if ($currentUserGroupName == 'section moderator')
				$sectionModerator = $user->getFullName();
			if ($currentUserGroupName == 'moderator')
				array_push($moderatorUsers, $user->getFullName());
		}
		return [$sectionModerator, !empty($moderatorUsers) ? implode(",", $moderatorUsers) : array()];
	}

	public function getEditors($submissionId) : array {
		$userDao = DAORegistry::getDAO('UserDAO');
		$userGroupDao = DAORegistry::getDAO('UserGroupDAO');
		$stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
		$stageAssignmentsEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_MANAGER, 5);
		$stageAssignmentsSectionEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, 5);
		
		$journalEditors = array();
		$sectionEditor = '';

		while ($stageAssignment = $stageAssignmentsEditorResults->next()) {
			$user = $userDao->getById($stageAssignment->getUserId(), false);
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));			
			if ($currentUserGroupName == 'editor')
				array_push($journalEditors, $user->getFullName());
		}
		while ($stageAssignment = $stageAssignmentsSectionEditorResults->next()) {
			$user = $userDao->getById($stageAssignment->getUserId(), false);
			$userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));
			if ($currentUserGroupName == 'section editor')
				$sectionEditor = $user->getFullName();
		}
		return [$journalEditors, $sectionEditor];
	}

	public function getSubmissionNotes($submissionId) : array {
        $resultNotes = Capsule::table('notes')
		->where('assoc_type', 1048585)
		->where('assoc_id', $submissionId)
		->select('contents')
		->get();
		
		$notes = array();

		foreach ($resultNotes as $noteObject) {
			$note = get_object_vars($noteObject);
			array_push($notes, $note['contents']);
		}
        return $notes;
    }
}

?>