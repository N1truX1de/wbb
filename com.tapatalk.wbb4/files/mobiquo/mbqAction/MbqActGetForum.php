<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseActGetForum');

/**
 * get_forum action
 * 
 * @since  2012-8-3
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqActGetForum extends MbqBaseActGetForum {
    
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
        $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
        $this->data = $oMbqRdEtForum->returnApiTreeDataForum(MbqMain::$oMbqAppEnv->exttForumTree);
    }
  
}

?>