<?php
namespace wbb\system\event\listener;
use wbb\data\thread\ViewableThreadList;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
* Listen PostAddForum saved event
 * 
 * @author	Sascha Greuel, Tom Wu
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.tapatalk.wbb4
 * @subpackage	system.event.listener
 */
class PostAddFormTapatalkListener implements IEventListener {
	/**
	* @see \wcf\system\event\IEventListener::execute()
	*/
	public function execute($eventObj, $className, $eventName) {
		$threadID = $eventObj->threadID;
		
		// reply post in more options mode
		if ($threadID) {
			$threadList = new ViewableThreadList();
			$threadList->setObjectIDs(array($threadID));
			$threadList->readObjects();
			$threads = $threadList->getObjects();
			
			// push
			if (!empty($threads[0])) {
				$thread = $threads[0];
				
				if (file_exists(WBB_TAPATALK_DIR . '/push/TapatalkPush.php')) {
					require_once(WBB_TAPATALK_DIR . '/push/TapatalkPush.php');
					$tapatalkPush = new \TapatalkPush();
					
					$tapatalkPush->callMethod('doPushReply', array(
						'oThread' => $thread->getDecoratedObject()
					));
				}
			}
		}
	}
}
