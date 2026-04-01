<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use APP\plugins\reports\scieloSubmissionsReport\ScieloSubmissionsReportPlugin;

trait PluginTestTrait
{
    private function initializePluginLocaleData(): void
    {
        $plugin = new ScieloSubmissionsReportPlugin();
        $plugin->pluginPath = 'plugins/reports/scieloSubmissionsReport';
        $plugin->addLocaleData();
    }
}
