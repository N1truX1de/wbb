<?php

use wbb\data\thread\ViewableThreadList;

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
    public function forumAdvancedSearch($filter, $oMbqDataPage, $mbqOpt) {
        if ($mbqOpt['case'] == 'getLatestTopic' || $mbqOpt['case'] == 'getUnreadTopic' || $mbqOpt['case'] == 'getParticipatedTopic') {
            $oMbqRdEtForumTopic = MbqMain::$oClk->newObj('MbqRdEtForumTopic');
            if ($mbqOpt['case'] == 'getParticipatedTopic') {
                MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
            } elseif ($mbqOpt['case'] == 'getLatestTopic') {
                $oViewableThreadList = new ViewableThreadList();
                $oViewableThreadList->sqlOffset = $oMbqDataPage->startNum;
                $oViewableThreadList->sqlLimit = $oMbqDataPage->numPerPage;
                $oViewableThreadList->getConditionBuilder()->add('thread.isAnnouncement = 0');   //!!!
                $oViewableThreadList->readObjects();
                $oMbqDataPage->totalNum = $oViewableThreadList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableThread';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $oMbqRdEtForumTopic->getObjsMbqEtForumTopic($oViewableThreadList->getObjects(), $mbqOpt);
                /* common end */
            } elseif ($mbqOpt['case'] == 'getUnreadTopic') {
                MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
            }
        } elseif ($mbqOpt['case'] == 'searchTopic') {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } elseif ($mbqOpt['case'] == 'searchPost') {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } elseif ($mbqOpt['case'] == 'advanced') {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>