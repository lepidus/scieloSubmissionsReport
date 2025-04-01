<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloPreprintDAO.inc.php
 *
 * @class ScieloPreprintDAO
 *
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving preprints and other data
 */

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

use APP\core\Services;
use APP\decision\Decision;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;

class ScieloPreprintsDAO extends ScieloSubmissionsDAO
{
    public function getAbstractViews($submissionId, $contextId): int
    {
        $statsService = Services::get('publicationStats');
        $metricsByType = $statsService->getTotalsByType($submissionId, $contextId, null, null);

        return $metricsByType['abstract'];
    }

    public function getPdfViews($submissionId, $contextId): int
    {
        $statsService = Services::get('publicationStats');
        $metricsByType = $statsService->getTotalsByType($submissionId, $contextId, null, null);
        return $metricsByType['pdf'];
    }

    public function getSubmissionNotes($submissionId): array
    {
        $resultNotes = DB::table('notes')
            ->where('assoc_type', 1048585)
            ->where('assoc_id', $submissionId)
            ->select('contents')
            ->get();

        $notes = [];

        foreach ($resultNotes as $noteObject) {
            $note = get_object_vars($noteObject);
            array_push($notes, $note['contents']);
        }
        return $notes;
    }

    public function getSubmitterIsScieloJournal($submitterId)
    {
        $submitterUserGroups = Repo::userGroup()->userUserGroups($submitterId);
        foreach ($submitterUserGroups as $userGroup) {
            $journalGroupAbbrev = 'SciELO';
            if ($userGroup->getLocalizedData('abbrev', 'pt_BR') == $journalGroupAbbrev) {
                return true;
            }
        }

        return false;
    }

    public function getSectionModerators($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        $sectionModeratorUsers = [];
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = Repo::user()->get($stageAssignment->getUserId(), false);
            $userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en'));

            if ($currentUserGroupAbbrev == 'am') {
                array_push($sectionModeratorUsers, $user->getFullName());
            }
        }
        return $sectionModeratorUsers;
    }

    public function getResponsibles($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');

        $moderatorUsers = [];
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = Repo::user()->get($stageAssignment->getUserId(), false);
            $userGroup = Repo::userGroup()->get($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en'));

            if ($currentUserGroupAbbrev == 'resp') {
                array_push($moderatorUsers, $user->getFullName());
            }
        }
        return $moderatorUsers;
    }

    public function getPublicationStatus($publicationId): string
    {
        $result = DB::table('publication_settings')
            ->where('publication_id', '=', $publicationId)
            ->where('setting_name', '=', 'relationStatus')
            ->select('setting_value as relationStatus')
            ->first();

        if (is_null($result)) {
            return '';
        }

        $relationStatus = get_object_vars($result)['relationStatus'];
        $relationsMap = [
            Publication::PUBLICATION_RELATION_NONE => 'publication.relation.none',
            Publication::PUBLICATION_RELATION_PUBLISHED => 'publication.relation.published'
        ];

        return __($relationsMap[$relationStatus]);
    }

    public function getPublicationDOI($publicationId): string
    {
        $result = DB::table('publication_settings')
            ->where('publication_id', '=', $publicationId)
            ->where('setting_name', '=', 'vorDoi')
            ->select('setting_value as vorDoi')
            ->first();

        if (is_null($result)) {
            return '';
        }

        return $publicationDOI = get_object_vars($result)['vorDoi'];
    }

    public function getFinalDecisionWithDate($submissionId, $locale)
    {
        $result = DB::table('submissions')
            ->where('submission_id', $submissionId)
            ->select('status')
            ->first();
        $submissionStatus = get_object_vars($result)['status'];

        $result = DB::table('publications')
            ->where('submission_id', '=', $submissionId)
            ->select('date_published')
            ->first();
        $publicationDatePublished = get_object_vars($result)['date_published'];

        if (!is_null($publicationDatePublished) && $submissionStatus == Submission::STATUS_PUBLISHED) {
            return new FinalDecision(__('common.accepted', [], $locale), $publicationDatePublished);
        }

        $possibleFinalDecisions = [Decision::ACCEPT, Decision::DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

        $result = DB::table('edit_decisions')
            ->where('submission_id', $submissionId)
            ->whereIn('decision', $possibleFinalDecisions)
            ->orderBy('date_decided', 'desc')
            ->first();

        if (is_null($result)) {
            return null;
        }

        $finalDecisionWithDate = $this->finalDecisionFromRow(get_object_vars($result), $locale);

        return $finalDecisionWithDate;
    }
}
