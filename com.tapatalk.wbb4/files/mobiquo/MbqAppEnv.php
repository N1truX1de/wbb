<?php

defined('MBQ_IN_IT') or exit;

/**
 * application environment class
 * 
 * @since  2012-7-2
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqAppEnv extends MbqBaseAppEnv {
    
    /* this class fully relys on the application,so you can define the properties what you need come from the application. */
    public $exttForumTree;  //forum tree structure
    public $exttAllForums;  //all forums one dimensional array
    public $siteRootUrl;    //site root url,init it in MbqConfig::calCfg()
    public $oCurrentUser;   //wcf\data\user\User
    public $accessibleBoardIds;

    public function __construct() {
        parent::__construct();
        $this->exttForumTree = array();
        $this->exttAllForums = array();
        $this->accessibleBoardIds = array();
    }
    
    /**
     * application environment init
     */
    public function init() {
        /* modified from index.php */
        require_once('./global.php');
        //wcf\system\request\RequestHandler::getInstance()->handle('wbb');
        $oRequestHandler = wcf\system\request\RequestHandler::getInstance();
    }
    
}

?>