<?php

/**
 * @file plugins/reports/scieloSubmissionsReport/classes/ScieloSubmissionsReportDAO.inc.php
 *
 * @class ScieloSubmissionsReportDAO
 * @ingroup plugins_reports_scieloSubmissionsReport
 *
 * Operations for retrieving submissions and other data
 */

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ScieloSubmissionsReportDAO extends DAO {  

    public function getSubmissions($contextId, $sectionsIds, $startSubmissionDateInterval, $endSubmissionDateInterval, $startFinalDecisionDateInterval, $endFinalDecisionDateInterval) {
		$query = Capsule::table('submissions')
		->join('publications', 'submissions.submission_id', '=', 'publications.submission_id')
		->where('submissions.context_id', $contextId)
		->whereNotNull('submissions.date_submitted')
		->whereIn('publications.section_id', $sectionsIds)
		->select('submissions.submission_id');
		
		if(!empty($startSubmissionDateInterval) && !empty($endSubmissionDateInterval)){
			$query = $query->where('submissions.date_submitted', '>=', $startSubmissionDateInterval)
			->where('submissions.date_submitted', '<=', $endSubmissionDateInterval);
		}

		$result = $query->get();

		$submissions = array();
		foreach($result->toArray() as $row) {
			$submissionId = $this->_submissionFromRow(get_object_vars($row));

			if(!is_null($startFinalDecisionDateInterval) && !is_null($endFinalDecisionDateInterval)){
				$finalDecisionWithDate = $this->getFinalDecisionWithDate($submissionId);

				if(!is_null($finalDecisionWithDate)){
					$finalDecisionDate = new DateTime($finalDecisionWithDate['date_decided']);
					if($finalDecisionDate >= $startFinalDecisionDateInterval && $finalDecisionDate <= $endFinalDecisionDateInterval){
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

	public function getFinalDecisionWithDate($submissionId) {
		$possibleFinalDecisions = [SUBMISSION_EDITOR_DECISION_ACCEPT, SUBMISSION_EDITOR_DECISION_DECLINE, SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE];

		$result = Capsule::table('edit_decisions')
		->where('submission_id', $submissionId)
		->whereIn('decision', $possibleFinalDecisions)
		->orderBy('date_decided', 'asc')
		->first();

		if(is_null($result)) return null;

		$finalDecisionWithDate = $this->_finalDecisionFromRow(get_object_vars($result));

		return $finalDecisionWithDate;
	}

	private function _submissionFromRow($row){
		return $row['submission_id'];
	}

	private function _finalDecisionFromRow($row){
		return ['decision' => "Aceitar", 'date_decided' => $row['date_decided']];
	}

}

?>