/**
 * Namespace for poll-related classes.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 */
WCF.Poll = { };

/**
 * Handles poll option management.
 * 
 * @param	string		containerID
 * @param	array<object>	optionList
 */
WCF.Poll.Management = Class.extend({
	/**
	 * container object
	 * @var	jQuery
	 */
	_container: null,
	
	/**
	 * width for input-elements
	 * @var	integer
	 */
	_inputSize: 0,
	
	/**
	 * Initializes the WCF.Poll.Management class.
	 * 
	 * @param	string		containerID
	 * @param	array<object>	optionList
	 */
	init: function(containerID, optionList) {
		this._container = $('#' + containerID).children('ol:eq(0)');
		if (!this._container.length) {
			console.debug("[WCF.Poll.Management] Invalid container id given, aborting.");
			return;
		}
		
		optionList = optionList || [ ];
		this._createOptionList(optionList);
		
		// bind event listener
		$(window).resize($.proxy(this._resize, this));
		this._container.parents('form').submit($.proxy(this._submit, this));
		
		// init sorting
		new WCF.Sortable.List(containerID, '', undefined, undefined, true);
		
		// trigger resize event for field length calculation
		this._resize();
	},
	
	/**
	 * Creates the option list on init.
	 * 
	 * @param	array<object>		optionList
	 */
	_createOptionList: function(optionList) {
		for (var $i = 0, $length = optionList.length; $i < $length; $i++) {
			var $option = optionList[$i];
			this._createOption($option.optionValue, $option.optionID);
		}
		
		// add empty option
		this._createOption();
	},
	
	/**
	 * Creates a new option element.
	 * 
	 * @param	string		optionValue
	 * @param	integer		optionID
	 * @param	jQuery		insertAfter
	 */
	_createOption: function(optionValue, optionID, insertAfter) {
		optionValue = optionValue || '';
		optionID = parseInt(optionID) || 0;
		insertAfter = insertAfter || null;
		
		var $listItem = $('<li class="sortableNode" />').data('optionID', optionID);
		if (insertAfter === null) {
			$listItem.appendTo(this._container);
		}
		else {
			$listItem.insertAfter(insertAfter);
		}
		
		// insert buttons
		var $buttonContainer = $('<span class="sortableButtonContainer" />').appendTo($listItem);
		$('<img src="' + WCF.Icon.get('wcf.icon.add') + '" alt="" title="' + WCF.Language.get('wcf.poll.addOption') + '" class="icon16 jsTooltip" />').click($.proxy(this._addOption, this)).appendTo($buttonContainer);
		$('<img src="' + WCF.Icon.get('wcf.icon.delete') + '" alt="" title="' + WCF.Language.get('wcf.poll.removeOption') + '" class="icon16 jsTooltip" />').click($.proxy(this._removeOption, this)).appendTo($buttonContainer);
		
		// insert input field
		$('<input type="text" value="' + optionValue + '" />').css({ width: this._inputSize + "px" }).appendTo($listItem);
	},
	
	/**
	 * Adds a new option after current one.
	 * 
	 * @param	object		event
	 */
	_addOption: function(event) {
		var $listItem = $(event.currentTarget).parents('li');
		
		this._createOption(undefined, undefined, $listItem);
	},
	
	/**
	 * Removes an option.
	 * 
	 * @param	object		event
	 */
	_removeOption: function(event) {
		$(event.currentTarget).parents('li').remove();
		
		if (this._container.children('li').length == 0) {
			this._createOption();
		}
	},
	
	/**
	 * Handles the 'resize'-event to adjust input-width.
	 */
	_resize: function() {
		var $containerWidth = this._container.innerWidth();
		
		// select first option to determine dimensions
		var $listItem = this._container.children('li:eq(0)');
		var $buttonWidth = $listItem.children('.sortableButtonContainer').outerWidth();
		var $inputSize = $containerWidth - $buttonWidth;
		
		if ($inputSize != this._inputSize) {
			this._inputSize = $inputSize;
			
			// update width of <input /> elements
			this._container.find('li > input').css({ width: this._inputSize + 'px' });
		}
	},
	
	/**
	 * Inserts hidden input elements storing the option values.
	 */
	_submit: function() {
		var $options = [ ];
		this._container.children('li').each(function(index, listItem) {
			var $listItem = $(listItem);
			var $optionValue = $.trim($listItem.children('input').val());
			
			// ignore empty values
			if ($optionValue != '') {
				$options.push({
					optionID: $listItem.data('optionID'),
					optionValue: $optionValue
				});
			}
		});
		
		// create hidden input fields
		if ($options.length) {
			var $formSubmit = this._container.parents('form').find('.formSubmit');
			
			for (var $i = 0, $length = $options.length; $i < $length; $i++) {
				var $option = $options[$i];
				$('<input type="hidden" name="pollOptions[' + $i + ']" value="' + $option.optionID + '_' + $option.optionValue + '" />').appendTo($formSubmit);
			}
		}
	}
});

