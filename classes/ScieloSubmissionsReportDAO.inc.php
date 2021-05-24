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

    public function getSubmissions($contextId, $sectionsIds) {
		$result = Capsule::table('submissions')
		->join('publications', 'submissions.submission_id', '=', 'publications.submission_id')
		->where('submissions.context_id', $contextId)
		->whereIn('publications.section_id', $sectionsIds)
		->select('submissions.submission_id')
		->get();
		
		$submissions = array();
		foreach($result->toArray() as $row) {
			$submissions[] = $this->_submissionFromRow(get_object_vars($row));
		}

        return $submissions;
	}

	private function _submissionFromRow($row){
		return $row['submission_id'];
	}

}

?>