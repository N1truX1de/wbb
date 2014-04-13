<?php

use wcf\system\WCF;

use wcf\data\conversation\UserConversationList;
use wcf\data\conversation\label\ConversationLabel;
use wcf\data\conversation\label\ConversationLabelList;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\data\conversation\Conversation;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtPc');

/**
 * private conversation read class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtPc extends MbqBaseRdEtPc {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtPc, $pName, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get unread private conversations number
     *
     * @return  Integer
     */
    public function getUnreadPcNum() {
        if (MbqMain::hasLogin()) {
            //ref __userPanelConversationDropdown.tpl,wcf\system\WCF
            return WCF::getConversationHandler()->getUnreadConversationCount();
        } else {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . 'Need login!');
        }
    }
    
    /**
     * get private conversation objs
     *
     * $mbqOpt['case'] = 'all' means get my all data.
     * $mbqOpt['case'] = 'byConvIds' means get data by conversation ids.$var is the ids.
     * $mbqOpt['case'] = 'byObjsViewableConversation' means get data by objsViewableConversation.$var is the objsViewableConversation.
     * @return  Mixed
     */
    public function getObjsMbqEtPc($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'all') {
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                //ref wcf\page\ConversationListPage,wcf\data\conversation\UserConversationList
                $oUserConversationList = new UserConversationList(WCF::getUser()->userID, '', 0);
                $oUserConversationList->sqlOffset = $oMbqDataPage->startNum;
                $oUserConversationList->sqlLimit = $oMbqDataPage->numPerPage;
                $oUserConversationList->sqlOrderBy = 'conversation.lastPostTime DESC';
                $oUserConversationList->readObjects();
                $oMbqDataPage->totalNum = $oUserConversationList->countObjects();
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableConversation';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $this->getObjsMbqEtPc($oUserConversationList->getObjects(), $mbqOpt);
                /* common end */
            }
        } elseif ($mbqOpt['case'] == 'byConvIds') {
            $oUserConversationList = new UserConversationList(WCF::getUser()->userID, '', 0);
    		$oUserConversationList->setObjectIDs($var);
    		$oUserConversationList->readObjects();
    		$objects = $oUserConversationList->getObjects();
            /* common begin */
            $mbqOpt['case'] = 'byObjsViewableConversation';
            return $this->getObjsMbqEtPc($objects, $mbqOpt);
            /* common end */
        } elseif ($mbqOpt['case'] == 'byObjsViewableConversation') {
            $objsViewableConversation = $var;
            /* common begin */
            $objsMbqEtPc = array();
            foreach ($objsViewableConversation as $oViewableConversation) {
                $objsMbqEtPc[] = $this->initOMbqEtPc($oViewableConversation, array('case' => 'oViewableConversation'));
            }
            /* load objsRecipientMbqEtUser property */
            $oMbqRdEtUser = MbqMain::$oClk->newObj('MbqRdEtUser');
            foreach ($objsMbqEtPc as &$oMbqEtPc) {
                $userIdsRecipient = $oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject()->getParticipantIDs();
                $objsRecipientMbqEtUser = $oMbqRdEtUser->getObjsMbqEtUser($userIdsRecipient, array('case' => 'byUserIds'));
                $oMbqEtPc->objsRecipientMbqEtUser = $objsRecipientMbqEtUser;
                //$oMbqEtPc->participantCount->setOriValue(count($oMbqEtPc->objsRecipientMbqEtUser));
            }
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oMbqDataPage->datas = $objsMbqEtPc;
                return $oMbqDataPage;
            } else {
                return $objsMbqEtPc;
            }
            /* common end */
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one private conversation by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'oViewableConversation' means init private conversation by oViewableConversation
     * @return  Mixed
     */
    public function initOMbqEtPc($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'oViewableConversation') {
            $oConversation = $var->getDecoratedObject();
            $oMbqEtPc = MbqMain::$oClk->newObj('MbqEtPc');
            $oMbqEtPc->convId->setOriValue($oConversation->conversationID);
            $oMbqEtPc->convTitle->setOriValue($oConversation->subject);
            $oMbqEtPc->totalMessageNum->setOriValue($oConversation->replies + 1);
            $oMbqEtPc->participantCount->setOriValue($oConversation->participantCount + 1);
            $oMbqEtPc->startUserId->setOriValue($oConversation->userID);
            $oMbqEtPc->startConvTime->setOriValue($oConversation->time);
            $oMbqEtPc->lastUserId->setOriValue($oConversation->lastPosterID);
            $oMbqEtPc->lastConvTime->setOriValue($oConversation->lastPostTime);
            $oMbqEtPc->newPost->setOriValue($oConversation->isNew() ? MbqBaseFdt::getFdt('MbqFdtPc.MbqEtPc.newPost.range.yes') : MbqBaseFdt::getFdt('MbqFdtPc.MbqEtPc.newPost.range.no'));
            //ref wcf\data\conversation\ConversationAction::validateGetAddParticipantsForm()
            if (!Conversation::isParticipant(array($oConversation->conversationID)) || !$oConversation->canAddParticipants()) {
    			$oMbqEtPc->canInvite->setOriValue(MbqBaseFdt::getFdt('MbqFdtPc.MbqEtPc.canInvite.range.no'));
    		} else {
    			$oMbqEtPc->canInvite->setOriValue(MbqBaseFdt::getFdt('MbqFdtPc.MbqEtPc.canInvite.range.yes'));
    		}
            $oMbqEtPc->deleteMode->setOriValue(MbqBaseFdt::getFdt('MbqFdtPc.MbqEtPc.deleteMode.range.soft-and-hard-delete'));
            $oMbqEtPc->firstMsgId->setOriValue($oConversation->firstMessageID);
            $oMbqEtPc->mbqBind['oViewableConversation'] = $var;
            return $oMbqEtPc; 
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>