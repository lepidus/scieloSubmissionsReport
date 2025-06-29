<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloSubmissionsDAO.inc.php
 *
 * @class ScieloSubmissionsDAO
 *
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving submissions and other data
 */

namespace APP\plugins\reports\scieloSubmissionsReport\classes;

use APP\decision\Decision;
use DateTime;
use Illuminate\Support\Facades\DB;
use PKP\db\DAO;
use PKP\log\event\PKPSubmissionEventLogEntry;

class ScieloSubmissionsDAO extends DAO
{
    protected const SUBMISSION_STAGE_ID = 5;

    public function getSubmissions($locale, $contextId, $sectionsIds, $submissionDateInterval, $finalDecisionDateInterval)
    {
        $query = DB::table('submissions')
            ->join('publications', 'submissions.current_publication_id', '=', 'publications.publication_id')
            ->where('submissions.context_id', $contextId)
            ->whereNotNull('submissions.date_submitted')
            ->whereIn('publications.section_id', $sectionsIds)
            ->select('submissions.submission_id');

        if (!is_null($submissionDateInterval)) {
            $query = $query->where('submissions.date_submitted', '>=', $submissionDateInterval->getBeginningDate())
                ->where('submissions.date_submitted', '<=', $submissionDateInterval->getEndDate());
        }

        $result = $query->get();

        $submissions = [];
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

    public function getSubmission($submissionId)
    {
        $result = DB::table('submissions')
            ->where('submission_id', '=', $submissionId)
            ->select('current_publication_id', 'date_submitted', 'date_last_activity', 'status', 'locale', 'context_id')
            ->first();

        return get_object_vars($result);
    }

    public function getPublicationTitle($publicationId, $locale, $submissionLocale)
    {
        $result = DB::table('publication_settings')
            ->where('publication_id', '=', $publicationId)
            ->where('setting_name', '=', 'title')
            ->select('locale', 'setting_value as title')
            ->get();

        $titles = [];
        foreach ($result->toArray() as $row) {
            $title = get_object_vars($row)['title'];
            $locale = get_object_vars($row)['locale'];
            $titles[$locale] = $title;
        }

        if (empty($titles)) {
            return '';
        }

        if (array_key_exists($locale, $titles)) {
            return $titles[$locale];
        }

        if (array_key_exists($submissionLocale, $titles)) {
            return $titles[$submissionLocale];
        }

        return array_pop(array_reverse($titles));
    }

    public function getPublicationSection($publicationId, $locale)
    {
        $result = DB::table('publications')
            ->where('publication_id', '=', $publicationId)
            ->select('section_id')
            ->first();
        $sectionId = get_object_vars($result)['section_id'];

        $result = DB::table('section_settings')
            ->where('section_id', '=', $sectionId)
            ->where('setting_name', '=', 'title')
            ->where('locale', '=', $locale)
            ->select('setting_value as title')
            ->first();

        $sectionTitle = get_object_vars($result)['title'];
        return $sectionTitle;
    }

    public function getPublicationAuthors($publicationId)
    {
        $result = DB::table('authors')
            ->where('publication_id', '=', $publicationId)
            ->select('author_id')
            ->get();

        $authorsIds = [];
        foreach ($result->toArray() as $row) {
            $authorsIds[] = get_object_vars($row)['author_id'];
        }

        return $authorsIds;
    }

    public function getFinalDecisionWithDate($submissionId, $locale, $possibleFinalDecisions = [])
    {
        if (empty($possibleFinalDecisions)) {
            $possibleFinalDecisions = [
                Decision::ACCEPT,
                Decision::DECLINE,
                Decision::INITIAL_DECLINE
            ];
        }

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

    public function getIdOfSubmitterUser($submissionId)
    {
        $result = DB::table('event_log')
            ->where('event_type', PKPSubmissionEventLogEntry::SUBMISSION_LOG_SUBMISSION_SUBMIT)
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
        $dateDecided = new DateTime($row['date_decided']);
        $decision = '';

        if ($row['decision'] == Decision::ACCEPT) {
            $decision = __('common.accepted', [], $locale);
        } elseif ($row['decision'] == Decision::DECLINE || $row['decision'] == SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE) {
            $decision = __('common.declined', [], $locale);
        }

        return new FinalDecision($decision, $dateDecided->format('Y-m-d'));
    }
}
