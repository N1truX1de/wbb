<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseAclEtPc');

/**
 * private conversation acl class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAclEtPc extends MbqBaseAclEtPc {
    
    public function __construct() {
    }
    
    /**
     * judge can get_inbox_stat
     *
     * @return  Boolean
     */
    public function canAclGetInboxStat() {
        return MbqMain::hasLogin();
    }
    
    /**
     * judge can get_conversations
     *
     * @return  Boolean
     */
    public function canAclGetConversations() {
        if (MbqMain::hasLogin() && (MbqMain::$oCurMbqEtUser->canPm->oriValue == MbqBaseFdt::getFdt('MbqFdtUser.MbqEtUser.canPm.range.yes'))) {
            return true;
        }
        return false;
    }
  
}

?>