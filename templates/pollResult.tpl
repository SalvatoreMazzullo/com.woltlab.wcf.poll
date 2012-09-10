<ol class="pollResultList">
	{foreach from=$poll->getOptions() item=option}
		<li>
			<div class="pollResultItem">
				<span class="pollMeter pollMeter{@$option->getColorID()}" style="width: {if $option->getRelativeVotes($poll)}{@$option->getRelativeVotes($poll)}%{else}1px{/if}">&nbsp;</span>
			</div>
			<small class="relativeVotes">{@$option->getRelativeVotes($poll)}%</small>
			<small>{$option->optionValue} ({#$option->votes})</small>
		</li>
	{/foreach}
</ol>

{if $poll->isPublic}
	<div class="formSubmit">
		<button class="jsPollShowParticipants small">{lang}wcf.poll.button.showParticipants{/lang}</button>
	</div>
{/if}