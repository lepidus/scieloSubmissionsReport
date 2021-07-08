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
import('plugins.reports.articles.ArticleReportPlugin');

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

    public function getLastDecision($submissionId): string
    {
        $editDecisionDao = DAORegistry::getDAO('EditDecisionDAO');
        $decisionsSubmission = $editDecisionDao->getEditorDecisions($submissionId);
        $lastDecision = '';
        foreach ($decisionsSubmission as $decisions) {
            $lastDecision = $decisions['decision'];
        }
        $report = new ArticleReportPlugin();
        return $report->getDecisionMessage($lastDecision);
    }
}
