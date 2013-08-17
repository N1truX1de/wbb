<?php

use wcf\data\conversation\message\ConversationMessage;
use wcf\data\conversation\message\ConversationMessageAction;
use wcf\data\conversation\message\ViewableConversationMessageList;
use wcf\data\conversation\Conversation;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;
use wcf\util\StringUtil;
use wcf\util\MessageUtil;
use wcf\util\ArrayUtil;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\message\censorship\Censorship;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\PreParser;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtPcMsg');

/**
 * private conversation message write class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtPcMsg extends MbqBaseWrEtPcMsg {
    
    public function __construct() {
    }
    
    /**
     * add private conversation message
     *
     * @param  Object  $oMbqEtPcMsg
     * @param  Object  $oMbqEtPc
     */
    public function addMbqEtPcMsg(&$oMbqEtPcMsg, $oMbqEtPc) {
        $oConversation = $oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject();
        //ref wcf\form\MessageForm,wcf\form\ConversationMessageAddForm
        $oMbqEtPcMsg->msgContent->setOriValue(MessageUtil::stripCrap(StringUtil::trim($oMbqEtPcMsg->msgContent->oriValue)));
        $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
        $attachmentObjectID = 0;
        $tmpHash = StringUtil::getRandomID();
        $attachmentParentObjectID = 0;
        //settings
        $preParse = $enableSmilies = $enableBBCodes = $showSignature = $enableHtml = 0;
        $preParse = 1;
        if (WCF::getSession()->getPermission('user.message.canUseSmilies')) $enableSmilies = 1;
        //if (WCF::getSession()->getPermission('user.message.canUseHtml')) $enableHtml = 1;
        if (WCF::getSession()->getPermission('user.message.canUseBBCodes')) $enableBBCodes = 1;
        $showSignature = 1;
        // get max text length
        $maxTextLength = WCF::getSession()->getPermission('user.conversation.maxLength'); //!!! use this,is better than 0
        //begin validate
        $allowedBBCodesPermission = 'user.message.allowedBBCodes';
        //validateText
        if (empty($oMbqEtPcMsg->msgContent->oriValue)) {
            MbqError::alert('', "Need message content.", '', MBQ_ERR_APP);
        }
            // check text length
        if ($maxTextLength != 0 && StringUtil::length($oMbqEtPcMsg->msgContent->oriValue) > $maxTextLength) {
            MbqError::alert('', "Message content is too long.", '', MBQ_ERR_APP);
        }
        if ($enableBBCodes && $allowedBBCodesPermission) {
            $disallowedBBCodes = BBCodeParser::getInstance()->validateBBCodes($oMbqEtPcMsg->msgContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission))));
            if (!empty($disallowedBBCodes)) {
                MbqError::alert('', "Message content included disallowed bbcodes.", '', MBQ_ERR_APP);
            }
        }
            // search for censored words
        if (ENABLE_CENSORSHIP) {
            $result = Censorship::getInstance()->test($oMbqEtPcMsg->msgContent->oriValue);
            if ($result) {
                MbqError::alert('', "Found censored words in message content.", '', MBQ_ERR_APP);
            }
        }
        //language
        $languageID = NULL;
        //attachment
		if (MODULE_ATTACHMENT && $attachmentObjectType) {
			$attachmentHandler = new AttachmentHandler($attachmentObjectType, $attachmentObjectID, $tmpHash, $attachmentParentObjectID);
		}
        //save
        if ($preParse) {
            // BBCodes are enabled
            if ($enableBBCodes) {
                if ($allowedBBCodesPermission) {
                    $oMbqEtPcMsg->msgContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPcMsg->msgContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission)))));
                }
                else {
                    $oMbqEtPcMsg->msgContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPcMsg->msgContent->oriValue));
                }
            }
            // BBCodes are disabled, thus no allowed BBCodes
            else {
                $oMbqEtPcMsg->msgContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPcMsg->msgContent->oriValue, array()));
            }
        }
		// save message
		$data = array(
			'conversationID' => $oConversation->conversationID,	
			'message' => $oMbqEtPcMsg->msgContent->oriValue,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username,
			'enableBBCodes' => $enableBBCodes,
			'enableHtml' => $enableHtml,
			'enableSmilies' => $enableSmilies,
			'showSignature' => $showSignature
		);
		$messageData = array(
			'data' => $data,
			'attachmentHandler' => $attachmentHandler
		);
		$objectAction = new ConversationMessageAction(array(), 'create', $messageData);
		$resultValues = $objectAction->executeAction();
        if ($resultValues['returnValues']->messageID) {
            $oMbqEtPcMsg->msgId->setOriValue($resultValues['returnValues']->messageID);
        } else {
            MbqError::alert('', "Can not create topic.", '', MBQ_ERR_APP);
        }
    }
  
}

?>