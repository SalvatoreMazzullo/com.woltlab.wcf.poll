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
		new WCF.Sortable.Simple(containerID, '', undefined, undefined, true);
		
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