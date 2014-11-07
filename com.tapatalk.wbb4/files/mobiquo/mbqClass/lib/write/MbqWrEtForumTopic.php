<?php

use wbb\page\ThreadPage;
use wbb\data\thread\ThreadEditor;
use wbb\data\thread\ThreadAction;
use wbb\data\post\ThreadPostList;

use wcf\data\smiley\SmileyCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\bbcode\BBCodeParser;
use wcf\system\bbcode\PreParser;
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\message\censorship\Censorship;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\MessageUtil;
use wcf\util\StringUtil;

use wbb\data\board\BoardCache;
use wbb\data\board\RestrictedBoardNodeList;
use wbb\data\post\Post;
use wbb\data\post\PostEditor;
use wbb\data\thread\Thread;
use wbb\system\label\object\ThreadLabelObjectHandler;
use wbb\system\WBBCore;
use wcf\data\user\object\watch\UserObjectWatchAction;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\label\LabelHandler;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\poll\PollManager;
use wcf\system\request\LinkHandler;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\util\HeaderUtil;
use wcf\util\ClassUtil;
use wcf\form\MessageForm;
use wbb\form\ThreadAddForm;

use wbb\data\board\BoardAction;

use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\object\watch\UserObjectWatch;
use wcf\data\user\object\watch\UserObjectWatchEditor;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtForumTopic');

