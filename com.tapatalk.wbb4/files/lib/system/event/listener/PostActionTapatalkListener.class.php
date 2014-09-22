<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\system\WCF;
use wbb\data\post\ViewablePostList;

/**
* listen PostAction finalizeAction event
* refer PostAction::quickReply() -> PostAction::create() -> trigger publication
*/
class PostActionTapatalkListener implements IEventListener {
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
        
        if (isset($_GET['controller']) && $_GET['controller'] == 'AJAXProxy' && $_POST['actionName'] == 'quickReply' && $className == 'wbb\data\post\PostAction' && $eventName == 'finalizeAction') {
            //quick reply post
            $ret = $eventObj->getReturnValues();
            if ($ret['actionName'] == 'triggerPublication' && $ret['objectIDs'] && ($postId = array_shift($ret['objectIDs']))) {
                $oViewablePostList = new ViewablePostList();
        		$oViewablePostList->setObjectIDs(array($postId));
        		$oViewablePostList->readObjects();
                $objsViewablePost = $oViewablePostList->getObjects();
                if ($objsViewablePost) {
                    $oViewablePost = array_shift($objsViewablePost);
                    $oPost = $oViewablePost->getDecoratedObject();
                    $pushPath = 'mobiquo/push/TapatalkPush.php';
                    require_once($pushPath);
                    $oTapatalkPush = new \TapatalkPush();   //!!!
                    $oTapatalkPush->callMethod('doPushReply', array(
                        'oPost' => $oPost
                    ));
                }
            }
        } elseif (isset($_GET['controller']) && $_GET['controller'] == 'ThreadAdd' && $className == 'wbb\data\post\PostAction' && $eventName == 'finalizeAction' && $eventObj->getActionName() == 'triggerPublication' && isset($_GET['id']) && $_GET['id'] && isset($_POST['type']) && ($_POST['type'] == 0 || $_POST['type'] == 1)) {
            //new topic
            $p = $eventObj->getParameters();
            $ret = $eventObj->getReturnValues();
            if (isset($ret['objectIDs']) && $ret['objectIDs'] && $ret['objectIDs'][0]) {
                $postId = $ret['objectIDs'][0];
                $pushPath = 'mobiquo/push/TapatalkPush.php';
                require_once($pushPath);
                $oTapatalkPush = new \TapatalkPush();   //!!!
                $oTapatalkPush->callMethod('doPushNewTopic', array(
                    'postId' => $postId
                ));
            }
        }
    }

}

?>