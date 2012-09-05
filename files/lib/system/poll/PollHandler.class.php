<?php
namespace wcf\system\poll;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\SingletonFactory;

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
class PollHandler extends SingletonFactory {
	/**
	 * list of object types
	 * @var	array<wcf\data\object\type\ObjectType>
	 */
	protected $cache = array();
	
	/**
	 * @see	wcf\system\SingletonFactory::init()
	 */
	protected function init() {
		$objectTypes = ObjectTypeCache::getInstance()->getObjectTypes('com.woltlab.wcf.poll');
		foreach ($objectTypes as $objectType) {
			$this->cache[$objectType->objectType] = $objectType;
		}
	}
}
