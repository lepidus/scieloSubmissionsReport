<?php

/**
 * @defgroup plugins_reports_ScieloSubmissionsReport SciELO Submissions Report Plugin
 */
 
/**
 * @file plugins/reports/scieloSubmissions/index.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_scieloSubmissions
 * @brief Wrapper for SciELO Submissions report plugin.
 *
 */ 

require_once('ScieloSubmissionsReportPlugin.inc.php');

return new ScieloSubmissionsReportPlugin();