/**
 * forum topic write class
 *
 * @since  2012-8-15
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtForumTopic extends MbqBaseWrEtForumTopic {

    public function __construct() {
    }

    /**
     * add forum topic view num
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function addForumTopicViewNum(&$var = null) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //ref wbb\page\ThreadPage::readData()
            // update view count
            $threadEditor = new ThreadEditor($var->mbqBind['oViewableThread']->getDecoratedObject());
            $threadEditor->updateCounters(array(
                'views' => 1
            ));
        }
    }

    /**
     * mark forum topic read
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'markAllAsRead' means mark all my unread topics as read
     */
    public function markForumTopicRead(&$var = null, $mbqOpt = array()) {
        if (isset($mbqOpt['case']) && $mbqOpt['case'] == 'markAsRead') {
            if (is_array($var)) {
                MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
            }
            $oBoardAction = new BoardAction(array($var), 'markAsRead', array());
            $oBoardAction->validateAction();
            $response = $oBoardAction->executeAction();
        } else if (isset($mbqOpt['case']) && $mbqOpt['case'] == 'markAllAsRead') {
            $oBoardAction = new BoardAction(array(), 'markAllAsRead', array());
            $oBoardAction->validateAction();
            $response = $oBoardAction->executeAction();
        } else {
            if (is_array($var)) {
                MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
            } else {
                //ref wbb\page\ThreadPage::readData()
                $oThreadPostList = new ThreadPostList($var->mbqBind['oViewableThread']->getDecoratedObject());
                $oThreadPostList->sqlOffset = 0;
                $oThreadPostList->sqlLimit = 9;
                $oThreadPostList->readObjects();    //only for making $oThreadPostList used in following code
                // update thread visit
                if ($var->mbqBind['oViewableThread']->isNew() && $oThreadPostList->getMaxPostTime() > $var->mbqBind['oViewableThread']->getVisitTime()) {
                    $threadAction = new ThreadAction(array($var->mbqBind['oViewableThread']->getDecoratedObject()), 'markAsRead', array(
                        'visitTime' => $oThreadPostList->getMaxPostTime(),
                        'viewableThread' => $var->mbqBind['oViewableThread']
                    ));
                    $threadAction->executeAction();
                }
            }
        }
    }

    /**
     * reset forum topic subscription
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function resetForumTopicSubscription(&$var = null) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //do nothing
        }
    }

    /**
     * add forum topic
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function addMbqEtForumTopic(&$var = null) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
            $objsMbqEtForum = $oMbqRdEtForum->getObjsMbqEtForum(array($var->forumId->oriValue), array('case' => 'byForumIds'));
            if ($oMbqEtForum = $objsMbqEtForum[0]) {
                $oBoard = $oMbqEtForum->mbqBind['oDetailedBoardNode']->getBoard();
            } else {
                MbqError::alert('', "Need valid forum.", '', MBQ_ERR_APP);
            }
            //ref wcf\form\MessageForm,wbb\form\ThreadAddForm
            $var->topicTitle->setOriValue(StringUtil::trim($var->topicTitle->oriValue));
            $var->topicContent->setOriValue(MessageUtil::stripCrap(StringUtil::trim($var->topicContent->oriValue)));
            $attachmentObjectType = 'com.woltlab.wbb.post';
            $attachmentObjectID = 0;
            $tmpHash = $var->groupId->oriValue ? $var->groupId->oriValue : StringUtil::getRandomID();
            $attachmentParentObjectID = $oBoard->boardID;
            //settings
            $preParse = $enableSmilies = $enableBBCodes = $showSignature = $subscribeThread = $enableHtml = 0;
            $preParse = 1;
            if (WCF::getSession()->getPermission('user.message.canUseSmilies')) $enableSmilies = 1;
            //if (WCF::getSession()->getPermission('user.message.canUseHtml')) $enableHtml = 1;
            if (WCF::getSession()->getPermission('user.message.canUseBBCodes')) $enableBBCodes = 1;
            $showSignature = 1;
            $subscribeThread = 1;
            $type = Thread::TYPE_DEFAULT;
            // get max text length
            $maxTextLength = WCF::getSession()->getPermission('user.board.maxPostLength');
            $minCharLength = WBB_THREAD_MIN_CHAR_LENGTH;
            $minWordCount = WBB_THREAD_MIN_WORD_COUNT;
            //begin validate
            $allowedBBCodesPermission = 'user.message.allowedBBCodes';
            //validateSubject
            if (empty($var->topicTitle->oriValue)) MbqError::alert('', "Need topic title.", '', MBQ_ERR_APP);
            if (StringUtil::length($var->topicTitle->oriValue) > 255) MbqError::alert('', "Topic title is too long.", '', MBQ_ERR_APP);
            // search for censored words
            if (ENABLE_CENSORSHIP) {
                $result = Censorship::getInstance()->test($var->topicTitle->oriValue);
                if ($result) {
                    MbqError::alert('', "Found censored words in topic title.", '', MBQ_ERR_APP);
                }
            }
            //validateText
            if (empty($var->topicContent->oriValue)) {
                MbqError::alert('', "Need topic content.", '', MBQ_ERR_APP);
            }
            // check text length
            if ($maxTextLength != 0 && StringUtil::length($var->topicContent->oriValue) > $maxTextLength) {
                MbqError::alert('', "Topic content is too long.", '', MBQ_ERR_APP);
            }
            if ($enableBBCodes && $allowedBBCodesPermission) {
                $disallowedBBCodes = BBCodeParser::getInstance()->validateBBCodes($var->topicContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission))));
                if (!empty($disallowedBBCodes)) {
                    MbqError::alert('', "Topic content included disallowed bbcodes.", '', MBQ_ERR_APP);
                }
            }
            // search for censored words
            if (ENABLE_CENSORSHIP) {
                $result = Censorship::getInstance()->test($var->topicContent->oriValue);
                if ($result) {
                    MbqError::alert('', "Found censored words in topic content.", '', MBQ_ERR_APP);
                }
            }
            if ($minCharLength && (StringUtil::length($var->topicContent->oriValue) < $minCharLength)) {
                MbqError::alert('', "Topic content is too short.", '', MBQ_ERR_APP);
            }
            if ($minWordCount && (count(explode(' ', $var->topicContent->oriValue)) < $minWordCount)) {
                MbqError::alert('', "Need more words in topic content", '', MBQ_ERR_APP);
            }
            //language
            //$languageID = LanguageFactory::getInstance()->getUserLanguage()->languageID;
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
                        $var->topicContent->setOriValue(PreParser::getInstance()->parse($var->topicContent->oriValue, ArrayUtil::trim(explode(',', WCF::getSession()->getPermission($allowedBBCodesPermission)))));
                    }
                    else {
                        $var->topicContent->setOriValue(PreParser::getInstance()->parse($var->topicContent->oriValue));
                    }
                }
                // BBCodes are disabled, thus no allowed BBCodes
                else {
                    $var->topicContent->setOriValue(PreParser::getInstance()->parse($var->topicContent->oriValue, array()));
                }
            }
            // save thread
            $data = array(
                'boardID' => $var->forumId->oriValue,
                'languageID' => $languageID,
                'topic' => $var->topicTitle->oriValue,
                'time' => TIME_NOW,
                'userID' => MbqMain::$oCurMbqEtUser->userId->oriValue,
                'username' => MbqMain::$oCurMbqEtUser->loginName->oriValue,
                'hasLabels' => 0
            );
            $data['isClosed'] = 0;
            if (!$oBoard->getPermission('canStartThreadWithoutModeration')) $data['isDisabled'] = 1;
            $threadData = array(
                'data' => $data,
                'board' => $oBoard,
                'attachmentHandler' => $attachmentHandler,
                'postData' => array(
                    'message' => $var->topicContent->oriValue,
                    'enableBBCodes' => $enableBBCodes,
                    'enableHtml' => $enableHtml,
                    'enableSmilies' => $enableSmilies,
                    'showSignature' => $showSignature
            ),
                'tags' => array(),
                'subscribeThread' => $subscribeThread
            );
            $oThreadAction = new ThreadAction(array(), 'create', $threadData);
            $resultValues = $oThreadAction->executeAction();
            if ($resultValues['returnValues']->threadID) {
                $var->topicId->setOriValue($resultValues['returnValues']->threadID);
                $oMbqRdEtForumTopic = MbqMain::$oClk->newObj('MbqRdEtForumTopic');
                $var = $oMbqRdEtForumTopic->initOMbqEtForumTopic($var->topicId->oriValue, array('case' => 'byTopicId'));    //for get state

                // update visit time (messages shouldn't occur as new upon next visit)
                @$oThread = $var->mbqBind['oViewableThread']->getDecoratedObject();
                if (!empty($oThread)){
                    $containerActionClassName = 'wbb\data\thread\ThreadAction';
                    if (ClassUtil::isInstanceOf($containerActionClassName, 'wcf\data\IVisitableObjectAction')) {
                        $containerAction = new $containerActionClassName(array(($oThread instanceof DatabaseObjectDecorator ? $oThread->getDecoratedObject() : $oThread)), 'markAsRead');
                        $containerAction->executeAction();
                    }
                }
            } else {
                MbqError::alert('', "Can not create topic.", '', MBQ_ERR_APP);
            }
        }
    }

    /**
     * subscribe topic
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function subscribeTopic(&$var = null) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //ref wcf\data\user\object\watch\UserObjectWatchAction::subscribe()
            $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.user.objectWatch', 'com.woltlab.wbb.thread');
            $userObjectWatch = UserObjectWatch::getUserObjectWatch($objectType->objectTypeID, WCF::getUser()->userID, intval($var->topicId->oriValue));
            if (!$userObjectWatch) {    //help confirm not subscribed
                UserObjectWatchEditor::create(array(
        			'userID' => WCF::getUser()->userID,
        			'objectID' => intval($var->topicId->oriValue),
        			'objectTypeID' => $objectType->objectTypeID,
        			'notification' => 0
                ));
                // reset user storage
                $objectType->getProcessor()->resetUserStorage(array(WCF::getUser()->userID));
            }
        }
    }

    /**
     * unsubscribe topic
     *
     * @param  Mixed  $var($oMbqEtForumTopic or $objsMbqEtForumTopic)
     */
    public function unsubscribeTopic(&$var = null) {
        if (is_array($var)) {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
        } else {
            //ref wcf\data\user\object\watch\UserObjectWatchAction::unsubscribe()
            $objectType = ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.user.objectWatch', 'com.woltlab.wbb.thread');
            $userObjectWatch = UserObjectWatch::getUserObjectWatch($objectType->objectTypeID, WCF::getUser()->userID, intval($var->topicId->oriValue));
            if ($userObjectWatch->watchID) {
                $editor = new UserObjectWatchEditor($userObjectWatch);
                $editor->delete();
                // reset user storage
                $objectType->getProcessor()->resetUserStorage(array(WCF::getUser()->userID));
            }
        }
    }

}

?>