<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;

class LikeablePostTapatalkListener implements IEventListener{
    /**
    * @see \wcf\system\event\IEventListener::execute()
    */
    public function execute($eventObj, $className, $eventName) {
//        error_log(print_r($_GET, true));
//        error_log(print_r($_POST, true));
//        error_log("$className, $eventName");
//        error_log(print_r($eventObj, true));
//        error_log(print_r($eventObj->getActionName(), true));
//        error_log(print_r($eventObj->getParameters(), true));
//        error_log(print_r($eventObj->getReturnValues(), true));

        $action = $eventObj->getActionName();
        $getParameters = $eventObj->getParameters();
        $returnValues = $eventObj->getReturnValues();
        if ($action == 'like' && isset($returnValues['returnValues']['isLiked']) && $returnValues['returnValues']['isLiked'] == 1){
            // like post
            $method = 'doPushLikePost';
            $pushData = array(
                'postId' => $getParameters['data']['objectID'],
            );
        }

        // push
        if (isset($method) && !empty($method) && !empty($pushData)) {
            if (file_exists(WBB_TAPATALK_DIR . '/push/TapatalkPush.php')) {
                require_once(WBB_TAPATALK_DIR . '/push/TapatalkPush.php');
                $tapatalkPush = new \TapatalkPush();
                $tapatalkPush->callMethod($method, $pushData);
            }
        }
    }
    }
}