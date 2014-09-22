<?php

use wcf\data\user\online\UsersOnlineList;
use wcf\data\option\OptionAction;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtSysStatistics');

/**
 * system statistics read class
 * 
 * @since  2012-9-13
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtSysStatistics extends MbqBaseRdEtSysStatistics {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtSysStatistics = null, $pName = null, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * init system statistics by condition
     *
     * @return  Object
     */
    public function initOMbqEtSysStatistics() {
        $oMbqEtSysStatistics = MbqMain::$oClk->newObj('MbqEtSysStatistics');
        //ref wbb\page\BoardListPage::readData(),wcf\data\user\online\UsersOnlineList
		if (MODULE_USERS_ONLINE && WBB_INDEX_ENABLE_ONLINE_LIST) {
			$usersOnlineList = new UsersOnlineList();
			$usersOnlineList->readStats();
			$usersOnlineList->getConditionBuilder()->add('session.userID IS NOT NULL');
			$usersOnlineList->readObjects();
			// check users online record
			$usersOnlineTotal = (defined('WBB_USERS_ONLINE_RECORD_NO_GUESTS') ? $usersOnlineList->stats['members'] : $usersOnlineList->stats['total']);
			if (!defined('WBB_USERS_ONLINE_RECORD') || $usersOnlineTotal > WBB_USERS_ONLINE_RECORD) {
				// save new record
				$optionAction = new OptionAction(array(), 'import', array('data' => array(
					'wbb_users_online_record' => $usersOnlineTotal,
					'wbb_users_online_record_time' => TIME_NOW
				)));
				$optionAction->executeAction();
			}
            $oMbqEtSysStatistics->forumTotalOnline->setOriValue($usersOnlineList->stats['total']);
            $oMbqEtSysStatistics->forumGuestOnline->setOriValue($usersOnlineList->stats['guests']);
		} else {
            $oMbqEtSysStatistics->forumTotalOnline->setOriValue(0);
            $oMbqEtSysStatistics->forumGuestOnline->setOriValue(0);
		}
        return $oMbqEtSysStatistics;
    }
  
}

?>