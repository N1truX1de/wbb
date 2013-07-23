<?php

use wcf\system\WCF;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtPc');

/**
 * private conversation read class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtPc extends MbqBaseRdEtPc {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtPc, $pName, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get unread private conversations number
     *
     * @return  Integer
     */
    public function getUnreadPcNum() {
        if (MbqMain::hasLogin()) {
            //ref __userPanelConversationDropdown.tpl,wcf\system\WCF
            return WCF::getConversationHandler()->getUnreadConversationCount();
        } else {
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . 'Need login!');
        }
    }
  
}

?>