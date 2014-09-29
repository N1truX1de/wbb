<?php
namespace wbb\system\event\listener;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
 * Listen ConversationMessageAction finalizeAction event
 * 
 * @author	Sascha Greuel, Tom Wu
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.tapatalk.wbb4
 * @subpackage	system.event.listener
 */
class ConversationMessageActionTapatalkListener implements IEventListener {
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
		
		if ($controller == 'ConversationAdd' && $actionName == 'create') {
			// new conversation
			if (!empty($parameters['conversation']) && $parameters['conversation']->conversationID && $parameters['conversation']->subject) {
				$method = 'doPushNewConversation';
				$pushData = array(
					'convId' => $parameters['conversation']->conversationID,
					'title' => $parameters['conversation']->subject
				);
			}
		}
		else if ($controller == 'ConversationMessageAdd' && $actionName == 'create') {
			// extended reply
			if (!empty($returnValues['returnValues']) && $returnValues['returnValues']->messageID && $returnValues['returnValues']->conversationID) {
				$method = 'doPushReplyConversation';
				$pushData = array(
					'convId' => $returnValues['returnValues']->conversationID,
					'msgId' => $returnValues['returnValues']->messageID
				);
			}
		}
		else if ($controller == 'AJAXProxy' && $actionName == 'quickReply') {
			// quick reply
			if (!empty($parameters['data']['conversationID']) && $parameters['data']['conversationID']) {
				$latestMessageID = $this->getLatestMsgID($parameters['data']['conversationID']);
				
				if ($latestMessageID) {
					$method = 'doPushReplyConversation';
					$pushData = array(
						'convId' => $parameters['data']['conversationID'],
						'msgId' => $latestMessageID
					);
				}
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
	
	private function getLatestMsgID($conversationID) {
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('msg.conversationID = ?', array($conversationID));
		$sql = "SELECT	MAX(msg.messageID) AS messageID
			FROM	wcf".WCF_N."_conversation_message AS msg
			".$conditionBuilder;
		$statement = WCF::getDB()->prepareStatement($sql, 1);
		$statement->execute($conditionBuilder->getParameters());
		$row = $statement->fetchArray();
		
		if (!empty($row)) {
			return intval($row['messageID']);
		}
		
		return 0;
	}
}
