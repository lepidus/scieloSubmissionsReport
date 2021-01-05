{* Copyright (c) 2019 Lepidus Tecnologia
Distributed under the GNU GPL v2. For full terms see the file docs/COPYING. *}
{strip}
{assign var="pageTitle" value= "plugins.reports.scieloSubmissionsReport.displayName"}
{include file="common/header.tpl"}
{/strip}

<br/>

<form id="scieloSubmissionsReportForm" method="post" action="">
	{include file="common/formErrors.tpl"}

	<h2>{translate key="plugins.reports.scieloSubmissionsReport.period"}</h2>
	<div class="data">
		<div id="campoTipoFiltragem">	
			<p>{translate key="plugins.reports.scieloSubmissionsReport.filterMessage"}</p>
			<select name="selectTipoFiltragemData" id="selectTipoFiltragemData">
				<option value="1">{translate key="plugins.reports.scieloSubmissionsReport.filterSubmission"}</option>
				<option value="2">{translate key="plugins.reports.scieloSubmissionsReport.filterDecision"}</option>
				<option value="3">{translate key="plugins.reports.scieloSubmissionsReport.filterBoth"}</option>
			</select>
		</div>

		<div id="camposFiltragemData">
			<!-- Submissão -->
			<div id="camposDataSubmissao">
				<div id="fieldDateSubmittedStart">	
					<label for="dateSubmittedStart">{translate key="plugins.reports.scieloSubmissionsReport.dateSubmittedStart"}</label> 
					<input type="date" id='dateSubmittedStart' name='dataSubmissaoInicial' from=$dateSubmittedStart defaultValue=$dateSubmittedStart value="{$years[0]}"/>
				</div>
				<div id="fieldDateSubmittedEnd">
					<label for="dateSubmittedEnd">{translate key="plugins.reports.scieloSubmissionsReport.dateSubmittedEnd"}</label>
					<input type="date" id='dateSubmittedEnd' name='dataSubmissaoFinal' from=$dateSubmittedEnd defaultValue=$dateSubmittedEnd value="{$years[1]}"/>
				</div>
			</div>
			
			<!-- Decisão -->
			<div id="camposDataDecisao" hidden="true">
				<div id="fieldDateDecisionStart">
					<label for="dateDecisionStart">{translate key="plugins.reports.scieloSubmissionsReport.dateDecisionStart"}</label> 
					<input type="date" id='dateDecisionStart' name='dataDecisaoInicial' from=$dateDecisionStart defaultValue=$dateDecisionStart value="{$years[0]}"/>
				</div>
				<div id="fieldDateDecisionEnd">
					<label for="dateDecisionEnd">{translate key="plugins.reports.scieloSubmissionsReport.dateDecisionEnd"}</label>
					<input type="date" id='dateDecisionEnd' name='dataDecisaoFinal' from=$dateDecisionEnd defaultValue=$dateDecisionEnd value="{$years[1]}"/>
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
	var selecaoTipoFiltragem = document.getElementById('selectTipoFiltragemData');
	var divSubmissao = document.getElementById('camposDataSubmissao');
	var divDecisao = document.getElementById('camposDataDecisao');

	selecaoTipoFiltragem.addEventListener("change", function(){ldelim}
		var valorSelecionado = selecaoTipoFiltragem.value;
		if(valorSelecionado == 1){ldelim}
			divSubmissao.hidden = false;
			divDecisao.hidden = true;
		{rdelim}
		else if(valorSelecionado == 2){ldelim}
			divSubmissao.hidden = true;
			divDecisao.hidden = false;
		{rdelim}
		else {ldelim}
			divSubmissao.hidden = false;
			divDecisao.hidden = false;
		{rdelim}
	{rdelim});
</script>