<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseAclEtAtt');

/**
 * attachment acl class
 * 
 * @since  2012-9-11
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAclEtAtt extends MbqBaseAclEtAtt {
    
    public function __construct() {
    }
    
    /**
     * judge can upload attachment
     *
     * @param  Object  $oMbqEtForum
     * @return  Boolean
     */
    public function canAclUploadAttach($oMbqEtForum) {
        return $oMbqEtForum->canUpload->oriValue;
    }
    
    /**
     * judge can remove attachment
     *
     * @param  Object  $oMbqEtAtt
     * @param  Object  $oMbqEtForum
     * @return  Boolean
     */
    public function canAclRemoveAttachment($oMbqEtAtt, $oMbqEtForum) {
        if ($oMbqEtAtt->mbqBind['oAttachment'] && $oMbqEtAtt->mbqBind['oAttachment']->canDelete()) {
            return true;
        }
        return false;
    }
  
}

?>