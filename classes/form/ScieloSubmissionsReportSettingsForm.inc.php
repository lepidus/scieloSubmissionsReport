<?php

use PKP\form\Form;

class ScieloSubmissionsReportSettingsForm extends Form
{
    public const CONFIG_VARS = [
        'recipientEmail' => 'string',
    ];
    private $plugin;
    private $coontextId;

    public function __construct($plugin, $contextId)
    {
        $this->plugin = $plugin;
        $this->contextId = $contextId;
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
    }

    public function initData()
    {
        $this->_data = [];
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $this->_data[$configVar] = $this->plugin->getSetting($this->contextId, $configVar);
        }
    }

    public function readInputData()
    {
        $this->readUserVars(array_keys(self::CONFIG_VARS));
    }

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pluginName', $this->plugin->getName());
        $templateMgr->assign('applicationName', Application::get()->getName());
        return parent::fetch($request, $template, $display);
    }

    public function execute(...$functionArgs)
    {
        foreach (self::CONFIG_VARS as $configVar => $type) {
            $this->plugin->updateSetting($this->contextId, $configVar, $this->getData($configVar), $type);
        }

        parent::execute(...$functionArgs);
    }
}
