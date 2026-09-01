<?php

namespace APP\plugins\reports\scieloSubmissionsReport\tests;

use PHPUnit\Framework\TestCase;

class SecurityValidationTest extends TestCase
{
    private string $pluginRoot;

    protected function setUp(): void
    {
        $this->pluginRoot = dirname(__DIR__);
    }

    public function testSettingsFormRequiresPostAndCsrfValidation(): void
    {
        $source = file_get_contents($this->pluginRoot . '/classes/form/ScieloSubmissionsReportSettingsForm.php');

        $this->assertStringContainsString('new FormValidatorPost($this)', $source);
        $this->assertStringContainsString('new FormValidatorCSRF($this)', $source);
    }

    public function testSettingsAreValidatedBeforeTheyArePersisted(): void
    {
        $source = file_get_contents($this->pluginRoot . '/ScieloSubmissionsReportPlugin.php');

        $this->assertMatchesRegularExpression(
            '/readInputData\(\);\s*if \(\$form->validate\(\)\) \{\s*\$form->execute\(\);/s',
            $source
        );
    }

    public function testReportRunsCoreFormValidation(): void
    {
        $source = file_get_contents($this->pluginRoot . '/ScieloSubmissionsReportForm.php');

        $this->assertStringContainsString('if (!parent::validate())', $source);
    }

    public function testReportTemplateSubmitsCsrfToken(): void
    {
        $template = file_get_contents($this->pluginRoot . '/templates/scieloSubmissionsReportPlugin.tpl');

        $this->assertStringContainsString('{csrf}', $template);
    }
}
