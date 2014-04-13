<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\system\WCF;
use wcf\system\database\util\PreparedStatementConditionBuilder;

/**
* listen ConversationMessageAction finalizeAction event
*/
class ConversationMessageActionTapatalkListener implements IEventListener {
    /**
    * @see \wcf\system\event\IEventListener::execute()
    */
    public function execute($eventObj, $className, $eventName) {
        //error_log(print_r($_GET, true));
        //error_log(print_r($_POST, true));
        //error_log("$className, $eventName");
        //error_log(print_r($eventObj, true));
        //error_log(print_r($eventObj->getActionName(), true));
        //error_log(print_r($eventObj->getParameters(), true));
        //error_log(print_r($eventObj->getReturnValues(), true));
        
        if (isset($_GET['controller']) && $_GET['controller'] == 'ConversationAdd' && $className == 'wcf\data\conversation\message\ConversationMessageAction' && $eventName == 'finalizeAction' && $eventObj->getActionName() == 'create') {
            //new conversation
            $p = $eventObj->getParameters();
            $ret = $eventObj->getReturnValues();
            if (isset($p['conversation']) && $p['conversation']->conversationID) {
                $title = $p['conversation']->subject;
                $pushPath = 'mobiquo/push/TapatalkPush.php';
                require_once($pushPath);
                $oTapatalkPush = new \TapatalkPush();   //!!!
                $oTapatalkPush->callMethod('doPushNewConversation', array(
                    'convId' => $p['conversation']->conversationID,
                    'title' => $title
                ));
                
            }
        } elseif (isset($_GET['controller']) && $_GET['controller'] == 'AJAXProxy' && $className == 'wcf\data\conversation\message\ConversationMessageAction' && $eventName == 'finalizeAction' && $eventObj->getActionName() == 'quickReply') {
            //quick reply conversation
            $p = $eventObj->getParameters();
            $ret = $eventObj->getReturnValues();
            if (isset($p['data']) && $p['data']['conversationID']) {
                $pushPath = 'mobiquo/push/TapatalkPush.php';
                require_once($pushPath);
                $oTapatalkPush = new \TapatalkPush();   //!!!
                $oTapatalkPush->callMethod('doPushReplyConversation', array(
                    'convId' => $p['data']['conversationID'],
                    'msgId' => $this->getLatestMsgId($p['data']['conversationID'])
                ));
            }
        } elseif (isset($_GET['controller']) && $_GET['controller'] == 'ConversationMessageAdd' && $className == 'wcf\data\conversation\message\ConversationMessageAction' && $eventName == 'finalizeAction' && $eventObj->getActionName() == 'create') {
            //more options reply conversation
            $p = $eventObj->getParameters();
            $ret = $eventObj->getReturnValues();
            if (isset($ret['returnValues']) && $ret['returnValues']->messageID && $ret['returnValues']->conversationID) {
                $pushPath = 'mobiquo/push/TapatalkPush.php';
                require_once($pushPath);
                $oTapatalkPush = new \TapatalkPush();   //!!!
                $oTapatalkPush->callMethod('doPushReplyConversation', array(
                    'convId' => $ret['returnValues']->conversationID,
                    'msgId' => $ret['returnValues']->messageID
                ));
            }
        }
    }
    
    private function getLatestMsgId($convId) {
		$conditionBuilder = new PreparedStatementConditionBuilder();
		$conditionBuilder->add('msg.conversationID = ?', array($convId));
		$sql = "SELECT MAX(msg.messageID) AS messageID FROM wcf".WCF_N."_conversation_message AS msg ".$conditionBuilder;
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		while ($row = $statement->fetchArray()) {
			return $row['messageID'];
		}
    }

}

?>