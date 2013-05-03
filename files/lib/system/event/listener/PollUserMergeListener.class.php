<?php
namespace wcf\system\event\listener;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
 * Merges user votes.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	system.event.listener
 * @category	Community Framework
 */
class PollUserMergeListener implements IEventListener {
	/**
	 * @see	wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		// poll_option_vote
		$conditions = new PreparedStatementConditionBuilder();
		$conditions->add("userID IN (?)", array($eventObj->mergedUserIDs));
		$sql = "UPDATE IGNORE	wcf".WCF_N."_poll_option_vote
			SET		userID = ?
			".$conditions;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute(array_merge(array($eventObj->destinationUserID), $conditions->getParameters()));
	}
}
