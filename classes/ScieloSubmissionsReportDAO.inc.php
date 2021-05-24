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

    public function getSubmissions($contextId) {
		$result = Capsule::table('submissions')
		->where('context_id', $contextId)
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