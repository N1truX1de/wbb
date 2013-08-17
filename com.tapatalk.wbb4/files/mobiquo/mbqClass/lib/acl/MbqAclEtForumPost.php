<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseAclEtForumPost');

/**
 * forum post acl class
 * 
 * @since  2012-8-20
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAclEtForumPost extends MbqBaseAclEtForumPost {
    
    public function __construct() {
    }
    
    /**
     * judge can get_user_reply_post
     *
     * @return  Boolean
     */
    public function canAclGetUserReplyPost() {
        if (MbqMain::$oMbqConfig->getCfg('user.guest_okay')->oriValue == MbqBaseFdt::getFdt('MbqFdtConfig.user.guest_okay.range.support')) {
            return true;
        } else {
            return MbqMain::hasLogin();
        }
    }
    
    /**
     * judge can search_post
     *
     * @return  Boolean
     */
    public function canAclSearchPost() {
        if (MbqMain::$oMbqConfig->getCfg('forum.guest_search')->oriValue == MbqBaseFdt::getFdt('MbqFdtConfig.forum.guest_search.range.support')) {
            return true;
        } else {
            return MbqMain::hasLogin();
        }
    }
    
    /**
     * judge can reply post
     *
     * @param  Object  $oMbqEtForumTopic
     * @return  Boolean
     */
    public function canAclReplyPost($oMbqEtForumTopic) {
        //ref thread.tpl,wbb\form\PostAddForm
        if (MbqMain::hasLogin() && $oMbqEtForumTopic->mbqBind['oViewableThread'] && $oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject()->canReply()) {
            $oThread = $oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject();
            if ($oMbqEtForumTopic->oMbqEtForum && $oMbqEtForumTopic->oMbqEtForum->mbqBind['oDetailedBoardNode']) {
                $oBoard = $oMbqEtForumTopic->oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard();
                if ($oBoard->getPermission('canViewBoard') && $oBoard->getPermission('canEnterBoard'))
                    return true;
            }
        }
        return false;
    }
    
    /**
     * judge can get quote post
     *
     * @param  Object  $oMbqEtForumPost
     * @return  Boolean
     */
    public function canAclGetQuotePost($oMbqEtForumPost) {
        return $this->canAclReplyPost($oMbqEtForumPost->oMbqEtForumTopic);
    }
    
    /**
     * judge can get_raw_post
     *
     * @param  Object  $oMbqEtForumPost
     * @return  Boolean
     */
    public function canAclGetRawPost($oMbqEtForumPost) {
        return $this->canAclSaveRawPost($oMbqEtForumPost);
    }
    
    /**
     * judge can save_raw_post
     *
     * @param  Object  $oMbqEtForumPost
     * @return  Boolean
     */
    public function canAclSaveRawPost($oMbqEtForumPost) {
        //ref threadPostList.tpl,wbb\form\PostEditForm
        if (MbqMain::hasLogin() && $oMbqEtForumPost->oMbqEtForumTopic && $oMbqEtForumPost->mbqBind['oViewablePost'] && $oMbqEtForumPost->oMbqEtForumTopic->mbqBind['oViewableThread']) {
            $oThread = $oMbqEtForumPost->oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject();
            $oPost = $oMbqEtForumPost->mbqBind['oViewablePost']->getDecoratedObject();
            if ($oThread->canEditPost($oPost)) {
                return true;
            }
        }
        return false;
    }
  
}

?>