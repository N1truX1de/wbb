<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseAclEtForumTopic');

/**
 * forum topic acl class
 * 
 * @since  2012-8-10
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAclEtForumTopic extends MbqBaseAclEtForumTopic {
    
    public function __construct() {
    }
    
    /**
     * judge can get topic from the forum
     *
     * @param  Object  $oMbqEtForum
     * @return  Boolean
     */
    public function canAclGetTopic($oMbqEtForum) {
        if ($oMbqEtForum->mbqBind['oDetailedBoardNode'] && $oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard()->canEnter()) {
            return true;
        }
        return false;
    }
    
    /**
     * judge can get thread
     *
     * @param  Object  $oMbqEtForumTopic
     * @return  Boolean
     */
    public function canAclGetThread($oMbqEtForumTopic) {
        //ref wbb\page\ThreadPage::readParameters()
        if ($oMbqEtForumTopic->mbqBind['oViewableThread'] && !$oMbqEtForumTopic->mbqBind['oViewableThread']->movedThreadID && $oMbqEtForumTopic->mbqBind['oViewableThread']->canRead()) {
            return true;
        }
        return false;
    }
    
    /**
     * judge can get_user_topic
     *
     * @return  Boolean
     */
    public function canAclGetUserTopic() {
        if (MbqMain::$oMbqConfig->getCfg('user.guest_okay')->oriValue == MbqBaseFdt::getFdt('MbqFdtConfig.user.guest_okay.range.support')) {
            return true;
        } else {
            return MbqMain::hasLogin();
        }
    }
    
    /**
     * judge can get_unread_topic
     *
     * @return  Boolean
     */
    public function canAclGetUnreadTopic() {
        return MbqMain::hasLogin();
    }
    
    /**
     * judge can get_participated_topic
     *
     * @return  Boolean
     */
    public function canAclGetParticipatedTopic() {
        return MbqMain::hasLogin();
    }
    
    /**
     * judge can get_latest_topic
     *
     * @return  Boolean
     */
    public function canAclGetLatestTopic() {
        if (MbqMain::$oMbqConfig->getCfg('forum.guest_search')->oriValue == MbqBaseFdt::getFdt('MbqFdtConfig.forum.guest_search.range.support')) {
            return true;
        } else {
            return MbqMain::hasLogin();
        }
    }
    
    /**
     * judge can search_topic
     *
     * @return  Boolean
     */
    public function canAclSearchTopic() {
        if (MbqMain::$oMbqConfig->getCfg('forum.guest_search')->oriValue == MbqBaseFdt::getFdt('MbqFdtConfig.forum.guest_search.range.support')) {
            return true;
        } else {
            return MbqMain::hasLogin();
        }
    }
    
    /**
     * judge can get subscribed topic
     *
     * @return  Boolean
     */
    public function canAclGetSubscribedTopic() {
        return MbqMain::hasLogin();
    }
    
    /**
     * judge can new topic
     *
     * @param  Object  $oMbqEtForum
     * @return  Boolean
     */
    public function canAclNewTopic($oMbqEtForum) {
        //ref board.tpl,wbb\form\ThreadAddForm::readParameters()
        if (MbqMain::hasLogin() && $oMbqEtForum->mbqBind['oDetailedBoardNode'] && $oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard()->canStartThread()) {
            $oBoard = $oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard();
            if ($oBoard->isBoard() && !$oBoard->isClosed && $oBoard->getPermission('canViewBoard') && $oBoard->getPermission('canEnterBoard') && $oBoard->getPermission('canStartThread'))
                return true;
        }
        return false;
    }
  
}

?>