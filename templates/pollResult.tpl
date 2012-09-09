<ol class="pollResultList">
	{foreach from=$poll->getOptions() item=option}
		<li>
			<meter value="{@$option->votes}" max="{@$poll->votes}">{@$option->getRelativeVotes($poll)}%</meter> <span>{@$option->getRelativeVotes($poll)}%</span>
		</li>
	{/foreach}
</ol>