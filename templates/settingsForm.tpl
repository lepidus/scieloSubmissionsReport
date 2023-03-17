{**
 * templates/settingsForm.tpl
 *
 * Copyright (c) 2023 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * Cariniana Preservation plugin settings
 *
 *}

<script>
    $(function() {ldelim}
        $('#scieloSubmissionsReportSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
    {rdelim});
</script>

<div id="scieloSubmissionsReportSettings">
    <form class="pkp_form" id="scieloSubmissionsReportSettingsForm" method="post" action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="reports" plugin=$pluginName verb="pluginSettings" save=true}">
        {csrf}
        {include file="controllers/notification/inPlaceNotification.tpl" notificationId="scieloSubmissionsReportSettingsFormNotification"}

        {fbvFormSection title="plugins.reports.scieloSubmissionsReport.settings.recipientEmail"}
            {fbvElement id="recipientEmail" class="recipientEmail" type="email" value="{$recipientEmail|escape}" required="true" label="plugins.reports.scieloSubmissionsReport.settings.recipientEmail.description" size=$fbvStyles.size.MEDIUM}
        {/fbvFormSection}
        {fbvFormButtons submitText="common.save"}
    </form>
</div>