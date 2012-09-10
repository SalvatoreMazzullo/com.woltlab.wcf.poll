<dl class="wide">
	{foreach from=$poll->getOptions() item=option}
		<dd>
			<label>
				{if $poll->canVote()}<input type="{if $poll->maxVotes > 1}checkbox{else}radio{/if}" name="pollOptions{@$poll->pollID}[]" value="{$option->optionValue}" data-option-id="{@$option->optionID}"{if $option->selected} checked="checked"{/if} />{/if}
				{$option->optionValue}
			</label>
		</dd>
	{/foreach}
</dl>
{if $poll->canVote()}
	{if $poll->maxVotes > 1}<small>{lang}wcf.poll.multipleVotes{/lang}</small>{/if}
	
	<div class="formSubmit">
		<button class="small jsSubmitVote" data-poll-id="{@$poll->pollID}">{lang}wcf.poll.button.vote{/lang}</button>
	</div>
{else}
	<p class="info">{lang}wcf.poll.restrictedResult{/lang}</p>
{/if}