<?php

use wbb\data\board\DetailedBoardNodeList;
use wcf\data\user\online\UsersOnlineList;
use wcf\data\option\OptionAction;
use wcf\page\AbstractPage;
use wbb\system\cache\builder\StatsCacheBuilder;
use wcf\system\cache\builder\UserStatsCacheBuilder;
use wcf\system\dashboard\DashboardHandler;
use wcf\system\menu\page\PageMenu;
use wcf\system\request\LinkHandler;
use wcf\system\user\collapsible\content\UserCollapsibleContentHandler;
use wcf\system\MetaTagHandler;
use wcf\system\WCF;

use wcf\system\attachment\AttachmentHandler;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtForum');

/**
 * forum read class
 * 
 * @since  2012-8-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtForum extends MbqBaseRdEtForum {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtForum = null, $pName = null, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get forum tree structure
     *
     * @return  Array
     */
    public function getForumTree() {
        /* ref wbb\page\BoardListPage */
        $oDetailedBoardNodeList = new DetailedBoardNodeList();
        $oDetailedBoardNodeList->readNodeTree();
        $tree = $oDetailedBoardNodeList->getNodeList();
        $newTree = array();
        foreach ($tree as $oDetailedBoardNode) {
            if ($oDetailedBoardNode->getBoard()->parentID === NULL) {   //top level
                $id = $oDetailedBoardNode->getBoard()->boardID;
                if ($oNewMbqEtForum = $this->initOMbqEtForum($oDetailedBoardNode, array('case' => 'oDetailedBoardNode'))) {
                    MbqMain::$oMbqAppEnv->exttAllForums[$id] = clone $oNewMbqEtForum;
                    $newTree [$id] = $oNewMbqEtForum;
                    $this->exttRecurInitObjsSubMbqEtForum($newTree[$id]);
                }
            }
        }
        return $newTree;
    }
    /**
     * recursive init objsSubMbqEtForum
     *
     * @param  Object  $oMbqEtForum  the object need init objsSubMbqEtForum
     */
    private function exttRecurInitObjsSubMbqEtForum(&$oMbqEtForum) {
        $oDetailedBoardNode = $oMbqEtForum->mbqBind['oDetailedBoardNode'];
        $oDetailedBoardNodeList = new DetailedBoardNodeList($oDetailedBoardNode->getBoard()->boardID, $oDetailedBoardNode->getDepth() + 1);
        $oDetailedBoardNodeList->readNodeTree();
        $tree = $oDetailedBoardNodeList->getNodeList();
        foreach ($tree as $oNewDetailedBoardNode) {
            $id = $oNewDetailedBoardNode->getBoard()->boardID;
            if ($oNewMbqEtForum = $this->initOMbqEtForum($oNewDetailedBoardNode, array('case' => 'oDetailedBoardNode'))) {
                MbqMain::$oMbqAppEnv->exttAllForums[$id] = clone $oNewMbqEtForum;
                $oMbqEtForum->objsSubMbqEtForum[$id] = $oNewMbqEtForum;
                $this->exttRecurInitObjsSubMbqEtForum($oMbqEtForum->objsSubMbqEtForum[$id]);
            }
        }
    }
    
    /**
     * get forum objs
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byForumIds' means get data by forum ids.$var is the ids.
     * @return  Array
     */
    public function getObjsMbqEtForum($var = null, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'byForumIds') {
            $objsMbqEtForum = array();
            $i = 0;
            foreach ($var as $id) {
                if ($oNewMbqEtForum = $this->initOMbqEtForum($id, array('case' => 'byForumId'))) {
                    $objsMbqEtForum[$i] = $oNewMbqEtForum;
                    $i ++;
                }
            }
            return $objsMbqEtForum;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one forum by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byForumId' means init forum by forum id
     * $mbqOpt['case'] = 'oDetailedBoardNode' means init forum by oDetailedBoardNode
     * @return  Mixed
     */
    public function initOMbqEtForum($var = null, $mbqOpt = array()) {
        if ($mbqOpt['case'] == 'byForumId') {
            if (isset(MbqMain::$oMbqAppEnv->exttAllForums[$var])) {
                return MbqMain::$oMbqAppEnv->exttAllForums[$var];
            }
            return false;
        } elseif ($mbqOpt['case'] == 'oDetailedBoardNode') {
            $oBoard = $var->getBoard();
            $oMbqEtForum = MbqMain::$oClk->newObj('MbqEtForum');
            $oMbqEtForum->forumId->setOriValue($oBoard->boardID);
            $oMbqEtForum->forumName->setOriValue($oBoard->getTitle());
            $oMbqEtForum->description->setOriValue($oBoard->description);
            $oMbqEtForum->totalTopicNum->setOriValue($var->getThreads());
            $oMbqEtForum->totalPostNum->setOriValue($var->getPosts());
            $oMbqEtForum->parentId->setOriValue($oBoard->parentID);
            if ($var->getUnreadThreads()) {
                $oMbqEtForum->newPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.newPost.range.yes'));
            }
            if ($oBoard->isExternalLink()) {
                $oMbqEtForum->url->setOriValue($oBoard->getLink());
            }
            if ($oBoard->isCategory()) {
                $oMbqEtForum->subOnly->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.subOnly.range.yes'));
            } elseif ($oBoard->isBoard()) {
                $oMbqEtForum->subOnly->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.subOnly.range.no'));
            }
            $oMbqAclEtForumTopic = MbqMain::$oClk->newObj('MbqAclEtForumTopic');
            $oMbqEtForum->mbqBind['oDetailedBoardNode'] = $var;
            if ($oMbqAclEtForumTopic->canAclNewTopic($oMbqEtForum)) {
                $oMbqEtForum->canPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.canPost.range.yes'));
            } else {
                $oMbqEtForum->canPost->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.canPost.range.no'));
            }
            $attachmentObjectType = 'com.woltlab.wbb.post';
            $attachmentObjectID = 0;
            $tmpHash = '';
            $attachmentParentObjectID = $oBoard->boardID;
            $oAttachmentHandler = new AttachmentHandler($attachmentObjectType, $attachmentObjectID, $tmpHash, $attachmentParentObjectID);
            if ($oAttachmentHandler->canUpload()) {
                $oMbqEtForum->canUpload->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.canUpload.range.yes'));
            } else {
                $oMbqEtForum->canUpload->setOriValue(MbqBaseFdt::getFdt('MbqFdtForum.MbqEtForum.canUpload.range.no'));
            }
            return $oMbqEtForum;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>