<?php

use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\ConversationAction;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtPcMsg');

/**
 * private conversation message read class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtPcMsg extends MbqBaseRdEtPcMsg {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtPcMsg, $pName, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get private conversation message objs
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byPc' means get data by private conversation obj.$var is the private conversation obj.
     * $mbqOpt['case'] = 'byObjsViewableConversationMessage' means get data by objsViewableConversationMessage.$var is the objsViewableConversationMessage.
     * @return  Mixed
     */
    public function getObjsMbqEtPcMsg($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'byPc') {
            $oMbqEtPc = $var;
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oViewableConversation = $var->mbqBind['oViewableConversation'];
                $oConversation = $var->mbqBind['oViewableConversation']->getDecoratedObject();
                $oViewableConversationMessageList = new ViewableConversationMessageList();
                $oViewableConversationMessageList->sqlOffset = $oMbqDataPage->startNum;
                $oViewableConversationMessageList->sqlLimit = $oMbqDataPage->numPerPage;
                //ref wcf\page\ConversationPage::initObjectList()
                $oViewableConversationMessageList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($var->convId->oriValue));
                $oViewableConversationMessageList->readObjects();
                $oMbqDataPage->totalNum = $oViewableConversationMessageList->countObjects();
                //mark read,ref wcf\page\ConversationPage::readData()
                // update last visit time count
        		if ($oViewableConversation->isNew() && $oViewableConversationMessageList->getMaxPostTime() > $oViewableConversation->lastVisitTime) {
        			$visitTime = $oViewableConversationMessageList->getMaxPostTime();
        			if ($visitTime == $oViewableConversation->lastPostTime) $visitTime = TIME_NOW;
        			$conversationAction = new ConversationAction(array($oViewableConversation->getDecoratedObject()), 'markAsRead', array('visitTime' => $visitTime));
        			$conversationAction->executeAction();
        		}
                /* common begin */
                $mbqOpt['case'] = 'byObjsViewableConversationMessage';
                $mbqOpt['oMbqDataPage'] = $oMbqDataPage;
                return $this->getObjsMbqEtPcMsg($oViewableConversationMessageList->getObjects(), $mbqOpt);
                /* common end */
            }
        } elseif ($mbqOpt['case'] == 'byObjsViewableConversationMessage') {
            $objsViewableConversationMessage = $var;
            /* common begin */
            $objsMbqEtPcMsg = array();
            $authorUserIds = array();
            foreach ($objsViewableConversationMessage as $oViewableConversationMessage) {
                $authorUserIds[] = $oViewableConversationMessage->getDecoratedObject()->userID;
                $objsMbqEtPcMsg[] = $this->initOMbqEtPcMsg($oViewableConversationMessage, array('case' => 'oViewableConversationMessage'));
            }
            /* load oAuthorMbqEtUser property */
            $oMbqRdEtUser = MbqMain::$oClk->newObj('MbqRdEtUser');
            $objsAuthorMbqEtUser = $oMbqRdEtUser->getObjsMbqEtUser($authorUserIds, array('case' => 'byUserIds'));
            foreach ($objsMbqEtPcMsg as &$oMbqEtPcMsg) {
                foreach ($objsAuthorMbqEtUser as $oAuthorMbqEtUser) {
                    if ($oMbqEtPcMsg->msgAuthorId->oriValue == $oAuthorMbqEtUser->userId->oriValue) {
                        $oMbqEtPcMsg->oAuthorMbqEtUser = $oAuthorMbqEtUser;
                        break;
                    }
                }
            }
            /* load attachment */
            $oMbqRdEtAtt =  MbqMain::$oClk->newObj('MbqRdEtAtt');
            foreach ($objsMbqEtPcMsg as &$oMbqEtPcMsg) {
                if ($attachmentList = $oMbqEtPcMsg->mbqBind['oViewableConversationMessage']->getDecoratedObject()->getAttachments()) {
                    foreach ($attachmentList->getObjects() as $oAttachment) {
                        $oMbqEtPcMsg->objsMbqEtAtt[] = $oMbqRdEtAtt->initOMbqEtAtt($oAttachment, array('case' => 'oAttachment'));
                    }
                }
            }
            /* load objsNotInContentMbqEtAtt */
            foreach ($objsMbqEtPcMsg as &$oMbqEtPcMsg) {
                $filedataids = MbqMain::$oMbqCm->getAttIdsFromContent($oMbqEtPcMsg->msgContent->oriValue);
                foreach ($oMbqEtPcMsg->objsMbqEtAtt as $oMbqEtAtt) {
                    if (!in_array($oMbqEtAtt->attId->oriValue, $filedataids)) {
                        $oMbqEtPcMsg->objsNotInContentMbqEtAtt[] = $oMbqEtAtt;
                    }
                }
            }
            /* common end */
            if ($mbqOpt['oMbqDataPage']) {
                $oMbqDataPage = $mbqOpt['oMbqDataPage'];
                $oMbqDataPage->datas = $objsMbqEtPcMsg;
                return $oMbqDataPage;
            } else {
                return $objsMbqEtPcMsg;
            }
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one private conversation message by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'oViewableConversationMessage' means init private conversation message by oViewableConversationMessage
     * @return  Mixed
     */
    public function initOMbqEtPcMsg($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'oViewableConversationMessage') {
            $oConversationMessage = $var->getDecoratedObject();
            $oMbqEtPcMsg = MbqMain::$oClk->newObj('MbqEtPcMsg');
            $oMbqEtPcMsg->msgId->setOriValue($oConversationMessage->messageID);
            $oMbqEtPcMsg->convId->setOriValue($oConversationMessage->conversationID);
            $oMbqEtPcMsg->msgContent->setOriValue($oConversationMessage->getMessage());
            $oMbqEtPcMsg->msgContent->setAppDisplayValue($oConversationMessage->getFormattedMessage());
            $oMbqEtPcMsg->msgContent->setTmlDisplayValue($this->processPcMsgContentForDisplay($var, true));
            $oMbqEtPcMsg->msgContent->setTmlDisplayValueNoHtml($this->processPcMsgContentForDisplay($var, false));
            $oMbqEtPcMsg->msgAuthorId->setOriValue($oConversationMessage->userID);
            $oMbqEtPcMsg->postTime->setOriValue($oConversationMessage->time);
            $oMbqEtPcMsg->mbqBind['oViewableConversationMessage'] = $var;
            return $oMbqEtPcMsg;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * process content for display in mobile app
     *
     * @params  Object  $var $var is $oViewableConversationMessage
     * @params  Boolean  $returnHtml
     * @return  String
     */
    public function processPcMsgContentForDisplay($var, $returnHtml) {
        $oMbqRdEtForumPost = MbqMain::$oClk->newObj('MbqRdEtForumPost');
        return $oMbqRdEtForumPost->processContentForDisplay($var, $returnHtml);
    }
    
    /**
     * get_quote_conversation
     *
     * @param  Object  $oMbqEtPcMsg
     * @return  Mixed
     */
    public function getQuoteConversation($oMbqEtPcMsg) {
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
        /* modified from MbqRdEtForumPost::getQuotePostContent() */
        $content = preg_replace('/.*<a href="#tapatalkQuoteEnd"><\/a>/is', '', $oMbqEtPcMsg->msgContent->oriValue);
        $userDisplayName = $oMbqEtPcMsg->oAuthorMbqEtUser ? $oMbqEtPcMsg->oAuthorMbqEtUser->getDisplayName() : '';
        $ret = "[quote=\"$userDisplayName\"]".trim($content)."[/quote]\n\n";
        return $ret;
    }
  
}

?>