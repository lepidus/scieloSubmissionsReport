<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloArticlesDAO.inc.php
 *
 * @class ScieloArticlesDAO
 *
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving articles and other data
 */

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

use APP\decision\Decision;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\security\Role;

class ScieloArticlesDAO extends ScieloSubmissionsDAO
{
    public function getReviews($submissionId): array
    {
        $reviewAssignmentDao = DAORegistry::getDAO('ReviewAssignmentDAO');
        $submissionReviews = $reviewAssignmentDao->getBySubmissionId($submissionId);
        $completeReviews = false;
        $reviews = [];

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
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $stageAssignmentsSectionEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, Role::ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsSectionEditorResults->next()) {
            $user = Repo::user()->get($stageAssignment->getUserId(), false);
            $userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en'));
            if ($currentUserGroupName == 'section editor') {
                return $user->getFullName();
            }
        }
        return '';
    }

    public function getJournalEditors($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $stageAssignmentsEditorResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, Role::ROLE_ID_MANAGER, self::SUBMISSION_STAGE_ID);
        $journalEditors = [];

        while ($stageAssignment = $stageAssignmentsEditorResults->next()) {
            $user = Repo::user()->get($stageAssignment->getUserId(), false);
            $userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId());
            $currentUserGroupName = strtolower($userGroup->getName('en'));
            if ($currentUserGroupName == 'journal editor') {
                array_push($journalEditors, $user->getFullName());
            }
        }
        return $journalEditors;
    }

    public function getDecisionMessage($decision)
    {
        switch ($decision) {
            case Decision::ACCEPT:
                return __('editor.submission.decision.accept');
            case Decision::PENDING_REVISIONS:
                return __('editor.submission.decision.requestRevisions');
            case Decision::RESUBMIT:
                return __('editor.submission.decision.resubmit');
            case Decision::DECLINE:
                return __('editor.submission.decision.decline');
            case Decision::SEND_TO_PRODUCTION:
                return __('editor.submission.decision.sendToProduction');
            case Decision::NEW_EXTERNAL_ROUND:
                return __('editor.submission.decision.newRound');
            case Decision::EXTERNAL_REVIEW:
                return __('editor.submission.decision.sendExternalReview');
            case Decision::INITIAL_DECLINE:
                return __('editor.submission.decision.decline');
            case Decision::REVERT_DECLINE:
                return __('editor.submission.decision.revertDecline');
            case Decision::RECOMMEND_ACCEPT:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.accept')]);
            case Decision::RECOMMEND_DECLINE:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.decline')]);
            case Decision::RECOMMEND_PENDING_REVISIONS:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.requestRevisions')]);
            case Decision::RECOMMEND_RESUBMIT:
                return __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.resubmit')]);
            default:
                return '';
        }
    }

    public function getLastDecision($submissionId): string
    {
        $decisions = Repo::decision()->getCollector()
            ->filterBySubmissionIds([$submissionId])
            ->getMany();

        $decision = null;
        if (!$decisions->isEmpty()) {
            $lastDecision = $decisions->last();
            $decision = $lastDecision->getData('decision');
        }

        return $this->getDecisionMessage($decision);
    }
}
