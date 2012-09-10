{if $__wcf->getUser()->userID && !$__pollLoadedJavaScript|isset}
	{assign var=__pollLoadedJavaScript value=true}
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Poll.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			new WCF.Poll.Manager('.pollContainer');
		});
		//]]>
	</script>
{/if}

<div class="container containerPadding pollContainer" data-poll-id="{@$poll->pollID}" data-can-vote="{if $poll->canVote()}1{else}0{/if}" data-can-view-result="{if $poll->canSeeResult()}1{else}0{/if}" data-in-vote="{if $poll->canVote() && !$poll->isParticipant()}1{else}0{/if}">
	<fieldset>
		<legend>{$poll->question}</legend>
		
		<small class="jsPollAllVotes">{if $poll->isPublic}<a>{/if}{lang}wcf.poll.totalVotes{/lang}{if $poll->isPublic}</a>{/if} <span class="badge">{#$poll->votes}</span></small>
		
		<div class="pollInnerContainer">
			{if !$__wcf->getUser()->userID}
				{if $poll->canSeeResult()}
					{include file='pollResult'}
				{else}
					{include file='pollVote'}
				{/if}
			{else}
				{if $poll->canVote() && !$poll->isParticipant()}
					{include file='pollVote'}
				{else}
					{include file='pollResult'}
				{/if}
			{/if}
		</div>
	</fieldset>
	
	{if $__wcf->getUser()->userID}
		<div class="formSubmit">
			<button class="small jsPollVote">{lang}wcf.poll.button.showVote{/lang}</button>
			<button class="small jsPollResult">{lang}wcf.poll.button.showResult{/lang}</button>
		</div>
	{/if}
</div>