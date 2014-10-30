<?php

defined('MBQ_IN_IT') or exit;

Abstract Class MbqBaseActLikePost extends MbqBaseAct {
    
    public function __construct() {
        parent::__construct();
    }
    
    protected function actionImplement() {
        $postId = MbqMain::$input[0];
        $oMbqRdEtForumPost = MbqMain::$oClk->newObj('MbqRdEtForumPost');
        $oMbqEtForumPost = $oMbqRdEtForumPost->initOMbqEtForumPost($postId, array('case'=>'byPostId'));
        if (empty($oMbqEtForumPost)){
            MbqError::alert('', 'Need valid post id', '', MBQ_ERR_APP);
        }
        $oMbqAclEtForumPost = MbqMain::$oClk->newObj('MbqAclEtForumPost');
        if (!$oMbqAclEtForumPost->canAclLike($oMbqEtForumPost)) {
            MbqError::alert('', '', '', MBQ_ERR_APP);
        }
        if ($oMbqEtForumPost->isLiked->oriValue == 1){
            $this->data['result'] = true;
            return;
        }
        $oMbqWrEtForumPost = MbqMain::$oClk->newObj('MbqWrEtForumPost');
        $oMbqWrEtForumPost->updateLike($oMbqEtForumPost);
        $this->data['result'] = true;
    }
}