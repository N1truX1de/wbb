<?php

defined('MBQ_IN_IT') or exit;

/**
 * sign in
 * 
 * @since  2013-10-16
 * @author Wu ZeTao <578014287@qq.com>
 */
Abstract Class MbqBaseActSignIn extends MbqBaseAct {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * action implement
     */
    protected function actionImplement() {
        if (!MbqMain::$oMbqConfig->moduleIsEnable('user')) {
            MbqError::alert('', "Not support module user!", '', MBQ_ERR_NOT_SUPPORT);
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_NOT_ACHIEVE);
    }
  
}

?>