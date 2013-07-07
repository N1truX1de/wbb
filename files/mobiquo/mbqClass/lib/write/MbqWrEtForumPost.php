<?php

use wbb\data\board\BoardCache;
use wbb\data\post\Post;
use wbb\data\post\PostAction;
use wbb\data\post\PostEditor;
use wbb\data\post\ThreadPostList;
use wbb\data\thread\Thread;
use wbb\data\thread\ThreadAction;
use wbb\data\thread\ThreadEditor;
use wcf\data\user\object\watch\UserObjectWatchAction;
use wcf\form\MessageForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\message\QuickReplyManager;
use wcf\system\poll\PollManager;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

use wbb\data\board\RestrictedBoardNodeList;
use wbb\system\label\object\ThreadLabelObjectHandler;
use wbb\system\WBBCore;
use wcf\system\exception\UserInputException;
use wcf\system\label\LabelHandler;
use wcf\system\language\LanguageFactory;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;
use wcf\util\UserUtil;

use wcf\data\smiley\SmileyCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\bbcode\PreParser;
use wcf\system\message\censorship\Censorship;
use wcf\util\MessageUtil;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtForumPost');

/**
 * forum post write class
 * 
 * @since  2012-8-21
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtForumPost extends MbqBaseWrEtForumPost {
    
    public function __construct() {
    }
    
    /**
     * add forum post
     *
     * @param  Mixed  $var($oMbqEtForumPost or $objsMbqEtForumPost)
     */
    public function addMbqEtForumPost(&$var) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            $oBoard = $var->oMbqEtForumTopic->oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard();
            $oThread = $var->oMbqEtForumTopic->mbqBind['oViewableThread']->getDecoratedObject();
            //ref wbb\form\PostAddForm,wcf\form\MessageForm,wbb\form\ThreadAddForm
            $var->postTitle->setOriValue(StringUtil::trim($var->postTitle->oriValue));
            $var->postContent->setOriValue(MessageUtil::stripCrap(StringUtil::trim($var->postContent->oriValue)));
            $attachmentObjectType = 'com.woltlab.wbb.post';
            $attachmentObjectID = 0;
            $tmpHash = StringUtil::getRandomID();
            $attachmentParentObjectID = $oBoard->boardID;
            //settings
            $preParse = $enableSmilies = $enableBBCodes = $showSignature = $subscribeThread = $enableHtml = 0;
            $preParse = 1;
            if (WCF::getSession()->getPermission('user.message.canUseSmilies')) $enableSmilies = 1;
            if (WCF::getSession()->getPermission('user.message.canUseHtml')) $enableHtml = 1;
            if (WCF::getSession()->getPermission('user.message.canUseBBCodes')) $enableBBCodes = 1;
            $showSignature = 1;
            $subscribeThread = 1;
            $type = Thread::TYPE_DEFAULT;
            // get max text length
            $maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');
            $minCharLength = WBB_POST_MIN_CHAR_LENGTH;
            $minWordCount = WBB_POST_MIN_WORD_COUNT;
            //begin validate
            $allowedBBCodesPermission = 'user.message.allowedBBCodes';
            //validateSubject
            if (StringUtil::length($var->postTitle->oriValue) > 255) MbqError::alert('', "Post title is too long.", '', MBQ_ERR_APP);
                // search for censored words
            if (ENABLE_CENSORSHIP) {
                $result = Censorship::getInstance()->test($var->postTitle->oriValue);
                if ($result) {
                    MbqError::alert('', "Found censored words in post title.", '', MBQ_ERR_APP);
                }
            }
            //validateText
            if (empty($var->postContent->oriValue)) {
                MbqError::alert('', "Need post content.", '', MBQ_ERR_APP);
            }
                // check text length
            if ($maxTextLength != 0 && StringUtil::length($var->postContent->oriValue) > $maxTextLength) {
                MbqError::alert('', "Post content is too long.", '', MBQ_ERR_APP);
            }
            if ($enableBBCodes && $allowedBBCodesPermission) {
                $disallowedBBCodes = BBCodeParser::getInstance()->validateBBCodes($var->postContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission))));
                if (!empty($disallowedBBCodes)) {
                    MbqError::alert('', "Post content included disallowed bbcodes.", '', MBQ_ERR_APP);
                }
            }
                // search for censored words
            if (ENABLE_CENSORSHIP) {
                $result = Censorship::getInstance()->test($var->postContent->oriValue);
                if ($result) {
                    MbqError::alert('', "Found censored words in post content.", '', MBQ_ERR_APP);
                }
            }
            if ($minCharLength && (StringUtil::length($var->postContent->oriValue) < $minCharLength)) {
                MbqError::alert('', "Post content is too short.", '', MBQ_ERR_APP);
            }
            if ($minWordCount && (count(explode(' ', $var->postContent->oriValue)) < $minWordCount)) {
                MbqError::alert('', "Need more words in Post content", '', MBQ_ERR_APP);
            }
            //attachment
    		if (MODULE_ATTACHMENT && $attachmentObjectType) {
    			$attachmentHandler = new AttachmentHandler($attachmentObjectType, $attachmentObjectID, $tmpHash, $attachmentParentObjectID);
    		}
    		//save
            if ($preParse) {
                // BBCodes are enabled
                if ($enableBBCodes) {
                    if ($allowedBBCodesPermission) {
                        $var->postContent->setOriValue(PreParser::getInstance()->parse($var->postContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission)))));
                    }
                    else {
                        $var->postContent->setOriValue(PreParser::getInstance()->parse($var->postContent->oriValue));
                    }
                }
                // BBCodes are disabled, thus no allowed BBCodes
                else {
                    $var->postContent->setOriValue(PreParser::getInstance()->parse($var->postContent->oriValue, array()));
                }
            }
            // save post
    		$data = array(
    			'threadID' => $var->oMbqEtForumTopic->topicId->oriValue,
    			'subject' => $var->postTitle->oriValue,
    			'message' => $var->postContent->oriValue,
    			'time' => TIME_NOW,
    			'userID' => MbqMain::$oCurMbqEtUser->userId->oriValue,
    			'username' => MbqMain::$oCurMbqEtUser->loginName->oriValue,
    			'enableBBCodes' => $enableBBCodes,
    			'enableHtml' => $enableHtml,
    			'enableSmilies' => $enableSmilies,
    			'showSignature' => $showSignature
    		);
    		if (!$oBoard->getPermission('canReplyThreadWithoutModeration')) $data['isDisabled'] = 1;
    		$oPostAction = new PostAction(array(), 'create', array(
    			'data' => $data,
    			'thread' => $oThread,
    			'board' => $oBoard,
    			'attachmentHandler' => $attachmentHandler,
    			'subscribeThread' => $subscribeThread
    		));
    		$resultValues = $oPostAction->executeAction();
            if ($resultValues['returnValues']->postID) {
                $var->postId->setOriValue($resultValues['returnValues']->postID);
            } else {
                MbqError::alert('', "Can not reply post.", '', MBQ_ERR_APP);
            }
        }
    }
  
}

?>