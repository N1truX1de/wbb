<?php

use wcf\system\WCF;
use wcf\system\user\authentication\DefaultUserAuthentication;

defined('MBQ_IN_IT') or exit;

define('MBQ_DS', DIRECTORY_SEPARATOR);
define('MBQ_PATH', dirname($_SERVER['SCRIPT_FILENAME']).MBQ_DS);    /* mobiquo path */
define('MBQ_DIRNAME', basename(MBQ_PATH));    /* mobiquo dir name */
define('MBQ_PARENT_PATH', substr(MBQ_PATH, 0, strrpos(MBQ_PATH, MBQ_DIRNAME.MBQ_DS)));    /* mobiquo parent dir path */
define('MBQ_FRAME_PATH', MBQ_PATH.'mbqFrame'.MBQ_DS);    /* frame path */
require_once(MBQ_FRAME_PATH.'MbqBaseConfig.php');

$_SERVER['SCRIPT_FILENAME'] = str_replace(MBQ_DIRNAME.'/', '', $_SERVER['SCRIPT_FILENAME']);  /* Important!!! */
$_SERVER['PHP_SELF'] = str_replace(MBQ_DIRNAME.'/', '', $_SERVER['PHP_SELF']);  /* Important!!! */
$_SERVER['SCRIPT_NAME'] = str_replace(MBQ_DIRNAME.'/', '', $_SERVER['SCRIPT_NAME']);    /* Important!!! */
$_SERVER['REQUEST_URI'] = str_replace(MBQ_DIRNAME.'/', '', $_SERVER['REQUEST_URI']);    /* Important!!! */

/**
 * plugin config
 * 
 * @since  2012-7-2
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqConfig extends MbqBaseConfig {

    public function __construct() {
        parent::__construct();
        require_once(MBQ_CUSTOM_PATH.'customDetectJs.php');
        $this->initCfg();
    }
    
    /**
     * init cfg default value
     */
    protected function initCfg() {
        parent::initCfg();
    }
    
    /**
     * calculate the final config of $this->cfg through $this->cfg default value and MbqMain::$customConfig and MbqMain::$oMbqAppEnv and the plugin support degree
     */
    public function calCfg() {
        $url = WCF::getPath();
        MbqMain::$oMbqAppEnv->siteRootUrl = substr($url, 0, strlen($url) - 4);
        //init user
        $oUser = DefaultUserAuthentication::getInstance()->loginAutomatically(true);
        if ($oUser && $oUser->userID) {
            MbqMain::$oMbqAppEnv->oCurrentUser = $oUser;
            $oMbqRdEtUser = MbqMain::$oClk->newObj('MbqRdEtUser');
            $oMbqRdEtUser->initOCurMbqEtUser();
        }
        if (MbqMain::hasLogin()) {  //!!!
            header('Mobiquo_is_login: true');
        } else {
            header('Mobiquo_is_login: false');
        }
        $oMbqRdEtForum = MbqMain::$oClk->newObj('MbqRdEtForum');
        MbqMain::$oMbqAppEnv->exttForumTree = $oMbqRdEtForum->getForumTree();  //!!!
        parent::calCfg();
      /* calculate the final config */
        $this->cfg['base']['sys_version']->setOriValue(PACKAGE_VERSION);
        if (OFFLINE) {
            $this->cfg['base']['is_open']->setOriValue(MbqBaseFdt::getFdt('MbqFdtConfig.base.is_open.range.no'));
        } else {
            $this->cfg['base']['is_open']->setOriValue(MbqBaseFdt::getFdt('MbqFdtConfig.base.is_open.range.yes'));
        }
        if (!MODULE_CONVERSATION || !WCF::getSession()->getPermission('user.conversation.canUseConversation')) {
            $this->cfg['pc']['module_enable']->setOriValue(MbqBaseFdt::getFdt('MbqFdtConfig.pc.module_enable.range.disable'));
            $this->cfg['pc']['conversation']->setOriValue(MbqBaseFdt::getFdt('MbqFdtConfig.pc.conversation.range.notSupport'));
        }
    }
    
}

?>