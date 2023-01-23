<?php
/**
 * @file plugins/reports/scieloSubmissionsReport/ScieloSubmissionsReportForm.inc.php
 *
 * Copyright (c) 2019-2021 Lepidus Tecnologia
 * Copyright (c) 2020-2021 SciELO
 * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @ingroup plugins_reports_scieloSubmissions
 *
 * @brief SciELO Submissions report Form
 */
import('lib.pkp.classes.form.Form');
import('plugins.reports.scieloSubmissionsReport.classes.ClosedDateInterval');
import('plugins.reports.scieloSubmissionsReport.classes.ScieloSubmissionsReportFactory');

class ScieloSubmissionsReportForm extends Form
{
    /* @var int Associated context ID */
    private $contextId;

    /* @var ReviewersReport  */
    private $plugin;

    private $application;
    private $sections;
    private $submissionDateInterval;
    private $finalDecisionDateInterval;
    private $includeViews;

    /**
     * Constructor
     * @param $plugin ReviewersReport Manual payment plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->application = substr(Application::getName(), 0, 3);
        $request = Application::get()->getRequest();
        $this->contextId = $request->getContext()->getId();
        $this->sections = array();
        $this->submissionDateInterval = null;
        $this->finalDecisionDateInterval = null;
        $this->includeViews = false;

        parent::__construct($plugin->getTemplateResource('scieloSubmissionsReportPlugin.tpl'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * Initialize form data.
     */
    public function initData()
    {
        $contextId = $this->contextId;
        $plugin = $this->plugin;
        $this->setData('scieloSubmissionsReport', $plugin->getSetting($contextId, 'scieloSubmissionsReport'));
    }

    public function validateReportData($reportParams)
    {
        if (array_key_exists('sections', $reportParams)) {
            $this->sections = $reportParams['sections'];
        }
        if (array_key_exists('includeViews', $reportParams)) {
            $this->includeViews = $reportParams['includeViews'];
        }
        $filteringType = $reportParams['selectFilterTypeDate'];

        if ($filteringType == 'filterBySubmission' || $filteringType == 'filterByBoth') {
            $submissionDateInterval = $this->validateDateInterval($reportParams['startSubmissionDateInterval'], $reportParams['endSubmissionDateInterval'], 'plugins.reports.scieloSubmissionsReport.warning.errorSubmittedDate');
            if (is_null($submissionDateInterval)) {
                return false;
            }
            $this->submissionDateInterval = $submissionDateInterval;
        }

        if ($filteringType == 'filterByFinalDecision' || $filteringType == 'filterByBoth') {
            $finalDecisionDateInterval = $this->validateDateInterval($reportParams['startFinalDecisionDateInterval'], $reportParams['endFinalDecisionDateInterval'], 'plugins.reports.scieloSubmissionsReport.warning.errorDecisionDate');
            if (is_null($finalDecisionDateInterval)) {
                return false;
            }
            $this->finalDecisionDateInterval = $finalDecisionDateInterval;
        }

        return true;
    }

    private function validateDateInterval($startInterval, $endInterval, $errorMessage)
    {
        $dateInterval = new ClosedDateInterval($startInterval, $endInterval);
        if (!$dateInterval->isValid()) {
            echo __($errorMessage);
            return null;
        }
        return $dateInterval;
    }

    private function emitHttpHeaders($request)
    {
        $context = $request->getContext();
        header('content-type: text/comma-separated-values');
        $acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $context->getLocalizedAcronym());
        header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
    }

    public function generateReport($request)
    {
        $this->emitHttpHeaders($request);

        $locale = AppLocale::getLocale();
        $scieloSubmissionsReportFactory = new ScieloSubmissionsReportFactory($this->application, $this->contextId, $this->sections, $this->submissionDateInterval, $this->finalDecisionDateInterval, $locale, $this->includeViews);
        $scieloSubmissionsReport = $scieloSubmissionsReportFactory->createReport();

        $csvFile = fopen('php://output', 'wt');
        $scieloSubmissionsReport->buildCSV($csvFile);
    }

    public function display($request = null, $template = null, $args = null)
    {
        $sections = $this->getAvailableSections($this->contextId);
        $sections_options = $this->getSectionsOptions($this->contextId, $sections);

        $templateManager = TemplateManager::getManager();
        $url = $request->getBaseUrl() . '/' . $this->plugin->getPluginPath() . '/templates/scieloSubmissionsStyleSheet.css';
        $templateManager->addStyleSheet('scieloSubmissionsStyleSheet', $url, array(
            'priority' => STYLE_SEQUENCE_CORE,
            'contexts' => 'backend',
        ));
        $templateManager->assign('application', $this->application);
        $templateManager->assign('sections', $sections);
        $templateManager->assign('sections_options', $sections_options);
        $templateManager->assign('years', array(0=>$args[0], 1=>$args[1]));
        $templateManager->assign([
            'breadcrumbs' => [
                [
                    'id' => 'reports',
                    'name' => __('manager.statistics.reports'),
                    'url' => $request->getRouter()->url($request, null, 'stats', 'reports'),
                ],
                [
                    'id' => 'scieloSubmissionsReport',
                    'name' => __('plugins.reports.scieloSubmissionsReport.displayName')
                ],
            ],
            'pageTitle', __('plugins.reports.scieloSubmissionsReport.displayName')
        ]);

        $templateManager->display($this->plugin->getTemplateResource($template));
    }

    private function getAvailableSections($contextId)
    {
        $sections = Services::get('section')->getSectionList($contextId);

        $listOfSections = array();
        foreach ($sections as $section) {
            $listOfSections[$section['id']] = $section['title'];
        }
        return $listOfSections;
    }

    public function getSectionsOptions($contextId, $sections)
    {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sectionsOptions = array();

        foreach ($sections as $sectionId => $sectionName) {
            $sectionObject = $sectionDao->getById($sectionId, $contextId);
            if ($sectionObject->getMetaReviewed() == 1) {
                $sectionsOptions[$sectionObject->getLocalizedTitle()] = $sectionObject->getLocalizedTitle();
            }
        }

        return $sectionsOptions;
    }
}
