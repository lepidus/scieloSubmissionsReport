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
use PKP\stageAssignment\StageAssignment;
use PKP\security\Role;

class ScieloArticlesDAO extends ScieloSubmissionsDAO
{
    public function getReviews($submissionId): array
    {
        $submissionReviews = Repo::reviewAssignment()->getCollector()
            ->filterBySubmissionIds([$submissionId])
            ->getMany();
        $reviews = [];

        foreach ($submissionReviews as $review) {
            if ($review->getDateCompleted()) {
                $reviews[] = $review->getLocalizedRecommendation();
            }
        }
        return $reviews;
    }

    public function getSectionEditor($submissionId): string
    {
        $stageAssignmentsSectionEditorResults = StageAssignment::withSubmissionIds([$submissionId])
            ->withRoleIds([Role::ROLE_ID_SUB_EDITOR])
            ->get();

        foreach ($stageAssignmentsSectionEditorResults as $stageAssignment) {
            $user = Repo::user()->get($stageAssignment->userId, true);
            if (is_null($user)) {
                continue;
            }

            $userGroup = Repo::userGroup()->get($stageAssignment->userGroupId);
            $currentUserGroupName = strtolower($userGroup->name['en']);
            if ($currentUserGroupName == 'section editor') {
                return $user->getFullName();
            }
        }
        return '';
    }

    public function getJournalEditors($submissionId): array
    {
        $stageAssignmentsEditorResults = StageAssignment::withSubmissionIds([$submissionId])
            ->withRoleIds([Role::ROLE_ID_MANAGER])
            ->get();
        $journalEditors = [];

        foreach ($stageAssignmentsEditorResults as $stageAssignment) {
            $user = Repo::user()->get($stageAssignment->userId, true);
            if (is_null($user)) {
                continue;
            }

            $userGroup = Repo::userGroup()->get($stageAssignment->userGroupId);
            $currentUserGroupName = strtolower($userGroup->name['en']);
            if ($currentUserGroupName == 'journal editor') {
                array_push($journalEditors, $user->getFullName());
            }
        }
        return $journalEditors;
    }

    public function getDecisionMessage($decision)
    {
        $decisionMessages = [
            Decision::ACCEPT => __('editor.submission.decision.accept'),
            Decision::PENDING_REVISIONS => __('editor.submission.decision.requestRevisions'),
            Decision::RESUBMIT => __('editor.submission.decision.resubmit'),
            Decision::DECLINE => __('editor.submission.decision.decline'),
            Decision::SEND_TO_PRODUCTION => __('editor.submission.decision.sendToProduction'),
            Decision::NEW_EXTERNAL_ROUND => __('editor.submission.decision.newRound'),
            Decision::EXTERNAL_REVIEW => __('editor.submission.decision.sendExternalReview'),
            Decision::INITIAL_DECLINE => __('editor.submission.decision.decline'),
            Decision::REVERT_DECLINE => __('editor.submission.decision.revertDecline'),
            Decision::RECOMMEND_ACCEPT => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.accept')]),
            Decision::RECOMMEND_DECLINE => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.decline')]),
            Decision::RECOMMEND_PENDING_REVISIONS => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.requestRevisions')]),
            Decision::RECOMMEND_RESUBMIT => __('editor.submission.recommendation.display', ['recommendation' => __('editor.submission.decision.resubmit')]),
        ];

        return $decisionMessages[$decision] ?? '';
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
