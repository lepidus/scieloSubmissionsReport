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

    /**
     * Constructor
     * @param $plugin ReviewersReport Manual payment plugin
     */
    public function __construct($plugin, $application)
    {
        $this->plugin = $plugin;
        $this->application = $application;
        $request = Application::get()->getRequest();
        $this->contextId = $request->getContext()->getId();

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

    public function generateReport($request, $sections, $startSubmissionDateInterval = null, $endSubmissionDateInterval = null, $startFinalDecisionDateInterval = null, $endFinalDecisionDateInterval = null)
    {
        $submissionDateInterval = $finalDecisionDateInterval = null;
        
        if(!is_null($startSubmissionDateInterval)){
            $submissionDateInterval = new ClosedDateInterval($startSubmissionDateInterval, $endSubmissionDateInterval);
            if (!$submissionDateInterval->isValid()) {
                echo __('plugins.reports.scieloSubmissionsReport.warning.errorSubmittedDate');
                return;
            }
        }
        
        if(!is_null($startFinalDecisionDateInterval)){
            $finalDecisionDateInterval = new ClosedDateInterval($startFinalDecisionDateInterval, $endFinalDecisionDateInterval);
            if (!$finalDecisionDateInterval->isValid()) {
                echo __('plugins.reports.scieloSubmissionsReport.warning.errorDecisionDate');
                return;
            }
        }

        $context = $request->getContext();
        header('content-type: text/comma-separated-values');
        $acronym = PKPString::regexp_replace("/[^A-Za-z0-9 ]/", '', $context->getLocalizedAcronym());
        header('content-disposition: attachment; filename=submissions' . $acronym . '-' . date('YmdHis') . '.csv');
        
        //Gerar o csv
        $application = substr(Application::getName(), 0, 3);
        $locale = AppLocale::getLocale();
        $scieloSubmissionsReportFactory = new ScieloSubmissionsReportFactory();
        $scieloSubmissionsReport = $scieloSubmissionsReportFactory->createReport($application, $this->contextId, $sections, $submissionDateInterval, $finalDecisionDateInterval, $locale);

        $csvFile = fopen('php://output', 'wt');
        $scieloSubmissionsReport->buildCSV($csvFile);
    }

    public function display($request = null, $template = null, $args = null)
    {
        $sections = $this->getAvailableSections($this->contextId);
        $sections_options = $this->getSectionsOptions($this->contextId, $sections);

        $templateManager = TemplateManager::getManager();
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

    private function getAvailableSections($contextId) {
		$sections = Services::get('section')->getSectionList($contextId);

        $listOfSections = array();
        foreach ($sections as $section) {
            $listOfSections[$section['id']] = $section['title'];
        }
        return $listOfSections;
    }

    public function getSectionsOptions($contextId, $sections) {
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $sectionsOptions = array();
        
        foreach ($sections as $sectionId => $sectionName) {
            $sectionObject = $sectionDao->getById($sectionId, $contextId);
            if($sectionObject->getMetaReviewed() == 1){
                $sectionsOptions[$sectionObject->getLocalizedTitle()] = $sectionObject->getLocalizedTitle();
            }
        }

        return $sectionsOptions;
    }
}
