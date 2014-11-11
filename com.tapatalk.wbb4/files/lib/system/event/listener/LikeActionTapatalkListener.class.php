<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;

/**
 * Listen PostAction finalizeAction event
 * 
 * @author	Sascha Greuel, keweiliu
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.tapatalk.wbb4
 * @subpackage	system.event.listener
 */
class LikeActionTapatalkListener implements IEventListener{
	/**
	 * @see \wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		$method = '';
		$pushData = array();
		
		$action = $eventObj->getActionName();
		$parameters = $eventObj->getParameters();
		$returnValues = $eventObj->getReturnValues();
		
		if ($action == 'like' && isset($returnValues['returnValues']['isLiked']) && $returnValues['returnValues']['isLiked'] == 1 && !empty($parameters['data']['objectID'])) {
			// like post
			$method = 'doPushLikePost';
			$pushData = array(
				'postId' => $parameters['data']['objectID']
			);
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
