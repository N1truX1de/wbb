<?php

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseWrEtPc');

/**
 * private conversation write class
 * 
 * @since  2012-11-4
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqWrEtPc extends MbqBaseWrEtPc {
    
    public function __construct() {
    }
    
    /**
     * mark private conversation read
     *
     * @param  Object  $oMbqEtPc
     * @return  Mixed
     */
    public function markPcRead($oMbqEtPc) {
        //this has been done in MbqRdEtPcMsg::getObjsMbqEtPcMsg() with $mbqOpt['case'] == 'byPc',so need do nothing here.
    }
  
}

?>