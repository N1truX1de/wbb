<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseAclEtPcMsg');

/**
 * private conversation message acl class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAclEtPcMsg extends MbqBaseAclEtPcMsg {
    
    public function __construct() {
    }
    
    /**
     * judge can reply_conversation
     *
     * @param  Object  $oMbqEtPcMsg
     * @param  Obejct  $oMbqEtPc
     * @return  Boolean
     */
    public function canAclReplyConversation($oMbqEtPcMsg, $oMbqEtPc) {
        if (MbqMain::hasLogin() && (strlen(trim($oMbqEtPcMsg->msgContent->oriValue)) > 0) && ($oMbqEtPcMsg->convId->oriValue == $oMbqEtPc->convId->oriValue) && !$oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject()->isClosed && $oMbqEtPc->mbqBind['oViewableConversation']->getDecoratedObject()->canRead()) {
            return true;
        }
        return false;
    }
    
    /**
     * judge can get_quote_conversation
     *
     * @param  Object  $oMbqEtPcMsg
     * @param  Obejct  $oMbqEtPc
     * @return  Boolean
     */
    public function canAclGetQuoteConversation($oMbqEtPcMsg, $oMbqEtPc) {
        return $this->canAclReplyConversation($oMbqEtPcMsg, $oMbqEtPc);
    }
  
}

?>