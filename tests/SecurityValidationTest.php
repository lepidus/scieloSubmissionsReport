<?php

use PHPUnit\Framework\TestCase;

class SecurityValidationTest extends TestCase
{
    private $pluginRoot;

    protected function setUp(): void
    {
        $this->pluginRoot = dirname(__DIR__);
    }

    public function testSettingsFormRequiresPostAndCsrfValidation(): void
    {
        $source = file_get_contents($this->pluginRoot . '/classes/form/ScieloSubmissionsReportSettingsForm.inc.php');

        $this->assertStringContainsString('new FormValidatorPost($this)', $source);
        $this->assertStringContainsString('new FormValidatorCSRF($this)', $source);
    }

    public function testSettingsAreValidatedBeforeTheyArePersisted(): void
    {
        $source = file_get_contents($this->pluginRoot . '/ScieloSubmissionsReportPlugin.inc.php');

        $this->assertMatchesRegularExpression(
            '/readInputData\(\);\s*if \(\$form->validate\(\)\) \{\s*\$form->execute\(\);/s',
            $source
        );
    }

    public function testReportRunsCoreFormValidation(): void
    {
        $source = file_get_contents($this->pluginRoot . '/ScieloSubmissionsReportForm.inc.php');

        $this->assertStringContainsString('if (!parent::validate())', $source);
    }

    public function testReportTemplateSubmitsCsrfToken(): void
    {
        $template = file_get_contents($this->pluginRoot . '/templates/scieloSubmissionsReportPlugin.tpl');

        $this->assertStringContainsString('{csrf}', $template);
    }
}
