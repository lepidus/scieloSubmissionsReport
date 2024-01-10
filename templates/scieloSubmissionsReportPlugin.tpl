{*
  * Copyright (c) 2019-2021 Lepidus Tecnologia
  * Copyright (c) 2020-2021 SciELO
  * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
  *
  *}
{extends file="layouts/backend.tpl"}

{block name="page"}
    <h1 class="app__pageHeading">
		{translate key="plugins.reports.scieloSubmissionsReport.displayName"}
	</h1>

    <div class="app__contentPanel">
    <form id="scieloSubmissionsReportForm" method="post" action="">
        {include file="common/formErrors.tpl"}

        <h2>{translate key="plugins.reports.scieloSubmissionsReport.period"}</h2>
        <div class="data">
            <div id="filterTypeField">
                <p>{translate key="plugins.reports.scieloSubmissionsReport.filterMessage"}</p>
                <select name="selectFilterTypeDate" id="selectFilterTypeDate">
                    <option value="filterBySubmission">{translate key="plugins.reports.scieloSubmissionsReport.filterSubmission"}</option>
                    <option value="filterByFinalDecision">{translate key="plugins.reports.scieloSubmissionsReport.filterDecision"}</option>
                    <option value="filterByBoth">{translate key="plugins.reports.scieloSubmissionsReport.filterBoth"}</option>
                </select>
            </div>

            <div id="dateFilterFields">
                <!-- Submitted Date -->
                <fieldset id="submittedDateFields" class="search_advanced">
                    <legend>
                        {translate key="plugins.reports.scieloSubmissionsReport.dateSubmittedInterval"}
                    </legend>
                    <div class="date_range">
                        <div class="from">
                            <label class="label">
                                {translate key="stats.dateRange.from"}
                            </label>
                            <input type="date" id='startSubmissionDateInterval' name='startSubmissionDateInterval' from=$startSubmissionDateInterval defaultValue=$startSubmissionDateInterval value="{$years[0]|escape}"/>
                        </div>
                        <div class="to">
                            <label class="label">
                                {translate key="plugins.reports.scieloSubmissionsReport.until"}
                            </label>
                            <input type="date" id='endSubmissionDateInterval' name='endSubmissionDateInterval' from=$endSubmissionDateInterval defaultValue=$endSubmissionDateInterval value="{$years[1]|escape}"/>
                        </div>
                    </div>
                </fieldset>

                <!-- Final Decision Date-->
                <fieldset id="finalDecisionDateFields" class="search_advanced" hidden="true">
                    <legend>
                        {translate key="plugins.reports.scieloSubmissionsReport.finalDecisionDateInterval"}
                    </legend>
                    <div class="date_range">
                        <div class="from">
                            <label class="label">
                                {translate key="stats.dateRange.from"}
                            </label>
                            <input type="date" id='startFinalDecisionDateInterval' name='startFinalDecisionDateInterval' from=$startFinalDecisionDateInterval defaultValue=$startFinalDecisionDateInterval value="{$years[0]|escape}"/>
                        </div>
                        <div class="to">
                            <label class="label">
                                {translate key="plugins.reports.scieloSubmissionsReport.until"}
                            </label>
                            <input type="date" id='endFinalDecisionDateInterval' name='endFinalDecisionDateInterval' from=$endFinalDecisionDateInterval defaultValue=$endFinalDecisionDateInterval value="{$years[1]|escape}"/>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>

        {if $sections|@count > 0}
            <h2>{translate key="plugins.reports.scieloSubmissionsReport.sections"}</h2>
            <table>
                <div class= "pkpListPanel">
                    <tr>
                        <td class="value" colspan="2">
                            {fbvElement type="checkBoxGroup" name="sections" id="sections" from=$sections selected=$sections_options translate=false}
                        </td>
                    </tr>
                </div>
            </table>
        {/if}

        {if $application eq "ops"}
            <div id="includeViewsCheckboxDiv">
                <input type="checkbox" name="includeViews" id="includeViewsCheckbox" value="1"/>
                {translate key="plugins.reports.scieloSubmissionsReport.checkboxIncludeViews"}
            </div>
        {/if}

        <p id="noticeTimeGenerateReport">
            {translate key="plugins.reports.scieloSubmissionsReport.noticeTimeGenerateReport"}
        </p>

        <div id="actionsButton">
            <input class="pkp_button submitFormButton" type="submit" value="{translate key="plugins.reports.scieloSubmissionsReport.generate"}" class="button defaultButton" />
            <input type="button" class="pkp_button submitFormButton" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url path="index" escape=false}'" />
        </div>
    </form>

    <script>
        $(function() {ldelim}
            var filterTypeSelection = document.getElementById('selectFilterTypeDate');
            var submissionDiv = document.getElementById('submittedDateFields');
            var decisionDiv = document.getElementById('finalDecisionDateFields');

            filterTypeSelection.addEventListener("change", function(){ldelim}
                var selectedValue = filterTypeSelection.value;
                if(selectedValue == "filterBySubmission"){ldelim}
                    submissionDiv.hidden = false;
                    decisionDiv.hidden = true;
                {rdelim}
                else if(selectedValue == "filterByFinalDecision"){ldelim}
                    submissionDiv.hidden = true;
                    decisionDiv.hidden = false;
                {rdelim}
                else {ldelim}
                    submissionDiv.hidden = false;
                    decisionDiv.hidden = false;
                {rdelim}
            {rdelim});
        {rdelim});
    </script>

{/block}
