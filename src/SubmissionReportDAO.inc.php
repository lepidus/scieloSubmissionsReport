<?php

/**
 * @file plugins/reports/submissions/SubmissionReportDAO.inc.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionReportDAO
 * @ingroup plugins_reports_submission
 *
 * @brief Submission report DAO
 */

import('lib.pkp.classes.db.DBRowIterator');
class SubmissionReportDAO extends DAO
{

    /**
     * Get the submission report data.
     * @param $journalId int
     * @return array
     */
    public function getReportWithSections($journalId, $dataStart, $dataEnd, $sections)
    {
        $locale = AppLocale::getLocale();
        
        if (is_null($sections)) {
            $queryResult = " SELECT submission_id AS Id, ss.setting_value AS 'Seção', ";
            $queryResult.= " CASE STATUS WHEN '1' THEN 'Avaliação' WHEN '4' THEN 'Rejeitado' WHEN '3' THEN 'Publicado' END AS Status, ";
            $queryResult.= " CASE stage_id WHEN '1' THEN 'Submissão' WHEN '3' THEN 'Avaliação' WHEN '4' THEN 'Edição de texto' WHEN '5' THEN 'Editoração' END AS 'Estágio', ";
            $queryResult.= " date_submitted AS 'Data de submissão', date_last_activity 'Data da modificação de Status' , DATEDIFF(date_last_activity,date_submitted) AS 'Dias até a última mudança de status' ";
            $queryResult.= " FROM submissions s JOIN section_settings AS ss WHERE date_submitted BETWEEN ";
            $queryResult.= " '{$dataStart}' AND '{$dataEnd}' AND ss.setting_name = 'title' AND ss.locale = '{$locale}' AND s.section_id = ss.section_id AND s.context_id = '{$journalId}' ORDER BY date_submitted ASC ";

            $result = $this->retrieve($queryResult);
            echo $result;
        } else {

            $newSections = "'" . implode("','", $sections) . "'";

            $queryResult = " SELECT submission_id AS Id, ss.setting_value AS 'Seção', ";
            $queryResult.= " CASE STATUS WHEN '1' THEN 'Avaliação' WHEN '4' THEN 'Rejeitado' WHEN '3' THEN 'Publicado' END AS Status, ";
            $queryResult.= " CASE stage_id WHEN '1' THEN 'Submissão' WHEN '3' THEN 'Avaliação' WHEN '4' THEN 'Edição de texto' WHEN '5' THEN 'Editoração' END AS 'Estágio', ";
            $queryResult.= " date_submitted AS 'Data de submissão', date_last_activity 'Data da modificação de Status', DATEDIFF(date_last_activity,date_submitted) AS 'Dias até a última mudança de status' ";
            $queryResult.= " FROM submissions s JOIN section_settings AS ss WHERE date_submitted BETWEEN ";
            $queryResult.= " '{$dataStart}' AND '{$dataEnd}' AND ss.setting_name = 'title' ";
            $queryResult.= " AND ss.locale = '{$locale}' AND s.section_id = ss.section_id  AND ss.setting_value IN ({$newSections}) AND s.context_id = {$journalId} ORDER BY date_submitted ASC ";
            $result = $this->retrieve($queryResult);
            echo $result;   
        }
    }

    public function getSession($journalId)
    {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $newSections = array();
        foreach ($sections as $section) {
            $newSections[$section['title']] = $section['title'];
        }
        return $newSections;
    }

    public function getSectionsOptions($journalId)
    {
        import('classes.core.Services');
		$sections = Services::get('section')
            ->getSectionList($journalId);

        $sectionDao = DAORegistry::getDAO('SectionDAO');

        $newSectionsOptions = array();
        
        foreach ($sections as $section) {
            $sectionObject = $sectionDao->getById($section['id'], $journalId);
            if($sectionObject->getMetaReviewed() == 1){
                $newSectionsOptions[$sectionObject->getLocalizedTitle()] = $sectionObject->getLocalizedTitle();
            }
        }

        return $newSectionsOptions;
    }
}
