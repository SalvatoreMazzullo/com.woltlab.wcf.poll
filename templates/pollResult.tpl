<ol class="pollResultList">
	{foreach from=$poll->getOptions() item=option}
		<li>
			<div class="pollResultItem">
				<span class="pollMeter pollMeter{@$option->getColorID()}" style="width: {@$option->getRelativeVotes($poll)}%">&nbsp;</span>
			</div>
			<small class="relativeVotes">{@$option->getRelativeVotes($poll)}%</small>
			<small>{$option->optionValue} ({#$option->votes})</small>
		</li>
	{/foreach}
</ol>