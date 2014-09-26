<?php

use wbb\page\BoardPage;
use wbb\data\board\BoardCache;
use wbb\data\board\BoardEditor;
use wbb\data\board\BoardTagCloud;
use wbb\data\board\DetailedBoardNodeList;
use wbb\data\thread\BoardThreadList;
use wbb\system\WBBCore;
use wcf\data\user\online\UsersOnlineList;
use wcf\data\user\UserProfile;
use wcf\page\SortablePage;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\dashboard\DashboardHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\label\LabelHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\user\collapsible\content\UserCollapsibleContentHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;
use wbb\data\thread\ViewableThreadList;
use wbb\data\post\ThreadPostList;

use wbb\data\thread\WatchedThreadList;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtForumTopic');

/**
 * forum topic read class
 * 
 * @since  2012-8-8
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtForumTopic extends MbqBaseRdEtForumTopic {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtForumTopic = null, $pName = null, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get forum topic objs
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byForum' means get data by forum obj.$var is the forum obj.
     * $mbqOpt['case'] = 'byObjsViewableThread' means get data by objsViewableThread.$var is the objsViewableThread.
     * $mbqOpt['case'] = 'byTopicIds' means get data by topic ids.$var is the ids.
     * $mbqOpt['case'] = 'byAuthor' means get data by author.$var is the MbqEtUser obj.
     * $mbqOpt['case'] = 'subscribed' means get subscribed data.$var is the user id.
     * $mbqOpt['top'] = true means get sticky data.
     * $mbqOpt['notIncludeTop'] = true means get not sticky data.
     * @return  Mixed
     */
    public function getObjsMbqEtForumTopic($var = null, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'byForum') {
            $oMbqEtForum = $var;
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                //ref wbb\page\BoardPage::initObjectList()
                $oBoardThreadList = new BoardThreadList($oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard(), 1000000);  //!!!
                $oBoardThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oBoardThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                if ($mbqOpt['notIncludeTop']) {
                    $oBoardThreadList->getConditionBuilder()->add('thread.isSticky = 0');
                } elseif ($mbqOpt['top']) {
                    $oBoardThreadList->getConditionBuilder()->add('thread.isSticky = 1');
                } else {
                    MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
                }
                $oBoardThreadList->readObjects();
                $oMbqDataPage->totalNum = $oBoardThreadList->countObjects();    //not include the ViewableThread
                $objsViewableThread = array();
                foreach ($oBoardThreadList->getObjects() as $oViewableThread) { 
                    if (!$oViewableThread->getDecoratedObject()->isAnnouncement) {  //filter the announcement ViewableThread
                        $objsViewableThread[] = $oViewableThread;
                    }
                }
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableThread';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                $mbqOpt['originCase'] = 'byForum';
                return $this->getObjsMbqEtForumTopic($objsViewableThread, $mbqOpt);
                /* common end */
            }
        } elseif ($mbqOpt['case'] == 'subscribed') {
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oWatchedThreadList = new WatchedThreadList();
                $oWatchedThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oWatchedThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oWatchedThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oWatchedThreadList->readObjectIDs();
                $oMbqDataPage->totalNum = $oWatchedThreadList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byTopicIds';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $this->getObjsMbqEtForumTopic($oWatchedThreadList->objectIDs, $mbqOpt);
                /* common end */
            }
        } elseif ($mbqOpt['case'] == 'byAuthor') {
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oViewableThreadList = new ViewableThreadList();
                $oViewableThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oViewableThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oViewableThreadList->getConditionBuilder()->add('thread.boardID IN (?)', array(MbqMain::$oMbqAppEnv->accessibleBoardIds));
                $oViewableThreadList->getConditionBuilder()->add('thread.userID = ?', array($var->userId->oriValue));   //!!!
                $oViewableThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oViewableThreadList->readObjects();
                $oMbqDataPage->totalNum = $oViewableThreadList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableThread';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $this->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
                /* common end */
            }
        } elseif ($mbqOpt['case'] == 'byTopicIds') {
            //ref wbb\data\thread\ViewableThread::getThread()
    		$oViewableThreadList = new ViewableThreadList();
    		$oViewableThreadList->setObjectIDs($var);
    		$oViewableThreadList->readObjects();
            /* common begin */
            $mbqOpt['case'] = 'byObjsViewableThread';
            return $this->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
            /* common end */
        } elseif ($mbqOpt['case'] == 'byObjsViewableThread') {
            $objsViewableThread = $var;
            /* common begin */
            $objsMbqEtForumTopic = array();
            $authorUserIds = array();
            $lastReplyUserIds = array();
            $forumIds = array();
            $topicIds = array();
            foreach ($objsViewableThread as $oViewableThread) {
                $objsMbqEtForumTopic[] = $this->initOMbqEtForumTopic($oViewableThread, array('case' => 'oViewableThread'));
            }
            foreach ($objsMbqEtForumTopic as $oMbqEtForumTopic) {
                $authorUserIds[$oMbqEtForumTopic->topicAuthorId->oriValue] = $oMbqEtForumTopic->topicAuthorId->oriValue;
                $lastReplyUserIds[$oMbqEtForumTopic->lastReplyAuthorId->oriValue] = $oMbqEtForumTopic->lastReplyAuthorId->oriValue;
                $forumIds[$oMbqEtForumTopic->forumId->oriValue] = $oMbqEtForumTopic->forumId->oriValue;
                $topicIds[$oMbqEtForumTopic->topicId->oriValue] = $oMbqEtForumTopic->topicId->oriValue;
            }
            //make topicContent and shortContent properties from first post,ref wbb\page\ThreadPage::initObjectList()
            $oMbqRdEtForumPost = MbqMain::$oClk->newObj('MbqRdEtForumPost');
            foreach ($objsMbqEtForumTopic as &$oMbqEtForumTopic) {
                $oThreadPostList = new ThreadPostList($oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject());
                $oThreadPostList->setObjectIDs(array($oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject()->firstPostID));
                $oThreadPostList->readObjects();
                foreach ($oThreadPostList->getObjects() as $oViewablePost) {
                    $oMbqEtForumTopic->topicContent->setOriValue($oViewablePost->getDecoratedObject()->getMessage());
                    $oMbqEtForumTopic->oFirstMbqEtForumPost = $oViewablePost;
                    if (isset($mbqOpt['originCase']) && $mbqOpt['originCase'] == "byForum"){
                        $oMbqEtForumTopic->shortContent->setOriValue(MbqMain::$oMbqCm->getShortContent($oMbqRdEtForumPost->processContentForDisplay($oViewablePost, true)));
                    }
                    break;  //only get the first post
                }
                $oThreadPostList->setObjectIDs(array($oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject()->lastPostID));
                $oThreadPostList->readObjects();
                foreach ($oThreadPostList->getObjects() as $oViewablePost) {
                    $oMbqEtForumTopic->oLastMbqEtForumPost = $oViewablePost;
                    if (isset($mbqOpt['originCase']) && $mbqOpt['originCase'] != "byForum"){
                        $oMbqEtForumTopic->shortContent->setOriValue(MbqMain::$oMbqCm->getShortContent($oMbqRdEtForumPost->processContentForDisplay($oViewablePost, true)));
                    }
                    break;  //only get the lase post
                }
            }
            /* load oMbqEtForum property */
            $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
            $objsMbqEtForum = $oMbqRdEtForum->getObjsMbqEtForum($forumIds, array('case' => 'byForumIds'));
            foreach ($objsMbqEtForum as $oNewMbqEtForum) {
                foreach ($objsMbqEtForumTopic as &$oMbqEtForumTopic) {
                    if ($oNewMbqEtForum->forumId->oriValue == $oMbqEtForumTopic->forumId->oriValue) {
                        $oMbqEtForumTopic->oMbqEtForum = $oNewMbqEtForum;
                    }
                }
            }
            /* load topic author */
            $oMbqRdEtUser = MbqMain::$oClk->newObj('MbqRdEtUser');
            $objsAuthorMbqEtUser = $oMbqRdEtUser->getObjsMbqEtUser($authorUserIds, array('case' => 'byUserIds'));
            foreach ($objsMbqEtForumTopic as &$oMbqEtForumTopic) {
                foreach ($objsAuthorMbqEtUser as $oAuthorMbqEtUser) {
                    if ($oMbqEtForumTopic->topicAuthorId->oriValue == $oAuthorMbqEtUser->userId->oriValue) {
                        $oMbqEtForumTopic->oAuthorMbqEtUser = $oAuthorMbqEtUser;
                        if ($oMbqEtForumTopic->oAuthorMbqEtUser->iconUrl->hasSetOriValue()) {
                            $oMbqEtForumTopic->authorIconUrl->setOriValue($oMbqEtForumTopic->oAuthorMbqEtUser->iconUrl->oriValue);
                        }
                        break;
                    }
                }
            }
            /* load oLastReplyMbqEtUser */
            $objsLastReplyMbqEtUser = $oMbqRdEtUser->getObjsMbqEtUser($lastReplyUserIds, array('case' => 'byUserIds'));
            foreach ($objsMbqEtForumTopic as &$oMbqEtForumTopic) {
                foreach ($objsLastReplyMbqEtUser as $oLastReplyMbqEtUser) {
                    if ($oMbqEtForumTopic->lastReplyAuthorId->oriValue == $oLastReplyMbqEtUser->userId->oriValue) {
                        $oMbqEtForumTopic->oLastReplyMbqEtUser = $oLastReplyMbqEtUser;
                        break;
                    }
                }
            }
            /* make other properties */
            $oMbqAclEtForumPost = MbqMain::$oClk->newObj('MbqAclEtForumPost');
            foreach ($objsMbqEtForumTopic as &$oMbqEtForumTopic) {
                if ($oMbqAclEtForumPost->canAclReplyPost($oMbqEtForumTopic)) {
                    $oMbqEtForumTopic->canReply->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.canReply.range.yes'));
                } else {
                    $oMbqEtForumTopic->canReply->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.canReply.range.no'));
                }
            }
            if (isset($mbqOpt['oMbqDataPage'])) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oMbqDataPage->datas = $objsMbqEtForumTopic;
                return $oMbqDataPage;
            } else {
                return $objsMbqEtForumTopic;
            }
            /* common end */
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one forum topic by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'oViewableThread' means init forum topic by oViewableThread
     * $mbqOpt['case'] = 'byTopicId' means init forum topic by topic id
     * @return  Mixed
     */
    public function initOMbqEtForumTopic($var = null, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'oViewableThread') {
            $oThread = $var->getDecoratedObject();
            $oMbqEtForumTopic = MbqMain::$oClk->newObj('MbqEtForumTopic');
            $oMbqEtForumTopic->totalPostNum->setOriValue($oThread->replies + 1);
            $oMbqEtForumTopic->topicId->setOriValue($oThread->threadID);
            $oMbqEtForumTopic->forumId->setOriValue($oThread->boardID);
            $oMbqEtForumTopic->firstPostId->setOriValue($oThread->firstPostID);
            $oMbqEtForumTopic->topicTitle->setOriValue($oThread->getTitle());
            $oMbqEtForumTopic->topicAuthorId->setOriValue($oThread->userID);
            $oMbqEtForumTopic->lastReplyAuthorId->setOriValue($oThread->lastPosterID);
            $oMbqEtForumTopic->postTime->setOriValue($oThread->lastPostTime);
            $oMbqEtForumTopic->lastReplyTime->setOriValue($oThread->lastPostTime);
            $oMbqEtForumTopic->replyNumber->setOriValue($oThread->replies);
            if (MbqMain::hasLogin()) {
                if ($var->isNew()) {    //!!!
                    $oMbqEtForumTopic->newPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.newPost.range.yes'));
                } else {
                    $oMbqEtForumTopic->newPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.newPost.range.no'));
                }
            } else {
                $oMbqEtForumTopic->newPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.newPost.range.no'));
            }
            $oMbqEtForumTopic->viewNumber->setOriValue($oThread->views);
            if ($oThread->isDisabled) {
                $oMbqEtForumTopic->state->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.state.range.postOkNeedModeration'));
            } else {
                $oMbqEtForumTopic->state->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.state.range.postOk'));
            }
            if ($oThread->isSubscribed()) {
                $oMbqEtForumTopic->isSubscribed->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.isSubscribed.range.yes'));
            } else {
                $oMbqEtForumTopic->isSubscribed->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.isSubscribed.range.no'));
            }
            if (MbqMain::hasLogin()) {
                $oMbqEtForumTopic->canSubscribe->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.canSubscribe.range.yes'));
            } else {
                $oMbqEtForumTopic->canSubscribe->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForumTopic.canSubscribe.range.no'));
            }
            $oMbqEtForumTopic->mbqBind['oViewableThread'] = $var;
            return $oMbqEtForumTopic;
        } elseif ($mbqOpt['case'] == 'byTopicId') {
            $topicId = $var;
            if ($objsMbqEtForumTopic = $this->getObjsMbqEtForumTopic(array($topicId), array('case' => 'byTopicIds'))) {
                return $objsMbqEtForumTopic[0];
            }
            return false;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>