<?php

/**
 * @defgroup plugins_reports_Submission Submission Report Plugin
 */
 
/**
 * @file plugins/reports/submissions/index.php
 *
 * Copyright (c) 2019 Lepidus Tecnologia
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_reports_submission
 * @brief Wrapper for Submission report plugin.
 *
 */ 

require_once('SubmissionReportPlugin.inc.php');

return new SubmissionReportPlugin();


