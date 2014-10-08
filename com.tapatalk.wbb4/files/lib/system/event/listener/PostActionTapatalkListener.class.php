<?php
namespace wbb\system\event\listener;
use wbb\data\post\ViewablePostList;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
 * Listen PostAction finalizeAction event
 * 
 * @author	Sascha Greuel, Tom Wu
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.tapatalk.wbb4
 * @subpackage	system.event.listener
 */
class PostActionTapatalkListener implements IEventListener {
	/**
	* @see \wcf\system\event\IEventListener::execute()
	*/
	public function execute($eventObj, $className, $eventName) {
		$method = '';
		$pushData = array();
		
		$controller = $_GET['controller'];
		$parameters = $eventObj->getParameters();
		$returnValues = $eventObj->getReturnValues();
		$actionName = $eventObj->getActionName();
		
		if ($actionName == 'triggerPublication' && !empty($returnValues['objectIDs'][0])) {
			if ($controller == 'AJAXProxy') {
				// quick reply post
				$postList = new ViewablePostList();
				$postList->setObjectIDs(array($returnValues['objectIDs'][0]));
				$postList->readObjects();
				$posts = $postList->getObjects();
				
				if ($posts) {
					$post = array_shift($posts);
					
					$method = 'doPushReply';
					$pushData = array(
						'oPost' => $post->getDecoratedObject()
					);
				}
			}
			else if ($controller == 'ThreadAdd' && (!isset($_POST['type']) || $_POST['type'] != 2)) {
				$objects = $eventObj->getObjects();
				$data = $objects[0];
				
				// new topic
				$method = 'doPushNewTopic';
				$pushData = array(
					'postId' => $returnValues['objectIDs'][0],
					'boardId' => $data->getThread()->boardID
				);
			}
		}
		
		// push
		if (!empty($method) && !empty($pushData)) {
			if (file_exists(WBB_TAPATALK_DIR . '/push/TapatalkPush.php')) {
				require_once(WBB_TAPATALK_DIR . '/push/TapatalkPush.php');
				$tapatalkPush = new \TapatalkPush();
				
				$tapatalkPush->callMethod($method, $pushData);
			}
		}
	}
}
