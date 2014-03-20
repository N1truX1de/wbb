<?php
namespace wcf\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
* Listener post quick reply
*/
class TapatalkForPostQuickReplyListener implements IEventListener {
    /**
    * @see \wcf\system\event\IEventListener::execute()
    */
    public function execute($eventObj, $className, $eventName) {
        error_log("$className, $eventName");
        error_log(print_r($eventObj->getActionName(), true));
        error_log(print_r($eventObj->getParameters(), true));
        error_log(print_r($eventObj->getReturnValues(), true));
    }

}

?>