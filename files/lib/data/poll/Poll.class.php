<?php
namespace wcf\data\poll;
use wcf\data\poll\option\PollOptionList;
use wcf\data\DatabaseObject;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\WCF;

/**
 * Represents a poll.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	data.poll
 * @category 	Community Framework
 */
class Poll extends DatabaseObject {
	/**
	 * @see	wcf\data\DatabaseObject::$databaseTableName
	 */
	protected static $databaseTableName = 'poll';
	
	/**
	 * @see	wcf\data\DatabaseObject::$databaseIndexName
	 */
	protected static $databaseTableIndexName = 'pollID';
	
	/**
	 * participation status
	 * @var	boolean
	 */
	protected $isParticipant = false;
	
	/**
	 * list of poll options
	 * @var	array<wcf\data\poll\option\PollOption>
	 */
	protected $options = array();
	
	/**
	 * Returns a list of poll options.
	 * 
	 * @return	array<wcf\data\poll\option\PollOption>
	 */
	public function getOptions() {
		$this->loadOptions();
		
		return $this->options;
	}
	
	/**
	 * Returns true, if current user is a participant.
	 * 
	 * @return	boolean
	 */
	public function isParticipant() {
		$this->loadOptions();
		
		return $this->isParticipant;
	}
	
	/**
	 * Loads associated options.
	 */
	protected function loadOptions() {
		if (!empty($this->options)) {
			return;
		}
		
		$optionList = new PollOptionList();
		$optionList->getConditionBuilder()->add("poll_option.pollID = ?", array($this->pollID));
		$optionList->sqlLimit = 0;
		$optionList->readObjects();
		$this->options = $optionList->getObjects();
			
		// read participation state
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("optionID IN (?)", array(array_keys($this->options)));
		$conditions->add("userID = ?", array(WCF::getUser()->userID));
			
		$sql = "SELECT	optionID
			FROM	wcf".WCF_N."_poll_option_vote
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditions->getParameters());
		while ($row = $statement->fetchArray()) {
			$this->isParticipant = true;
			
			$this->options[$row['optionID']]->selected = true;
		}
	}
	
	/**
	 * Returns true, if poll is already finished.
	 * 
	 * @return	boolean
	 */
	public function isFinished() {
		if ($this->endTime >= TIME_NOW) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Returns true, if current user can vote.
	 * 
	 * @return	boolean
	 */
	public function canVote() {
		// guest voting is not possible
		if (!WCF::getUser()->userID) {
			return false;
		}
		else if ($this->isFinished()) {
			return false;
		}
		else if ($this->isParticipant() && !$this->isChangeable) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Returns true, if current user can see the result.
	 * 
	 * @return	boolean
	 */
	public function canSeeResult() {
		if ($this->isFinished() || $this->isParticipant() || !$this->resultsRequireVote) {
			return true;
		}
		
		return false;
	}
}
