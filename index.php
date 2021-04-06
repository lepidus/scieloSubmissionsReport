<?php

/**
 * @defgroup plugins_reports_ScieloSubmissionsReport SciELO Submissions Report Plugin
 */
 
/**
 * @file plugins/reports/scieloSubmissions/index.php
 *
 * Copyright (c) 2019-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @ingroup plugins_reports_scieloSubmissions
 * @brief Wrapper for SciELO Submissions report plugin.
 *
 */ 

require_once('ScieloSubmissionsReportPlugin.inc.php');

return new ScieloSubmissionsReportPlugin();


