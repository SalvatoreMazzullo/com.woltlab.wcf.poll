<?php
namespace wcf\data\poll;
use wcf\data\AbstractDatabaseObjectAction;

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
}
