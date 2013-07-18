<?php

use wcf\util\StringUtil;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseActUploadAttach');

/**
 * upload_attach action
 * 
 * @since  2012-9-11
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqActUploadAttach extends MbqBaseActUploadAttach {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * action implement
     */
    public function actionImplement() {
        if (!MbqMain::$oMbqConfig->moduleIsEnable('forum')) {
            MbqError::alert('', "Not support module forum!", '', MBQ_ERR_NOT_SUPPORT);
        }
        $forumId = MbqMain::$input['forum_id'];
        $groupId = MbqMain::$input['group_id'] ? MbqMain::$input['group_id'] : StringUtil::getRandomID();
        $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
        $objsMbqEtForum = $oMbqRdEtForum->getObjsMbqEtForum(array($forumId), array('case' => 'byForumIds'));
        if ($objsMbqEtForum && ($oMbqEtForum = $objsMbqEtForum[0])) {
            $oMbqAclEtAtt = MbqMain::$oClk->newObj('MbqAclEtAtt');
            if ($oMbqAclEtAtt->canAclUploadAttach($oMbqEtForum)) {    //acl judge
                $oMbqWrEtAtt = MbqMain::$oClk->newObj('MbqWrEtAtt');
                $oMbqEtAtt = $oMbqWrEtAtt->uploadAttachment($forumId, $groupId);
                $oMbqRdEtAtt = MbqMain::$oClk->newObj('MbqRdEtAtt');
                $this->data['result'] = true;
                $data1 = $oMbqRdEtAtt->returnApiDataAttachment($oMbqEtAtt);
                MbqMain::$oMbqCm->mergeApiData($this->data, $data1);
            } else {
                MbqError::alert('', '', '', MBQ_ERR_APP);
            }
        } else {
            MbqError::alert('', "Need valid forum id!", '', MBQ_ERR_APP);
        }
    }
  
}

?>