WCF.Poll.Handler = Class.extend({
	_cache: { },
	_canSeeResult: { },
	_canVote: { },
	_objectType: '',
	_polls: { },
	_proxy: null,
	
	init: function(objectType, containerSelector) {
		var $polls = $(containerSelector);
		if (!$polls.length) {
			console.debug("[WCF.Poll.Manager] Given selector '" + containerSelector + "' does not match, aborting.");
			return;
		}
		
		this._objectType = objectType || '';
		if (!this._objectType) {
			console.debug("[WCF.Poll.Manager] Invalid object type given, aborting.");
			return;
		}
		
		this._cache = { };
		this._canSeeResult = { };
		this._polls = { };
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this),
			url: 'index.php/Poll/?t=' + SECURITY_TOKEN + SID_ARG_2ND
		});
		
		var self = this;
		$polls.each(function(index, poll) {
			var $poll = $(poll);
			var $pollID = $poll.data('pollID');
			
			if (self._polls[$pollID] === undefined) {
				self._cache[$pollID] = {
					result: '',
					vote: ''
				};
				self._polls[$pollID] = $poll;
				
				self._canSeeResult[$pollID] = ($poll.data('canSeeResult')) ? true : false;
				self._canVote[$pollID] = ($poll.data('canVote')) ? true : false;
				
				self._bindListeners($pollID);
				
				if ($poll.data('inVote')) {
					self._prepareVote($pollID);
				}
			}
		});
	},
	
	_bindListeners: function(pollID) {
		if (this._canSeeResult[pollID]) {
			this._polls[pollID].find('.jsPollShowResult:eq(0)').attr('pollID', pollID).click($.proxy(this._showResult, this));
		}
		
		if (this._canVote[pollID]) {
			this._polls[pollID].find('.jsPollVote:eq(0)').attr('pollID', pollID).click($.proxy(this._showVote, this));
		}
	},
	
	_showResult: function(event, pollID) {
		var $pollID = (event === null) ? pollID : $(event.currentTarget).data('pollID');
		
		// user cannot see the results yet
		if (!this._canSeeResult[pollID]) {
			return;
		}
		
		// ignore request, we're within results already
		var $inVote = this._polls[$pollID].data('inVote');
		if (!$inVote) {
			return;
		}
		
		if (this._cache[$pollID].result) {
			this._proxy.setOption('data', {
				actionName: 'getResult',
				pollID: $pollID
			});
			this._proxy.sendRequest();
		}
		else {
			// cache current output
			if (!this._cache[$pollID].vote) {
				this._cache[$pollID].vote = this._polls[$pollID].find('.pollInnerContainer').html();
			}
			
			// show results from cache
			this._polls[$pollID].find('.pollInnerContainer').html(this._cache[$pollID].result);
		}
	},
	
	_showVote: function(event) {
		
	},
	
	_success: function(data, textStatus, jqXHR) {
		if (!data || !data.actionName) {
			return;
		}
		
		switch (data.actionName) {
			case 'getResult':
				
			break;
			
			case 'getVote':
				
			break;
			
			case 'vote':
				this._cache[data.returnValues.pollID].result = data.returnValues.resultTemplate;
				
				if (data.returnValues.voteTemplate) {
					this._cache[data.returnValues.pollID].vote = data.returnValues.voteTemplate;
				}
				
				// display results
				this._canSeeResult[data.returnValues.pollID] = true;
				this._showResult(null, data.returnValues.pollID);
			break;
		}
	},
	
	_prepareVote: function(pollID) {
		this._polls[pollID].find('.pollInnerContainer .jsSubmitVote').click($.proxy(this._vote, this));
	},
	
	_vote: function(event) {
		var $pollID = $(event.currentTarget).data('pollID');
		
		// user cannot vote
		if (!this._canVote[$pollID]) {
			return;
		}
		
		// collect values
		$optionIDs = [ ];
		this._polls[$pollID].find('.pollInnerContainer input').each(function(index, input) {
			var $input = $(input);
			if ($input.is(':checked')) {
				$optionIDs.push($input.data('optionID'));
			}
		});
		
		if ($optionsIDs.length) {
			this._proxy.setOption('data', {
				actionName: 'vote',
				optionIDs: $optionIDs,
				pollID: $pollID
			});
			this._proxy.sendRequest();
		}
	}
});
