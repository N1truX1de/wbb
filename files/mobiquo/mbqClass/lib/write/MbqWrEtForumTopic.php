<?php

use wbb\page\ThreadPage;
use wbb\data\thread\ThreadEditor;
use wbb\data\thread\ThreadAction;
use wbb\data\post\ThreadPostList;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtForumTopic');

/**
 * forum topic write class
 * 
 * @since  2012-8-15
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtForumTopic extends MbqBaseWrEtForumTopic {
    
    public function __construct() {
    }
    
    /**
     * add forum topic view num
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function addForumTopicViewNum(&$var) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //ref wbb\page\ThreadPage::readData()
            // update view count
    		$threadEditor = new ThreadEditor($var->mbqBind['oViewableThread']->getDecoratedObject());
    		$threadEditor->updateCounters(array(
    			'views' => 1
    		));
        }
    }
    
    /**
     * mark forum topic read
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'markAllAsRead' means mark all my unread topics as read
     */
    public function markForumTopicRead(&$var = NULL, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'markAllAsRead') {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            if (is_array($var)) {
                MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
            } else {
                //ref wbb\page\ThreadPage::readData()
                $oThreadPostList = new ThreadPostList($var->mbqBind['oViewableThread']->getDecoratedObject());  
                $oThreadPostList->sqlOffset = 0;
                $oThreadPostList->sqlLimit = 9;
                $oThreadPostList->readObjects();    //only for making $oThreadPostList used in following code
                // update thread visit
        		if ($var->mbqBind['oViewableThread']->isNew() && $oThreadPostList->getMaxPostTime() > $var->mbqBind['oViewableThread']->getVisitTime()) {
        			$threadAction = new ThreadAction(array($var->mbqBind['oViewableThread']->getDecoratedObject()), 'markAsRead', array(
        				'visitTime' => $oThreadPostList->getMaxPostTime(),
        				'viewableThread' => $var->mbqBind['oViewableThread']
        			));
        			$threadAction->executeAction();
        		}
            }
        }
    }
    
    /**
     * reset forum topic subscription
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function resetForumTopicSubscription(&$var) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //do nothing
        }
    }
  
}

?>