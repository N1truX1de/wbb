<?php

use wcf\data\conversation\Conversation;
use wcf\data\conversation\ConversationAction;
use wcf\data\user\UserProfile;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\conversation\ConversationHandler;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\NamedUserException;
use wcf\system\exception\UserInputException;
use wcf\system\message\quote\MessageQuoteManager;
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
use wcf\data\conversation\ConversationEditor;
use wcf\system\log\modification\ConversationModificationLogHandler;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\clipboard\ClipboardHandler;
use wcf\system\database\util\PreparedStatementConditionBuilder;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtPc');

/**
 * private conversation write class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtPc extends MbqBaseWrEtPc {
    
    public function __construct() {
    }
    
    /**
     * mark private conversation read
     *
     * @param  Object  $oMbqEtPc
     * @return  Mixed
     */
    public function markPcRead($oMbqEtPc) {
        //this has been done in MbqRdEtPcMsg::getObjsMbqEtPcMsg() with $mbqOpt['case'] == 'byPc',so need do nothing here.
    }
    
    /**
     * add private conversation
     *
     * @param  Object  $oMbqEtPc
     */
    public function addMbqEtPc(&$oMbqEtPc) {
        //ref wcf\form\MessageForm,wcf\form\ConversationAddForm
        $oMbqEtPc->convTitle->setOriValue(StringUtil::trim($oMbqEtPc->convTitle->oriValue));
        $oMbqEtPc->convContent->setOriValue(MessageUtil::stripCrap(StringUtil::trim($oMbqEtPc->convContent->oriValue)));
        $attachmentObjectType = 'com.woltlab.wcf.conversation.message';
        $attachmentObjectID = 0;
        $tmpHash = StringUtil::getRandomID();
        $attachmentParentObjectID = 0;
        // check max pc permission
		if (ConversationHandler::getInstance()->getConversationCount() >= WCF::getSession()->getPermission('user.conversation.maxConversations')) {
			MbqError::alert('', 'Sorry.You can not create more conversations.', '', MBQ_ERR_APP);
		}
        //settings
        $preParse = $enableSmilies = $enableBBCodes = $showSignature = $enableHtml = 0;
        $preParse = 1;
        if (WCF::getSession()->getPermission('user.message.canUseSmilies')) $enableSmilies = 1;
        //if (WCF::getSession()->getPermission('user.message.canUseHtml')) $enableHtml = 1;
        if (WCF::getSession()->getPermission('user.message.canUseBBCodes')) $enableBBCodes = 1;
        $showSignature = 1;
        // get max text length
        $maxTextLength = WCF::getSession()->getPermission('user.conversation.maxLength');
        //begin validate
        try {
            $participantIDs = Conversation::validateParticipants(implode(",", $oMbqEtPc->userNames->oriValue));
        } catch (UserInputException $e) {
            MbqError::alert('', $e->getMessage(), '', MBQ_ERR_APP);
        } catch (Exception $e) {
            MbqError::alert('', $e->getMessage(), '', MBQ_ERR_APP);
        }
        if (empty($participantIDs)) {
             MbqError::alert('', 'Need valid participant user ids.', '', MBQ_ERR_APP);
        }
        // check number of participants
		if (count($participantIDs) > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
			MbqError::alert('', 'Too many participants.', '', MBQ_ERR_APP);
		}
        $allowedBBCodesPermission = 'user.message.allowedBBCodes';
        //validateSubject
        if (empty($oMbqEtPc->convTitle->oriValue)) MbqError::alert('', "Need conversation title.", '', MBQ_ERR_APP);
        if (StringUtil::length($oMbqEtPc->convTitle->oriValue) > 255) MbqError::alert('', "Conversation title is too long.", '', MBQ_ERR_APP);
            // search for censored words
        if (ENABLE_CENSORSHIP) {
            $result = Censorship::getInstance()->test($oMbqEtPc->convTitle->oriValue);
            if ($result) {
                MbqError::alert('', "Found censored words in conversation title.", '', MBQ_ERR_APP);
            }
        }
        //validateText
        if (empty($oMbqEtPc->convContent->oriValue)) {
            MbqError::alert('', "Need conversation content.", '', MBQ_ERR_APP);
        }
            // check text length
        if ($maxTextLength != 0 && StringUtil::length($oMbqEtPc->convContent->oriValue) > $maxTextLength) {
            MbqError::alert('', "Conversation content is too long.", '', MBQ_ERR_APP);
        }
        if ($enableBBCodes && $allowedBBCodesPermission) {
            $disallowedBBCodes = BBCodeParser::getInstance()->validateBBCodes($oMbqEtPc->convContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission))));
            if (!empty($disallowedBBCodes)) {
                MbqError::alert('', "Conversation content included disallowed bbcodes.", '', MBQ_ERR_APP);
            }
        }
            // search for censored words
        if (ENABLE_CENSORSHIP) {
            $result = Censorship::getInstance()->test($oMbqEtPc->convContent->oriValue);
            if ($result) {
                MbqError::alert('', "Found censored words in conversation content.", '', MBQ_ERR_APP);
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
                    $oMbqEtPc->convContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPc->convContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission)))));
                }
                else {
                    $oMbqEtPc->convContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPc->convContent->oriValue));
                }
            }
            // BBCodes are disabled, thus no allowed BBCodes
            else {
                $oMbqEtPc->convContent->setOriValue(PreParser::getInstance()->parse($oMbqEtPc->convContent->oriValue, array()));
            }
        }
		// save conversation
		$data = array(
			'subject' => $oMbqEtPc->convTitle->oriValue,
			'time' => TIME_NOW,
			'userID' => WCF::getUser()->userID,
			'username' => WCF::getUser()->username,
			'isDraft' => 0,
			'participantCanInvite' => 0
		);
		$conversationData = array(
			'data' => $data,
			'attachmentHandler' => $attachmentHandler,
			'messageData' => array(
				'message' => $oMbqEtPc->convContent->oriValue,
				'enableBBCodes' => $enableBBCodes,
				'enableHtml' => $enableHtml,
				'enableSmilies' => $enableSmilies,
				'showSignature' => $showSignature
			)
		);
		$conversationData['participants'] = $participantIDs;
		$conversationData['invisibleParticipants'] = array();
		$objectAction = new ConversationAction(array(), 'create', $conversationData);
		$resultValues = $objectAction->executeAction();
        if ($resultValues['returnValues']->conversationID) {
            $oMbqEtPc->convId->setOriValue($resultValues['returnValues']->conversationID);
        } else {
            MbqError::alert('', "Can not create topic.", '', MBQ_ERR_APP);
        }
    }
    
    /**
     * invite participant
     *
     * @param  Object  $oMbqEtPcInviteParticipant
     */
    public function inviteParticipant($oMbqEtPcInviteParticipant) {
        $oConversation = $oMbqEtPcInviteParticipant->oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject();
        $conversationEditor = new ConversationEditor($oConversation);
        //ref wcf\data\conversation\ConversationAction::addParticipants()
        try {
            $participantIDs = Conversation::validateParticipants(implode(",", $oMbqEtPcInviteParticipant->userNames->oriValue), 'participants', $conversationEditor->getParticipantIDs(true));
        } catch (UserInputException $e) {
            MbqError::alert('', $e->getMessage(), '', MBQ_ERR_APP);
        } catch (Exception $e) {
            MbqError::alert('', $e->getMessage(), '', MBQ_ERR_APP);
        }
        // validate limit
		$newCount = $conversationEditor->participants + count($participantIDs);
		if ($newCount > WCF::getSession()->getPermission('user.conversation.maxParticipants')) {
		    MbqError::alert('', 'Too many participants.', '', MBQ_ERR_APP);
		}
		$count = 0;
		$successMessage = '';
		if (!empty($participantIDs)) {
			// check for already added participants
			$data = array();
			if ($conversationEditor->isDraft) {
				$draftData = unserialize($conversationEditor->draftData);
				$draftData['participants'] = array_merge($draftData['participants'], $participantIDs);
				$data = array('data' => array('draftData' => serialize($draftData)));
			}
			else {
				$data = array('participants' => $participantIDs);
			}
			
			$conversationAction = new ConversationAction(array($conversationEditor), 'update', $data);
			$conversationAction->executeAction();
			
			$count = count($participantIDs);
			$successMessage = WCF::getLanguage()->getDynamicVariable('wcf.conversation.edit.addParticipants.success', array('count' => $count));
			
			ConversationModificationLogHandler::getInstance()->addParticipants($conversationEditor->getDecoratedObject(), $participantIDs);
			
			if (!$conversationEditor->isDraft) {
				// update participant summary
				$conversationEditor->updateParticipantSummary();
			}
		}
    }
    
    /**
     * delete conversation
     *
     * @param  Object  $oMbqEtPc
     * @param  Integer  $mode
     */
    public function deleteConversation($oMbqEtPc, $mode) {
        $oConversation = $oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject();
        $conversationEditor = new ConversationEditor($oConversation);
        if ($mode == 1) {
            $hideConversation = Conversation::STATE_HIDDEN;
        } elseif ($mode == 2) {
            $hideConversation = Conversation::STATE_LEFT;
        } else {
            MbqError::alert('', 'Need valid mode.', '', MBQ_ERR_APP);
        }
        $objectIDs = array($oMbqEtPc->convId->oriValue);
        //ref wcf\data\conversation\ConversationAction::hideConversation()
		$sql = "UPDATE	wcf".WCF_N."_conversation_to_user
			SET	hideConversation = ?
			WHERE	conversationID = ?
				AND participantID = ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		WCF::getDB()->beginTransaction();
		foreach ($objectIDs as $conversationID) {
			$statement->execute(array(
				$hideConversation,
				$conversationID,
				WCF::getUser()->userID
			));
		}
		WCF::getDB()->commitTransaction();
		// reset user's conversation counters if user leaves conversation
		// permanently
		if ($hideConversation == Conversation::STATE_LEFT) {
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'conversationCount');
			UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), 'unreadConversationCount');
		}
		// add modification log entry
		if ($hideConversation == Conversation::STATE_LEFT) {
			ConversationModificationLogHandler::getInstance()->leave($conversationEditor->getDecoratedObject());
		}
		// unmark items
		ClipboardHandler::getInstance()->unmark($objectIDs, ClipboardHandler::getInstance()->getObjectTypeID('com.woltlab.wcf.conversation.conversation'));
		
		if ($hideConversation == Conversation::STATE_LEFT) {
			// update participant summary
			ConversationEditor::updateParticipantSummaries($objectIDs);
			// delete conversation if all users have left it
			$conditionBuilder = new PreparedStatementConditionBuilder();
			$conditionBuilder->add('conversation.conversationID IN (?)', array($objectIDs));
			$conditionBuilder->add('conversation_to_user.conversationID IS NULL');
			$conversationIDs = array();
			$sql = "SELECT		DISTINCT conversation.conversationID
				FROM		wcf".WCF_N."_conversation conversation
				LEFT JOIN	wcf".WCF_N."_conversation_to_user conversation_to_user
				ON		(conversation_to_user.conversationID = conversation.conversationID AND conversation_to_user.hideConversation <> ".Conversation::STATE_LEFT.")
				".$conditionBuilder;
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			while ($row = $statement->fetchArray()) {
				$conversationIDs[] = $row['conversationID'];
			}
			if (!empty($conversationIDs)) {
				$action = new ConversationAction($conversationIDs, 'delete');
				$action->executeAction();
			}
		}
    }
  
}

?>