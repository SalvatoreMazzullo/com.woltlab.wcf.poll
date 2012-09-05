<?php
namespace wcf\data\poll;
use wcf\data\DatabaseObjectEditor;

/**
 * Extends the poll object with functions to create, update and delete polls.
 * 
 * @author	Alexander Ebert
 * @copyright	2001-2012 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.poll
 * @subpackage	data.poll
 * @category 	Community Framework
 */
class PollEditor extends DatabaseObjectEditor {
	/**
	 * @see	wcf\data\DatabaseObjectEditor::$baseClass
	 */
	protected static $baseClass = 'wcf\data\poll\Poll';
}
