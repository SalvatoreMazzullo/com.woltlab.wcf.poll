<?php
namespace wcf\action;
use wcf\data\poll\Poll;
use wcf\data\poll\PollAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\SystemException;
use wcf\system\exception\UserInputException;
use wcf\system\poll\PollManager;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\JSON;
use wcf\util\StringUtil;

/**
 * Handles poll interaction.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	action
 * @category 	Community Framework
 */
class PollAction extends AJAXProxyAction {
	/**
	 * list of option ids
	 * @var	array<integer>
	 */
	public $optionIDs = array();
	
	/**
	 * poll object
	 * @var	wcf\data\poll\Poll
	 */
	public $poll = null;
	
	/**
	 * poll id
	 * @var	integer
	 */
	public $pollID = 0;
	
	/**
	 * @see	wcf\action\IAction::readParameters()
	 */
	public function readParameters() {
		AbstractSecureAction::readParameters();
		
		if (isset($_POST['actionName'])) $this->actionName = StringUtil::trim($_POST['actionName']);
		if (isset($_POST['pollID'])) $this->pollID = intval($_POST['pollID']);
		
		$this->poll = new Poll($this->pollID);
		if (!$this->poll->pollID) {
			throw new UserInputException('pollID');
		}
		
		PollManager::getInstance()->validatePermissions($this->poll);
		
		if (isset($_POST['optionIDs']) && is_array($_POST['optionIDs'])) {
			$this->optionIDs = ArrayUtil::toIntegerArray($_POST['optionIDs']);
			if (count($this->optionIDs) > $this->poll->maxVotes) {
				throw new PermissionDeniedException();
			}
			
			// validate option ids
			$sql = "SELECT	optionID
				FROM	wcf".WCF_N."_poll_option
				WHERE	pollID = ?";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute(array($this->poll->pollID));
			$optionIDs = array();
			while ($row = $statement->fetchArray()) {
				$optionIDs[] = $row;
			}
			
			foreach ($this->optionIDs as $optionID) {
				if (!in_array($optionID, $optionIDs)) {
					throw new PermissionDeniedException();
				}
			}
		}
	}
	
	/**
	 * @see	wcf\action\IAction::execute()
	 */
	public function execute() {
		AbstractAction::execute();
		
		$returnValues = array(
			'actionName' => $this->actionName,
			'pollID' => $this->pollID
		);
		
		switch ($this->actionName) {
			case 'getResult':
				$this->getResult($returnValues);
			break;
			
			case 'getVote':
				$this->getVote($returnValues);
			break;
			
			case 'vote':
				$this->vote($returnValues);
			break;
			
			default:
				throw new SystemException("Unknown action '".$this->actionName."'");
			break;
		}
		
		$this->executed();
		
		// send JSON-encoded response
		header('Content-type: application/json');
		echo JSON::encode($returnValues);
		exit;
	}
	
	/**
	 * Renders the result template.
	 */
	public function getResult(array &$returnValues) {
		WCF::getTPL()->assign(array(
			'poll' => $this->poll
		));
		
		$returnValues['resultTemplate'] = WCF::getTPL()->fetch('pollResult');
	}
	
	/**
	 * Renders the vote template.
	 */
	public function getVote(array &$returnValues) {
		WCF::getTPL()->assign(array(
			'poll' => $this->poll
		));
		
		$returnValues['voteTemplate'] = WCF::getTPL()->fetch('pollVote');
	}
	
	/**
	 * Adds a user vote.
	 * 
	 * @param	array<mixed>	$returnValues
	 */
	protected function vote(array &$returnValues) {
		$pollAction = new PollAction(array($this->poll), 'vote', array('optionIDs' => $this->optionIDs));
		$pollAction->executeAction();
		
		// render result template
		$this->getResult($returnValues);
		
		// render vote template if votes are changeable
		if ($this->poll->isChangeable) {
			$this->getVote($returnValues);
		}
	}
}
