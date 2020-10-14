{* Copyright (c) 2019 Lepidus Tecnologia
Distributed under the GNU GPL v2. For full terms see the file docs/COPYING. *}
{strip}
{assign var="pageTitle" value= "plugins.reports.scieloSubmissionsReport.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

	<form method="post" action="">
		{include file="common/formErrors.tpl"}

		<h2>{translate key="plugins.reports.scieloSubmissionsReport.period"}</h2>
		<table class="data">
		<tr valign="top">
		 	<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.scieloSubmissionsReport.dateSubmittedStart" translate=true}</td> 
			<td>
				<input type="date" id='dateSubmittedStart' name='dataSubmissaoInicial' from=$dateSubmittedStart defaultValue=$dateSubmittedStart value="{$years[0]}"/>
			</td>
		</tr>
		
		<tr valign="top">
			<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.scieloSubmissionsReport.dateSubmittedEnd"  }</td>
			<td>
				<input type="date" id='dateSubmittedEnd' name='dataSubmissaoFinal' from=$dateSubmittedEnd defaultValue=$dateSubmittedEnd value="{$years[1]}"/>
			</td>
		</tr>
		
		<!-- novos dois campos -->
		<tr valign="top">
		 	<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.scieloSubmissionsReport.dateDecisionStart" translate=true}</td> 
			<td>
				<input type="date" id='dateDecisionStart' name='dataDecisaoInicial' from=$dateDecisionStart defaultValue=$dateDecisionStart value="{$years[0]}"/>
			</td>

		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.scieloSubmissionsReport.dateDecisionEnd"  }</td>
			<td>
				<input type="date" id='dateDecisionEnd' name='dataDecisaoFinal' from=$dateDecisionEnd defaultValue=$dateDecisionEnd value="{$years[1]}"/>
			</td>
		</tr>

		</table>

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
