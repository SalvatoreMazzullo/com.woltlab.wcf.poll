<?php
namespace wcf\data\poll;
use wcf\data\poll\PollEditor;
use wcf\data\poll\option\PollOptionList;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes poll-related actions.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	data.poll
 * @category 	Community Framework
 */
class PollAction extends AbstractDatabaseObjectAction {
	/**
	 * @see wcf\data\AbstractDatabaseObjectAction::$className
	 */
	protected $className = 'wcf\data\poll\PollEditor';
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::create()
	 */
	public function create() {
		if (!isset($this->parameters['data']['time'])) $this->parameters['data']['time'] = TIME_NOW;
		
		// create poll
		$poll = parent::create();
		
		// create options
		$sql = "INSERT INTO	wcf".WCF_N."_poll_option
					(pollID, optionValue, showOrder)
			VALUES		(?, ?, ?)";
		$statement = WCF::getDB()->prepareStatement($sql);
		
		WCF::getDB()->beginTransaction();
		foreach ($this->parameters['options'] as $showOrder => $option) {
			$statement->execute(array(
				$poll->pollID,
				$option['optionValue'],
				$showOrder
			));
		}
		WCF::getDB()->commitTransaction();
		
		return $poll;
	}
	
	/**
	 * @see	wcf\data\AbstractDatabaseObjectAction::update()
	 */
	public function update() {
		parent::update();
		
		// read current poll
		$poll = current($this->objects);
		
		// get current options
		$optionList = new PollOptionList();
		$optionList->getConditionBuilder()->add("poll_option.pollID = ?", array($poll->pollID));
		$optionList->sqlLimit = 0;
		$optionList->sqlOrderBy = "poll_option.showOrder ASC";
		$optionList->readObjects();
		$options = $optionList->getObjects();
		
		$newOptions = $updateOptions = array();
		foreach ($this->parameters['options'] as $showOrder => $option) {
			// check if editing an existing option
			if ($option['optionID']) {
				// check if an update is required
				if ($options[$option['optionID']]->showOrder != $showOrder || $options[$option['optionID']]->optionValue != $option['optionValue']) {
					$updateOptions[$option['optionID']] = array(
						'optionValue' => $option['optionValue'],
						'showOrder' => $showOrder
					);
				}
				
				// remove option
				unset($options[$option['optionID']]);
			}
			else {
				$newOptions[] = array(
					'optionValue' => $option['optionValue'],
					'showOrder' => $showOrder
				);
			}
		}
		
		if (!empty($newOptions) || !empty($updateOptions) || !empty($options)) {
			WCF::getDB()->beginTransaction();
			
			// check if new options should be created
			if (!empty($newOptions)) {
				$sql = "INSERT INTO	wcf".WCF_N."_poll_option
							(pollID, optionValue, showOrder)
					VALUES		(?, ?, ?)";
				$statement = WCF::getDB()->prepareStatement($sql);
				foreach ($newOptions as $option) {
					$statement->execute(array(
						$poll->pollID,
						$option['optionValue'],
						$option['showOrder']
					));
				}
			}
			
			// check if existing options should be updated
			if (!empty($updateOptions)) {
				$sql = "UPDATE	wcf".WCF_N."_poll_option
					SET	optionValue = ?
						AND showOrder = ?
					WHERE	optionID = ?";
				$statement = WCF::getDB()->prepareStatement($sql);
				foreach ($updateOptions as $optionID => $option) {
					$statement->execute(array(
						$option['optionValue'],
						$option['showOrder'],
						$optionID
					));
				}
			}
			
			// check if options should be removed
			if (!empty($options)) {
				$sql = "DELETE FROM	wcf".WCF_N."_poll_option
					WHERE		optionID = ?";
				$statement = WCF::getDB()->prepareStatement($sql);
				foreach ($options as $option) {
					$statement->execute(array($option->optionID));
				}
			}
			
			// force recalculation of poll stats
			$pollEditor = new PollEditor($poll);
			$pollEditor->calculateVotes();
			
			WCF::getDB()->commitTransaction();
		}
	}
	
	/**
	 * Executes a user's vote.
	 */
	public function vote() {
		$poll = current($this->objects);
		
		// get previous vote
		$sql = "SELECT	optionID
			FROM	wcf".WCF_N."_poll_option_vote
			WHERE	pollID = ?
				AND userID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array(
			$poll->pollID,
			WCF::getUser()->userID
		));
		$alreadyVoted = false;
		$optionIDs = array();
		while ($row = $statement->fetchArray()) {
			$alreadyVoted = true;
			$optionIDs[] = $row['optionID'];
		}
		
		// calculate the difference
		foreach ($this->parameters['optionIDs'] as $index => $optionID) {
			$optionsIndex = array_search($optionID, $optionIDs);
			if ($optionsIndex !== false) {
				// ignore this option
				unset($this->parameters['optionIDs'][$index]);
				unset($optionIDs[$optionsIndex]);
			}
		}
		
		// insert new vote options
		if (!empty($this->parameters['optionIDs'])) {
			$sql = "INSERT INTO	wcf".WCF_N."_poll_option_vote
						(pollID, optionID, userID)
				VALUES		(?, ?, ?)";
			$statement = WCF::getDB()->prepareStatement($sql);
			foreach ($this->parameters['optionIDs'] as $optionID) {
				$statement->execute(array(
					$poll->pollID,
					$optionID,
					WCF::getUser()->userID
				));
			}
			
			// increase votes per option
			$sql = "UPDATE	wcf".WCF_N."_poll_option
				SET	votes = votes + 1
				WHERE	optionID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			foreach ($this->parameters['optionIDs'] as $optionID) {
				$statement->execute(array($optionID));
			}
		}
		
		// remove previous options
		if (!empty($optionIDs)) {
			$sql = "DELETE FROM	wcf".WCF_N."_poll_option_vote
				WHERE		optionID = ?
						AND userID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			foreach ($optionIDs as $optionID) {
				$statement->execute(array(
					$optionID,
					WCF::getUser()->userID
				));
			}
			
			// decrease votes per option
			$sql = "UPDATE	wcf".WCF_N."_poll_option
				SET	votes = votes - 1
				WHERE	optionID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			foreach ($optionIDs as $optionID) {
				$statement->execute(array($optionID));
			}
		}
		
		// increase poll votes
		if (!$alreadyVoted) {
			$poll->increaseVotes();
		}
	}
}
