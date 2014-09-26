<?php

use wbb\data\thread\ViewableThreadList;
use wbb\data\post\ViewablePostList;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\WCF;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdForumSearch');

/**
 * forum search class
 * 
 * @since  2012-8-27
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdForumSearch extends MbqBaseRdForumSearch {
    
    public function __construct() {
    }
    
    /**
     * forum advanced search
     *
     * @param  Array  $filter  search filter
     * @param  Object  $oMbqDataPage
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'advanced' means advanced search
     * @return  Object  $oMbqDataPage
     */
    public function forumAdvancedSearch($filter = array(), $oMbqDataPage = null, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'getLatestTopic' || $mbqOpt['case'] == 'getUnreadTopic' || $mbqOpt['case'] == 'getParticipatedTopic') {
            $oMbqRdEtForumTopic = MbqMain::$oClk->newObj('MbqRdEtForumTopic');
            if ($mbqOpt['case'] == 'getParticipatedTopic') {
                $oViewableThreadList = new ViewableThreadList();
                $oViewableThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oViewableThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oViewableThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oViewableThreadList->getConditionBuilder()->add('thread.threadID IN (SELECT threadID from wbb'.WCF_N.'_post where userID = ?)', array(MbqMain::$oCurMbqEtUser->userId->oriValue));   //!!!
                $oViewableThreadList->readObjects();
                $oMbqDataPage->totalNum = $oViewableThreadList->countObjects();
                $oUnreadThreadList = new ViewableThreadList();                
                $oUnreadThreadList->sqlConditionJoins = "    LEFT JOIN   wcf".WCF_N."_tracked_visit tracked_thread_visit
                            ON      (tracked_thread_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.thread')." AND tracked_thread_visit.objectID = thread.threadID AND tracked_thread_visit.userID = ".WCF::getUser()->userID.")
                            LEFT JOIN   wcf".WCF_N."_tracked_visit tracked_board_visit
                            ON      (tracked_board_visit.objectTypeID = ".VisitTracker::getInstance()->getObjectTypeID('com.woltlab.wbb.board')." AND tracked_board_visit.objectID = thread.boardID AND tracked_board_visit.userID = ".WCF::getUser()->userID.")";
                $oUnreadThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oUnreadThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oUnreadThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oUnreadThreadList->getConditionBuilder()->add('thread.threadID IN (SELECT threadID from wbb'.WCF_N.'_post where userID = ?)', array(MbqMain::$oCurMbqEtUser->userId->oriValue));   //!!!
                $oUnreadThreadList->getConditionBuilder()->add('thread.lastPostTime > ?', array(VisitTracker::getInstance()->getVisitTime('com.woltlab.wbb.thread')));
                $oUnreadThreadList->getConditionBuilder()->add("(thread.lastPostTime > tracked_thread_visit.visitTime OR tracked_thread_visit.visitTime IS NULL)");
                $oUnreadThreadList->getConditionBuilder()->add("(thread.lastPostTime > tracked_board_visit.visitTime OR tracked_board_visit.visitTime IS NULL)");
                $oUnreadThreadList ->readObjects();
                $oMbqDataPage->totalUnreadNum = $oUnreadThreadList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableThread';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $oMbqRdEtForumTopic->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
            } elseif ($mbqOpt['case'] == 'getLatestTopic') {
                $oViewableThreadList = new ViewableThreadList();
                $oViewableThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oViewableThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oViewableThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oViewableThreadList->getConditionBuilder()->add('thread.boardID IN (?)', array(MbqMain::$oMbqAppEnv->accessibleBoardIds));
                $oViewableThreadList->readObjects();
                $oMbqDataPage->totalNum = $oViewableThreadList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableThread';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $oMbqRdEtForumTopic->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
                /* common end */
            } elseif ($mbqOpt['case'] == 'getUnreadTopic') {
                require_once(MBQ_APPEXTENTION_PATH.'ExttMbqBoardQuickSearchAction.php');
                $oExttMbqBoardQuickSearchAction = new ExttMbqBoardQuickSearchAction();
                $oExttMbqBoardQuickSearchAction->exttMbqStartNum = $oMbqDataPage->startNum;
                $oExttMbqBoardQuickSearchAction->exttMbqNumPerPage = $oMbqDataPage->numPerPage;
                $ret = $oExttMbqBoardQuickSearchAction->execute();
                $oMbqDataPage->totalNum = $ret['total'];
                $newMbqOpt['case'] = 'byTopicIds';
                $newMbqOpt['oMbqDataPage'] = $oMbqDataPage;
                $oMbqDataPage = $oMbqRdEtForumTopic->getObjsMbqEtForumTopic($ret['topicIds'], $newMbqOpt);
                return $oMbqDataPage;
            }
        } elseif ($mbqOpt['case'] == 'searchTopic') {
            $oMbqRdEtForumTopic = MbqMain::$oClk->newObj('MbqRdEtForumTopic');
            $oViewableThreadList = new ViewableThreadList();
            $oViewableThreadList->sqlOffset = $oMbqDataPage->startNum;
            $oViewableThreadList->sqlLimit = $oMbqDataPage->numPerPage;
            $oViewableThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
            $oViewableThreadList->getConditionBuilder()->add('thread.boardID IN (?)', array(MbqMain::$oMbqAppEnv->accessibleBoardIds));
            $oViewableThreadList->getConditionBuilder()->add('thread.threadID IN (SELECT threadID from wbb'.WCF_N.'_post as mbqPost where mbqPost.subject LIKE ? OR mbqPost.message LIKE ?)', array('%'.addcslashes($filter['keywords'], '_%').'%', '%'.addcslashes($filter['keywords'], '_%').'%'));   //!!!
            $oViewableThreadList->readObjects();
            $oMbqDataPage->totalNum = $oViewableThreadList->countObjects();
            /* common begin */
            $mbqOpt['case'] = 'byObjsViewableThread';
            $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
            return $oMbqRdEtForumTopic->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
            /* common end */
        } elseif ($mbqOpt['case'] == 'searchPost') {
            $oMbqRdEtForumPost = MbqMain::$oClk->newObj('MbqRdEtForumPost');
            $oViewablePostList = new ViewablePostList();
            $oViewablePostList->sqlConditionJoins .= 'INNER JOIN wbb'.WCF_N.'_thread thread ON (post.threadID = thread.threadID AND thread.isAnnouncement = 0)'; //!!!
            $oViewablePostList->getConditionBuilder()->add('thread.boardID IN (?)', array(MbqMain::$oMbqAppEnv->accessibleBoardIds));
            $oViewablePostList->getConditionBuilder()->add('(post.subject LIKE ? OR post.message LIKE ?)', array('%'.addcslashes($filter['keywords'], '_%').'%', '%'.addcslashes($filter['keywords'], '_%').'%'));   //!!!
            $oViewablePostList->readObjects();
            $oMbqDataPage->totalNum = $oViewablePostList->countObjects();
            /* common begin */
            $mbqOpt['case'] = 'byObjsViewablePost';
            $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
            return $oMbqRdEtForumPost->getObjsMbqEtForumPost($oViewablePostList->getObjects(), $mbqOpt);
            /* common end */
        } elseif ($mbqOpt['case'] == 'advanced') {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>