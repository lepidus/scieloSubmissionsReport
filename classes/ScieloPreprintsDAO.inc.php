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
    public function getAbstractViews($submissionId, $contextId): int
    {
        $statsService = \Services::get('stats');
        $abstractRecords = $statsService->getRecords([
            'assocTypes' => ASSOC_TYPE_SUBMISSION,
            'submissionIds' => [$submissionId],
            'contextIds' => [$contextId]
        ]);
        $abstractViews = array_reduce($abstractRecords, [$statsService, 'sumMetric'], 0);

        return $abstractViews;
    }

    public function getPdfViews($submissionId, $contextId): int
    {
        $statsService = \Services::get('stats');
        $galleyRecords = $statsService->getRecords([
            'assocTypes' => ASSOC_TYPE_SUBMISSION_FILE,
            'fileType' => STATISTICS_FILE_TYPE_PDF,
            'submissionIds' => [$submissionId],
            'contextIds' => [$contextId]
        ]);
        $pdfViews = array_reduce($galleyRecords, [$statsService, 'sumMetric'], 0);

        return $pdfViews;
    }

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

    public function getSubmitterIsScieloJournal($submitterId)
    {
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');

        $submitterUserGroups = $userGroupDao->getByUserId($submitterId);
        while ($userGroup = $submitterUserGroups->next()) {
            $journalGroupAbbrev = "SciELO";
            if ($userGroup->getLocalizedData('abbrev', 'pt_BR') == $journalGroupAbbrev) {
                return true;
            }
        }

        return false;
    }

    public function getSectionModerators($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $sectionModeratorUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en_US'));

            if ($currentUserGroupAbbrev == 'am') {
                array_push($sectionModeratorUsers, $user->getFullName());
            }
        }
        return $sectionModeratorUsers;
    }

    public function getResponsibles($submissionId): array
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO');
        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $moderatorUsers =  array();
        $stageAssignmentsResults = $stageAssignmentDao->getBySubmissionAndRoleId($submissionId, ROLE_ID_SUB_EDITOR, self::SUBMISSION_STAGE_ID);

        while ($stageAssignment = $stageAssignmentsResults->next()) {
            $user = $userDao->getById($stageAssignment->getUserId(), false);
            $userGroup = $userGroupDao->getById($stageAssignment->getUserGroupId());
            $currentUserGroupAbbrev = strtolower($userGroup->getData('abbrev', 'en_US'));

            if ($currentUserGroupAbbrev == 'resp') {
                array_push($moderatorUsers, $user->getFullName());
            }
        }
        return $moderatorUsers;
    }

    public function getPublicationStatus($publicationId): string
    {
        $result = Capsule::table('publication_settings')
        ->where('publication_id', '=', $publicationId)
        ->where('setting_name', '=', 'relationStatus')
        ->select('setting_value as relationStatus')
        ->first();

        if (is_null($result)) {
            return "";
        }

        $relationStatus = get_object_vars($result)['relationStatus'];
        $relationsMap = [
            PUBLICATION_RELATION_NONE => 'publication.relation.none',
            PUBLICATION_RELATION_SUBMITTED => 'publication.relation.submitted',
            PUBLICATION_RELATION_PUBLISHED => 'publication.relation.published'
        ];

        return __($relationsMap[$relationStatus]);
    }

    public function getPublicationDOI($publicationId): string
    {
        $result = Capsule::table('publication_settings')
        ->where('publication_id', '=', $publicationId)
        ->where('setting_name', '=', 'vorDoi')
        ->select('setting_value as vorDoi')
        ->first();

        if (is_null($result)) {
            return "";
        }

        return $publicationDOI = get_object_vars($result)['vorDoi'];
    }

    public function getFinalDecisionWithDate($submissionId, $locale)
    {
        $result = Capsule::table('submissions')
        ->where('submission_id', $submissionId)
        ->select('status')
        ->first();
        $submissionStatus = get_object_vars($result)['status'];

        $result = Capsule::table('publications')
        ->where('submission_id', '=', $submissionId)
        ->select('date_published')
        ->first();
        $publicationDatePublished = get_object_vars($result)['date_published'];

        if (!is_null($publicationDatePublished) && $submissionStatus == STATUS_PUBLISHED) {
            return new FinalDecision(__('common.accepted', [], $locale), $publicationDatePublished);
        }

        $possibleFinalDecisions = [SUBMISSION_EDITOR_DECISION_ACCEPT, SUBMISSION_EDITOR_DECISION_DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

        $result = Capsule::table('edit_decisions')
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
