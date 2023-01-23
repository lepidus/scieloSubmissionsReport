<?php
/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloArticlesDAO.inc.php
 *
 * @class ScieloArticlesDAO
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving articles and other data
 */

import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.FinalDecision');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsDAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ScieloArticlesDAO extends ScieloSubmissionsDAO
{
    public function getReviews($submissionId): array
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $submissionReviews = $reviewAssignmentDao->getBySubmissionId($submissionId);
        $completeReviews = false;
        $reviews = array();

        foreach ($submissionReviews as $review) {
            if ($review->getDateCompleted()) {
                $completeReviews = true;
                $reviews[] = $review->getLocalizedRecommendation();
            }
        }
        return $reviews;
    }

    public function getSectionEditor($submissionId): string
    {
        $userDao = DAORegistry::getDAO('UserDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $stageAssignmentsSectionEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsSectionEditorResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));
            if ($currentUserGroupName == 'section editor') {
                return $user->getFullName();
            }
        }
        return '';
    }

    public function getEditors($submissionId): array
    {
        $userDao = DAORegistry::getDAO('UserDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $stageAssignmentsEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_MANAGER, self::SUBMISSION_STAGE_ID);
        $journalEditors = array();

        while ($stageAssignment = $stageAssignmentsEditorResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en_US'));
            if ($currentUserGroupName == 'editor') {
                array_push($journalEditors, $user->getFullName());
            }
        }
        return $journalEditors;
    }

    public function getDecisionMessage($decision)
    {
        import('classes.workflow.EditorDecisionActionsManager');
        switch($decision) {
            case SUBMISSION_EDITOR_DECISION_ACCEPT:
                return __('editor.submission.decision.accept');
            case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
                return __('editor.submission.decision.requestRevisions');
            case SUBMISSION_EDITOR_DECISION_RESUBMIT:
                return __('editor.submission.decision.resubmit');
            case SUBMISSION_EDITOR_DECISION_DECLINE:
                return __('editor.submission.decision.decline');
            case SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION:
                return __('editor.submission.decision.sendToProduction');
            case SUBMISSION_EDITOR_DECISION_NEW_ROUND:
                return __('editor.submission.decision.newRound');
            case SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW:
                return __('editor.submission.decision.sendExternalReview');
            case SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE:
                return __('editor.submission.decision.decline');
            case SUBMISSION_EDITOR_DECISION_REVERT_DECLINE:
                return __('editor.submission.decision.revertDecline');
            case SUBMISSION_EDITOR_RECOMMEND_ACCEPT:
                return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.accept')));
            case SUBMISSION_EDITOR_RECOMMEND_DECLINE:
                return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.decline')));
            case SUBMISSION_EDITOR_RECOMMEND_PENDING_REVISIONS:
                return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.requestRevisions')));
            case SUBMISSION_EDITOR_RECOMMEND_RESUBMIT:
                return __('editor.submission.recommendation.display', array('recommendation' => __('editor.submission.decision.resubmit')));
            default:
                return '';
        }
    }

    public function getLastDecision($submissionId): string
    {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $decisionsSubmission = $editDecisionDao->getEditorDecisions($submissionId);
        $lastDecision = '';
        foreach ($decisionsSubmission as $decisions) {
            $lastDecision = $decisions['decision'];
        }

        return $this->getDecisionMessage($lastDecision);
    }
}
