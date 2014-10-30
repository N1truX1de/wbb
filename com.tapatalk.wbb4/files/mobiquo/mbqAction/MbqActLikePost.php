<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseActLikePost');

/**
 * like post
 * 
 * @since  2014-10-30
 * @author Kevin <569980801@qq.com>
 */
Class MbqActLikePost extends MbqBaseActLikePost {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * action implement
     */
    public function actionImplement() {
        parent::actionImplement();
    }
  
}

?>