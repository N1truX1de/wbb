<?php

use wcf\data\attachment\AttachmentAction;
use wcf\util\StringUtil;
use wcf\system\upload\UploadHandler;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtAtt');

/**
 * attachment write class
 * 
 * @since  2012-9-11
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtAtt extends MbqBaseWrEtAtt {
    
    public function __construct() {
    }
    
    /**
     * upload an attachment
     *
     * @param  Integer  $forumId
     * @param  String  $groupId
     * @return  Object  $oMbqEtAtt
     */
    public function uploadAttachment($forumId, $groupId) {
        $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
        $objsMbqEtForum = $oMbqRdEtForum->getObjsMbqEtForum(array($forumId), array('case' => 'byForumIds'));
        if ($objsMbqEtForum && ($oMbqEtForum = $objsMbqEtForum[0])) {
        } else {
            MbqError::alert('', "Need valid forum id!", '', MBQ_ERR_APP);
        }
        //ref wcf\action\AJAXUploadAction,wcf\action\AJAXProxyAction,wcf\data\attachment\AttachmentAction
        $parameters['objectType'] = 'com.woltlab.wbb.post';
        $parameters['objectID'] = 0;
        $parameters['tmpHash'] = $groupId ? $groupId : StringUtil::getRandomID();
        $parameters['parentObjectID'] = $oMbqEtForum->forumId->oriValue;
        $parameters['__files'] = UploadHandler::getUploadHandler('attachment'); //ref AJAXUploadAction::readParameters()
        $oAttachmentAction = new AttachmentAction(array(), 'upload', $parameters);    //ref AJAXProxyAction::invoke()
        $oAttachmentAction->validateAction();   //todo:catch exception
        $ret = $oAttachmentAction->executeAction();     //todo:catch exception
        if ($ret['returnValues']['attachments']) {
            $r = array_shift();
            $oMbqEtAtt = MbqMain::$oClk->newObj('MbqEtAtt');
            $oMbqEtAtt->attId->setOriValue($r['attachmentID']);
            $oMbqEtAtt->groupId->setOriValue($parameters['tmpHash']);
            $oMbqEtAtt->filtersSize->setOriValue($r['filesize']);
            $oMbqEtAtt->uploadFileName->setOriValue($r['filename']);
            return $oMbqEtAtt;
        } else {
            MbqError::alert('', "Upload attachment failed!", '', MBQ_ERR_APP);
        }
    }
  
}

?>