<?php
namespace wcf\system\poll;
use wcf\data\poll\Poll;

/**
 * Provides methods to create and manage polls.
 *
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	system.poll
 * @category 	Community Framework
 */
interface IPollHandler {
	/**
	 * Returns true, if current user may vote.
	 * 
	 * @return	boolean
	 */
	public function canVote();
	
	/**
	 * Validates if given poll object is accessible for current user.
	 * 
	 * @param	wcf\data\poll\Poll	$poll
	 */
	public function validate(Poll $poll);
}
