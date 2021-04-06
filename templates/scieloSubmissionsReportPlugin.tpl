{*
  * Copyright (c) 2019-2021 Lepidus Tecnologia
  * Copyright (c) 2020-2021 SciELO
  * Distributed under the GNU GPL v3. For full terms see LICENSE or https://www.gnu.org/licenses/gpl-3.0.txt
  *
  *}

{strip}
{assign var="pageTitle" value= "plugins.reports.scieloSubmissionsReport.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form id="scieloSubmissionsReportForm" method="post" action="">
	{include file="common/formErrors.tpl"}

	<h2>{translate key="plugins.reports.scieloSubmissionsReport.period"}</h2>
	<div class="data">
		<div id="filterTypeField">	
			<p>{translate key="plugins.reports.scieloSubmissionsReport.filterMessage"}</p>
			<select name="selectFilterTypeDate" id="selectFilterTypeDate">
				<option value="1">{translate key="plugins.reports.scieloSubmissionsReport.filterSubmission"}</option>
				<option value="2">{translate key="plugins.reports.scieloSubmissionsReport.filterDecision"}</option>
				<option value="3">{translate key="plugins.reports.scieloSubmissionsReport.filterBoth"}</option>
			</select>
		</div>

		<div id="dateFilterFields">
			<!-- Submission -->
			<div id="dateSubmissionFields">
				<div id="fieldDateSubmittedStart">	
					<label for="dateSubmittedStart">{translate key="plugins.reports.scieloSubmissionsReport.dateSubmittedStart"}</label> 
					<input type="date" id='dateSubmittedStart' name='initialSubmissionDate' from=$dateSubmittedStart defaultValue=$dateSubmittedStart value="{$years[0]}"/>
				</div>
				<div id="fieldDateSubmittedEnd">
					<label for="dateSubmittedEnd">{translate key="plugins.reports.scieloSubmissionsReport.dateSubmittedEnd"}</label>
					<input type="date" id='dateSubmittedEnd' name='finalSubmissionDate' from=$dateSubmittedEnd defaultValue=$dateSubmittedEnd value="{$years[1]}"/>
				</div>
			</div>
			
			<!-- Decision -->
			<div id="decisionDateFields" hidden="true">
				<div id="fieldDateDecisionStart">
					<label for="dateDecisionStart">{translate key="plugins.reports.scieloSubmissionsReport.dateDecisionStart"}</label> 
					<input type="date" id='dateDecisionStart' name='initialDecisionDate' from=$dateDecisionStart defaultValue=$dateDecisionStart value="{$years[0]}"/>
				</div>
				<div id="fieldDateDecisionEnd">
					<label for="dateDecisionEnd">{translate key="plugins.reports.scieloSubmissionsReport.dateDecisionEnd"}</label>
					<input type="date" id='dateDecisionEnd' name='finalDecisionDate' from=$dateDecisionEnd defaultValue=$dateDecisionEnd value="{$years[1]}"/>
				</div>
			</div>
		</div>
	</div>

	{if $sessions|@count > 0}
		<h2>{translate key="plugins.reports.scieloSubmissionsReport.sections"}</h2>
		<table> 
			<div class= "pkpListPanel"> 
				<tr>	
					<td class="value" colspan="2">
						{fbvElement type="checkBoxGroup" name="sessions" id="sessions" from=$sessions selected=$sessions_options translate=false}
					</td>
				</tr>
			</div>
		</table> 
	{/if}
	
	<p id="actionsButton">
		<input type="hidden" name="generate" value="1" type="generate" />
		<input class="pkp_button submitFormButton" type="submit" value="{translate key="plugins.reports.scieloSubmissionsReport.generate"}" class="button defaultButton" />
		<input type="button" class="pkp_button submitFormButton" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url path="index" escape=false}'" /> 
	</p>
</form>

{include file="common/footer.tpl"}

<script>
	var filterTypeSelection = document.getElementById('selectFilterTypeDate');
	var submissionDiv = document.getElementById('dateSubmissionFields');
	var decisionDiv = document.getElementById('decisionDateFields');

	filterTypeSelection.addEventListener("change", function(){ldelim}
		var selectedValue = filterTypeSelection.value;
		if(selectedValue == 1){ldelim}
			submissionDiv.hidden = false;
			decisionDiv.hidden = true;
		{rdelim}
		else if(selectedValue == 2){ldelim}
			submissionDiv.hidden = true;
			decisionDiv.hidden = false;
		{rdelim}
		else {ldelim}
			submissionDiv.hidden = false;
			decisionDiv.hidden = false;
		{rdelim}
	{rdelim});
</script>