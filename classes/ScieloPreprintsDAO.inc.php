<?php
/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloPreprintDAO.inc.php
 *
 * @class ScieloPreprintDAO
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving preprints and other data
 */

import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.FinalDecision');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsDAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ScieloPreprintsDAO extends ScieloSubmissionsDAO
{
    public function getSubmissionNotes($submissionId): array
    {
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

    public function getSectionModerator($submissionId): string
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));

            if ($currentUserGroupName == 'section moderator') {
                return $user->getFullName();
            }
        }
        return '';
    }

    public function getModerators($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $moderatorUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));

            if ($currentUserGroupName == 'moderator') {
                array_push($moderatorUsers, $user->getFullName());
            }
        }
        return !empty($moderatorUsers) ? [implode(",", $moderatorUsers)] : array();
    }

    public function getPublicationDOIBySubmission($submission): string
    {
        $publication = $submission->getCurrentPublication();
        $relationId = $publication->getData('relationStatus');
        $publicationDOI = $publication->getData('vorDoi');
        return ($relationId && $publicationDOI) ? $publicationDOI : "";
    }

    public function getFinalDecisionWithDate($submissionId, $locale)
    {
        $submission = DAORegistry::getDAO('SubmissionDAO')->getById($submissionId);
        $publication = $submission->getData('publications')[0];
        if ($publication->getData('datePublished')) {
            return new FinalDecision(__('common.accepted', [], $locale), $publication->getData('datePublished'));
        }

        $possibleFinalDecisions = [SUBMISSION_EDITOR_DECISION_ACCEPT, SUBMISSION_EDITOR_DECISION_DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

        $result = Capsule::table('edit_decisions')
        ->where('submission_id', $submissionId)
        ->whereIn('decision', $possibleFinalDecisions)
        ->orderBy('date_decided', 'asc')
        ->first();

        if (is_null($result)) {
            return null;
        }

        $finalDecisionWithDate = $this->finalDecisionFromRow(get_object_vars($result), $locale);

        return $finalDecisionWithDate;
    }
}
