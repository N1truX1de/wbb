<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\system\WCF;
use wbb\data\thread\ViewableThreadList;

/**
* listen PostAddForum saved event
* refer AbstractForm::saved()
*/
class PostAddFormTapatalkListener implements IEventListener {
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
        if ($_GET['controller'] == 'PostAdd' && $className == 'wbb\form\PostAddForm' && $eventName == 'saved') {
            //reply post in more options mode
            if ($eventObj->threadID) {
                $oViewableThreadList = new ViewableThreadList();
        		$oViewableThreadList->setObjectIDs(array($eventObj->threadID));
        		$oViewableThreadList->readObjects();
        		$objsViewableThread = $oViewableThreadList->getObjects();
        		if ($objsViewableThread) {
        		    $oViewableThread = array_shift($objsViewableThread);
        		    $oThread = $oViewableThread->getDecoratedObject();
                    $pushPath = 'mobiquo/push/TapatalkPush.php';
                    require_once($pushPath);
                    $oTapatalkPush = new \TapatalkPush();   //!!!
                    $oTapatalkPush->callMethod('doPushReply', array(
                        'oThread' => $oThread
                    ));
        	    }
            }
        }
    }

}

?>