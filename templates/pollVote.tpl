<dl class="wide">
	{foreach from=$poll->getOptions() item=option}
		<dd>
			<label>
				<input type="{if $poll->maxVotes > 1}checkbox{else}radio{/if}" name="pollOptions{@$poll->pollID}[]" value="{$option->optionValue}" data-option-id="{@$option->optionID}"{if $option->selected} checked="checked"{/if} />
				{$option->optionValue}
			</label>
		</dd>
	{/foreach}
</dl>
{if $poll->maxVotes > 1}<small>{lang}wcf.poll.multipleVotes{/lang}</small>{/if}

<div class="formSubmit">
	<button class="jsSubmitVote" data-poll-id="{@$poll->pollID}">{lang}wcf.poll.button.vote{/lang}</button>
</div>