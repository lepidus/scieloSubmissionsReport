{* Copyright (c) 2019 Lepidus Tecnologia
Distributed under the GNU GPL v2. For full terms see the file docs/COPYING. *}
{strip}
{assign var="pageTitle" value= "plugins.reports.submissions.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

	<form method="post" action="">
		{include file="common/formErrors.tpl"}

		<h2>{translate key="plugins.reports.submissions.period"}</h2>
		<table class="data">
		<tr valign="top">
		 	<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.submissions.dateStart" translate=true}</td> 
			<td>
				<input type="date" id='dateStart' name='dataInicio' from=$dateStart defaultValue=$dateStart value="{$years[0]}"/>
			</td>

		</tr>
		<tr valign="top">
			<td class="label">{fieldLabel name="lastYear" required="true"  key="plugins.reports.submissions.dateEnd"  }</td>
			<td>
				<input type="date" id='dateEnd' name='dataFim' from=$dateEnd defaultValue=$dateEnd value="{$years[1]}"/>
			</td>
		</tr>
		</table>

		{if $sessions|@count > 0}
			<h2>{translate key="plugins.reports.submissions.sections"}</h2>
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
			<input class="pkp_button submitFormButton" type="submit" value="{translate key="plugins.reports.submissions.generate"}" class="button defaultButton" />
			<input type="button" class="pkp_button submitFormButton" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url path="index" escape=false}'" /> 
		</p>
	</form>


{include file="common/footer.tpl"}
