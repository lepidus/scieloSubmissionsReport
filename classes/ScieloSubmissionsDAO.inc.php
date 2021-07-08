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
import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.FinalDecision');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ScieloSubmissionsDAO extends DAO
{
    protected const SUBMISSION_STAGE_ID = 5;

    public function getSubmissions($locale, $contextId, $sectionsIds, $submissionDateInterval, $finalDecisionDateInterval)
    {
        $query = Capsule::table('submissions')
        ->join('publications', 'submissions.submission_id', '=', 'publications.submission_id')
        ->where('submissions.context_id', $contextId)
        ->whereNotNull('submissions.date_submitted')
        ->whereIn('publications.section_id', $sectionsIds)
        ->select('submissions.submission_id');

        if (!is_null($submissionDateInterval)) {
            $query = $query->where('submissions.date_submitted', '>=', $submissionDateInterval->getBeginningDate())
            ->where('submissions.date_submitted', '<=', $submissionDateInterval->getEndDate());
        }

        $result = $query->get();

        $submissions = array();
        foreach ($result->toArray() as $row) {
            $submissionId = $this->submissionFromRow(get_object_vars($row));

            if (!is_null($finalDecisionDateInterval)) {
                $finalDecisionWithDate = $this->getFinalDecisionWithDate($submissionId, $locale);

                if (!is_null($finalDecisionWithDate)) {
                    $finalDecisionDate = $finalDecisionWithDate->getDateDecided();
                    if ($finalDecisionDateInterval->isInsideInterval($finalDecisionDate)) {
                        $submissions[] = $submissionId;
                    }
                }
            } else {
                $submissions[] = $submissionId;
            }
        }

        return $submissions;
    }

    public function getFinalDecisionWithDate($submissionId, $locale)
    {
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

    public function getIdOfSubmitterUser($submissionId)
    {
        $result = Capsule::table('event_log')
        ->where('event_type', SUBMISSION_LOG_SUBMISSION_SUBMIT)
        ->where('assoc_type', ASSOC_TYPE_SUBMISSION)
        ->where('assoc_id', $submissionId)
        ->select('user_id')
        ->get();
        $result = $result->toArray();

        if (empty($result)) {
            return null;
        }

        $userId = get_object_vars($result[0])['user_id'];
        return $userId;
    }

    protected function submissionFromRow($row)
    {
        return $row['submission_id'];
    }

    protected function finalDecisionFromRow($row, $locale)
    {
        $dateDecided = $row['date_decided'];
        $decision = "";

        if ($row['decision'] == SUBMISSION_EDITOR_DECISION_ACCEPT) {
            $decision = __('common.accepted', [], $locale);
        } elseif ($row['decision'] == SUBMISSION_EDITOR_DECISION_DECLINE || $row['decision'] == SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE) {
            $decision = __('common.declined', [], $locale);
        }

        return new FinalDecision($decision, $dateDecided);
    }
}
