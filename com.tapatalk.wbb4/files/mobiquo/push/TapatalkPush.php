<?php

use wcf\system\WCF;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wbb\data\thread\ViewableThreadList;
use wbb\data\post\ViewablePostList;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\data\conversation\UserConversationList;
use wcf\data\conversation\message\ViewableConversationMessageList;

define('MBQ_PUSH_BLOCK_TIME', 60);    /* push block time(minutes) */

require_once(dirname(__FILE__).'/../mbqFrame/basePush/TapatalkBasePush.php');   //this sentence only used for push feature of native plugin

/**
 * push class
 * 
 * @since  2014-3-18
 * @author Wu ZeTao <578014287@qq.com>
 */
Class TapatalkPush extends TapatalkBasePush {
    
    //native properties
    protected $oDb;
    protected $oUser;  //current user object if loged in
    
    //init
    public function __construct() {
        parent::__construct();
        
        $this->oDb = WCF::getDB();
        
        $oUser = WCF::getUser();
        if ($oUser && $oUser->userID) {
            $this->oUser = $oUser;
        }
        $this->loadImActive();
        
        $this->loadPushStatus();
        $this->loadSupportedPushType();
        $this->loadSlug();
        
        $this->siteUrl = WCF::getPath('wbb');
    }
    
    /**
     * load $this->supportedPushType
     */
    protected function loadSupportedPushType() {
        if (MbqCommonConfig::$cfg['push_type']) {
            $this->supportedPushType = explode(',', MbqCommonConfig::$cfg['push_type']);
        }
    }
    
    /**
     * load $this->imActive
     *
     * @return Boolean
     */
    protected function loadImActive() {
        if ($this->oUser && $this->getActiveAppUserIds($this->oUser->userID)) {
            $this->imActive = true;
        } else {
            $this->imActive = false;
        }
    }
    
    /**
     * filter active user id from tapatalk_push_user table
     *
     * @param  Mixed  user id(integer) or user ids(array)
     * @return  Array  return empty array when get error,or return active user ids array
     */
    protected function getActiveAppUserIds($var) {
        if (!is_array($var)) $var = array($var);
        $conditionBuilder = new PreparedStatementConditionBuilder();    //!!!
        $conditionBuilder->add('user_id IN (?)', array($var));
        $query ="SELECT user_id FROM wbb".WCF_N."_tapatalk_push_user ".$conditionBuilder;
		$statement = $this->oDb->prepareStatement($query);
		$statement->execute($conditionBuilder->getParameters());
		if ($this->findDbError()) return array();
		$ret = array();
		while ($r = $statement->fetchArray()) {
            $ret[] = $r['user_id'];
        }
        return $ret;
    }
    
    /**
     * load $this->pushStatus and $this->pushKey
     */
    protected function loadPushStatus() {
        $this->pushStatus = false;
        if (MbqCommonConfig::$cfg['push'] && !OFFLINE && (@ini_get('allow_url_fopen') || function_exists('curl_init'))) {
            $this->pushKey = WBB_TAPATALK_API_KEY;
            $this->pushStatus = true;
        }
    }
    
    /**
     * judge db error
     *
     * @return  Boolean
     */
    protected function findDbError() {
        return false;   //TODO
        if ($this->oDb->getErrorNumber()) {
            $this->errMsg = 'Db error occured.'.$this->oDb->getErrorDesc();
            return true;
        }
        return false;
    }
    
    /**
     * save slug
     *
     * @param  Mixed $slug
     * @return Boolean
     */
    protected function saveSlug($slug = NULL) {
        if (is_null($slug)) {
            $data = json_encode($this->slugData);
        } else {
            $this->slugData = $slug;
            $data = json_encode($slug);
        }
        $query ="SELECT count(update_time) as num FROM wbb".WCF_N."_tapatalk_status";
		$statement = $this->oDb->prepareStatement($query);
		$statement->execute();
		$r = $statement->fetchArray();
		if ($this->findDbError()) return false;
		if ($r['num'] == 1) {
            $query = "UPDATE wbb".WCF_N."_tapatalk_status SET update_time = ?, status_info = ?";
            $statement = $this->oDb->prepareStatement($query);
            $statement->execute(array(time(), $data));
		} elseif ($r['num'] == 0) {
		    $query = "INSERT INTO wbb".WCF_N."_tapatalk_status (status_info, create_time, update_time) VALUES (?, ?, ?)";
		    $statement = $this->oDb->prepareStatement($query);
            $statement->execute(array($data, time(), time()));
		} else {
		    return false;
		}
        if ($this->findDbError()) 
            return false;
        else
            return true;
    }
    
    /**
     * load $this->slugData
     *
     * @return Boolean
     */
    protected function loadSlug() {
        $query ="SELECT * FROM wbb".WCF_N."_tapatalk_status LIMIT 1";
		$statement = $this->oDb->prepareStatement($query);
		$statement->execute();
		$r = $statement->fetchArray();
		if ($this->findDbError()) return false;
		if ($r) {
            $this->slugData = json_decode($r['status_info']);
		} else {
		    $this->slugData = array();  //default is empty array
		}
		return true;
    } 
    
    /**
     * wrap push data before process push
     *
     * @param  Array  $push_data
     */
    protected function push($push_data) {
        if (!empty($push_data)) {
            foreach ($push_data as $pack) {
                if (!in_array($pack['type'], $this->supportedPushType)) {
                    return false;
                }
            }
            $data = array(
                'url'  => $this->siteUrl,
                'key'  => $this->pushKey,
                'data' => base64_encode(serialize($push_data)),
            );
            if($this->pushStatus)
                $this->do_post_request($data);
        }
    }
    
    protected function do_post_request($data) {
        $push_url = 'http://push.tapatalk.com/push.php';

        //Get push_slug from db
        if ($this->loadSlug()) 
            $slug = $this->slugData;
        else 
            return false;
        $slug = $this->push_slug($slug, 'CHECK');

        //If it is valide(result = true) and it is not sticked, we try to send push
        if($slug[2] && !$slug[5])
        {
            //Slug is initialed or just be cleared
            if($slug[8])
            {
                $this->saveSlug($slug);
            }

            //Send push
            $push_resp = $this->getContentFromRemoteServer($push_url, 0, $this->errMsg, 'POST', $data);

            if(trim($push_resp) === 'Invalid push notification key') $push_resp = 1;
            if(!is_numeric($push_resp) && !preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $push_resp))
            {
                //Sending push failed, try to update push_slug to db
                $slug = $this->push_slug($slug, 'UPDATE');

                if($slug[2] && $slug[8])
                {
                    $this->saveSlug($slug);
                }
            }
        }

        return $push_resp;
    }
    
    protected function push_slug($push_v_data, $method = 'NEW') {
        if(empty($push_v_data))
            $push_v_data = array();

        $current_time = time();
        if(!is_array($push_v_data))
            return array(2 => 0, 3 => 'Invalid v data', 5 => 0);
        if($method != 'CHECK' && $method != 'UPDATE' && $method != 'NEW')
            return array(2 => 0, 3 => 'Invalid method', 5 => 0);

        if($method != 'NEW' && !empty($push_v_data))
        {
            $push_v_data[8] = $method == 'UPDATE';
            if($push_v_data[5] == 1)
            {
                if($push_v_data[6] + $push_v_data[7] > $current_time)
                    return $push_v_data;
                else
                    $method = 'NEW';
            }
        }

        if($method == 'NEW' || empty($push_v_data))
        {
            $push_v_data = array();     //Slug
            $push_v_data[0] = 3;        //        $push_v_data['max_times'] = 3;                //max push failed attempt times in period
            $push_v_data[1] = 300;      //        $push_v_data['max_times_in_period'] = 300;     //the limitation period
            $push_v_data[2] = 1;        //        $push_v_data['result'] = 1;                   //indicate if the output is valid of not
            $push_v_data[3] = '';       //        $push_v_data['result_text'] = '';             //invalid reason
            $push_v_data[4] = array();  //        $push_v_data['stick_time_queue'] = array();   //failed attempt timestamps
            $push_v_data[5] = 0;        //        $push_v_data['stick'] = 0;                    //indicate if push attempt is allowed
            $push_v_data[6] = 0;        //        $push_v_data['stick_timestamp'] = 0;          //when did push be sticked
            $push_v_data[7] = 600;      //        $push_v_data['stick_time'] = 600;             //how long will it be sticked
            $push_v_data[8] = 1;        //        $push_v_data['save'] = 1;                     //indicate if you need to save the slug into db
            return $push_v_data;
        }

        if($method == 'UPDATE')
        {
            $push_v_data[4][] = $current_time;
        }
        $sizeof_queue = count($push_v_data[4]);

        $period_queue = $sizeof_queue > 1 ? ($push_v_data[4][$sizeof_queue - 1] - $push_v_data[4][0]) : 0;

        $times_overflow = $sizeof_queue > $push_v_data[0];
        $period_overflow = $period_queue > $push_v_data[1];

        if($period_overflow)
        {
            if(!array_shift($push_v_data[4]))
                $push_v_data[4] = array();
        }

        if($times_overflow && !$period_overflow)
        {
            $push_v_data[5] = 1;
            $push_v_data[6] = $current_time;
        }

        return $push_v_data;
    }
    
    /**
     * Get content from remote server
     *
     * @param string $url      NOT NULL          the url of remote server, if the method is GET, the full url should include parameters; if the method is POST, the file direcotry should be given.
     * @param string $holdTime [default 0]       the hold time for the request, if holdtime is 0, the request would be sent and despite response.
     * @param string $error_msg                  return error message
     * @param string $method   [default GET]     the method of request.
     * @param string $data     [default array()] post data when method is POST.
     *
     * @exmaple: getContentFromRemoteServer('http://push.tapatalk.com/push.php', 0, $error_msg, 'POST', $ttp_post_data)
     * @return string when get content successfully|false when the parameter is invalid or connection failed.
    */
    protected function getContentFromRemoteServer($url, $holdTime = 0, &$error_msg, $method = 'GET', $data = array()) {
        //Validate input.
        $vurl = parse_url($url);
        if ($vurl['scheme'] != 'http')
        {
            $error_msg = 'Error: invalid url given: '.$url;
            return false;
        }
        if($method != 'GET' && $method != 'POST')
        {
            $error_msg = 'Error: invalid method: '.$method;
            return false;//Only POST/GET supported.
        }
        if($method == 'POST' && empty($data))
        {
            $error_msg = 'Error: data could not be empty when method is POST';
            return false;//POST info not enough.
        }

        if(!empty($holdTime) && function_exists('file_get_contents') && $method == 'GET')
        {
            $response = @file_get_contents($url);
        }
        else if (@ini_get('allow_url_fopen'))
        {
            if(empty($holdTime))
            {
                // extract host and path:
                $host = $vurl['host'];
                $path = $vurl['path'];

                if($method == 'POST')
                {
                    $fp = @fsockopen($host, 80, $errno, $errstr, 5);

                    if(!$fp)
                    {
                        $error_msg = 'Error: socket open time out or cannot connect.';
                        return false;
                    }

                    $data =  http_build_query($data);

                    fputs($fp, "POST $path HTTP/1.1\r\n");
                    fputs($fp, "Host: $host\r\n");
                    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
                    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
                    fputs($fp, "Connection: close\r\n\r\n");
                    fputs($fp, $data);
                    fclose($fp);
                    return 1;
                }
                else
                {
                    $error_msg = 'Error: 0 hold time for get method not supported.';
                    return false;
                }
            }
            else
            {
                if($method == 'POST')
                {
                    $params = array('http' => array(
                        'method' => 'POST',
                        'content' => http_build_query($data, '', '&'),
                    ));
                    $ctx = stream_context_create($params);
                    $old = ini_set('default_socket_timeout', $holdTime);
                    $fp = @fopen($url, 'rb', false, $ctx);
                }
                else
                {
                    $fp = @fopen($url, 'rb', false);
                }
                if (!$fp)
                {
                    $error_msg = 'Error: fopen failed.';
                    return false;
                }
                ini_set('default_socket_timeout', $old);
                stream_set_timeout($fp, $holdTime);
                stream_set_blocking($fp, 0);

                $response = @stream_get_contents($fp);
            }
        }
        elseif (function_exists('curl_init'))
        {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            if($method == 'POST')
            {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            if(empty($holdTime))
            {
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT,1);
            }
            $response = curl_exec($ch);
            curl_close($ch);
        }
        else
        {
            $error_msg = 'CURL is disabled and PHP option "allow_url_fopen" is OFF. You can enable CURL or turn on "allow_url_fopen" in php.ini to fix this problem.';
            return false;
        }
        return $response;
    }
    
    /**
     * record user info after login from app
     *
     * @return Boolean
     */
    protected function doAfterAppLogin() {
        if ($this->oUser->userID && $this->pushStatus) {
            $query ="SELECT count(user_id) as num FROM wbb".WCF_N."_tapatalk_push_user WHERE user_id = ?";
    		$statement = $this->oDb->prepareStatement($query);
		    $statement->execute(array($this->oUser->userID));
			$r = $statement->fetchArray();
			if ($this->findDbError()) return false;
			if ($r['num'] == 1) {
                $query = "UPDATE wbb".WCF_N."_tapatalk_push_user SET update_time = ? WHERE user_id = ?";
                $statement = $this->oDb->prepareStatement($query);
                $statement->execute(array(time(), $this->oUser->userID));
			} elseif ($r['num'] == 0) {
			    $query = "INSERT INTO wbb".WCF_N."_tapatalk_push_user (user_id, create_time, update_time) VALUES (?, ?, ?)";
			    $statement = $this->oDb->prepareStatement($query);
                $statement->execute(array($this->oUser->userID, time(), time()));
			} else {
			    return false;
			}
            if (!$this->findDbError()) return true;
        }
        return false;
    }
    
    /**
     * new topic push
     *
     * @param  Array  $p
     * @return Boolean
     */
    protected function doPushNewTopic($p) {
        $push_data = array();
        $objsUser = array();
        if (defined('MBQ_IN_IT') && MBQ_IN_IT) {    //mobiquo
            $objsUser = $this->getUsersByTag($p['oMbqEtForumTopic']->topicContent->oriValue);
            $topicId = $p['oMbqEtForumTopic']->topicId->oriValue;
            $postId = $oThread->firstPostID;
        } else {    //native plugin
            $postId = $p['postId'];
            $oPost = $this->getPostByPostId($postId);
            if ($oPost) {
                $objsUser = $this->getUsersByTag($oPost->message);
                $topicId = $oPost->threadID;
            } else {
                return false;
            }
        }
        $oThread = $this->getTopicByTopicId($topicId);
		//send tag push
		if ($objsUser && $oThread) {
	        //can send push
	        foreach ($objsUser as $oUser) {
                $pushPack = array(
                    'userid'    => $oUser->userID,
                    'type'      => 'tag',
                    'id'        => $topicId,
                    'subid'     => $postId,
                    'title'     => $oThread->getTitle(),
                    'author'    => $this->oUser->username,
                    'dateline'  => time()
                );
                $push_data[] = $pushPack;
	        }
            $this->push($push_data);
		}
        return false;
    }
    
    /**
     * reply push
     *
     * @param  Array  $p
     * @return Boolean
     */
    protected function doPushReply($p) {
        $push_data = array();
        if (defined('MBQ_IN_IT') && MBQ_IN_IT) {    //mobiquo
            $topicId = $p['oMbqEtForumPost']->topicId->oriValue;
            $postId = $p['oMbqEtForumPost']->postId->oriValue;
        } else {    //native plugin
            if (isset($p['oThread'])) { //more options reply
                $topicId = $p['oThread']->threadID;
                $postId = $p['oThread']->lastPostID;
            } elseif (isset($p['oPost'])) { //quick reply
                $topicId = $p['oPost']->threadID;
                $postId = $p['oPost']->postID;
            } else {
                return false;
            }
        }
        $query ="SELECT userID FROM wcf".WCF_N."_user_object_watch WHERE objectTypeID = (SELECT objectTypeID FROM wbb".WCF_N."_object_type WHERE objectType = 'com.woltlab.wbb.thread') and objectID = ?";
		$statement = $this->oDb->prepareStatement($query);
	    $statement->execute(array($topicId));
	    $userIds = array();
		while ($r = $statement->fetchArray()) {
		    $userIds[] = $r['userID'];
		}
		$objsUser = $this->getUsersByUserIdsExceptMe($userIds);
		$oThread = $this->getTopicByTopicId($topicId);
		$oPost = $this->getPostByPostId($postId);
		//send sub push
		if ($objsUser && $oThread && $oPost) {
	        //can send push
	        foreach ($objsUser as $oUser) {
                $pushPack = array(
                    'userid'    => $oUser->userID,
                    'type'      => 'sub',
                    'id'        => $topicId,
                    'subid'     => $postId,
                    'title'     => $oPost->subject ? $oPost->subject : $oThread->getTitle(),
                    'author'    => $this->oUser->username,
                    'dateline'  => time()
                );
                $push_data[] = $pushPack;
	        }
            $this->push($push_data);
		}
		if ($oThread && $oPost) {
		    $objsUser = $this->getUsersByTag($oPost->message);
		    if ($objsUser) {    //send tag push
		        //can send push
    	        foreach ($objsUser as $oUser) {
                    $pushPack = array(
                        'userid'    => $oUser->userID,
                        'type'      => 'tag',
                        'id'        => $topicId,
                        'subid'     => $postId,
                        'title'     => $oPost->subject ? $oPost->subject : $oThread->getTitle(),
                        'author'    => $this->oUser->username,
                        'dateline'  => time()
                    );
                    $push_data[] = $pushPack;
    	        }
                $this->push($push_data);
		    }
		    $objsUser = $this->getUsersByQuote($oPost->message);
		    if ($objsUser) {    //send quote push
		        //can send push
    	        foreach ($objsUser as $oUser) {
                    $pushPack = array(
                        'userid'    => $oUser->userID,
                        'type'      => 'quote',
                        'id'        => $topicId,
                        'subid'     => $postId,
                        'title'     => $oPost->subject ? $oPost->subject : $oThread->getTitle(),
                        'author'    => $this->oUser->username,
                        'dateline'  => time()
                    );
                    $push_data[] = $pushPack;
    	        }
                $this->push($push_data);
		    }
		}
        return false;
    }
    
    /**
     * new conversation push
     *
     * @param  Array  $p
     * @return Boolean
     */
    protected function doPushNewConversation($p) {
        $push_data = array();
        if (defined('MBQ_IN_IT') && MBQ_IN_IT) {    //mobiquo
            $convId = $p['oMbqEtPc']->convId->oriValue;
            $position = 1;
            $title = $p['oMbqEtPc']->convTitle->oriValue;
        } else {    //native plugin
            $convId = $p['convId'];
            $position = 1;
            $title = $p['title'];
        }
        if ($oConversation = $this->getConversationByConvId($convId)) {
            $userIdsParticipant = $oConversation->getParticipantIDs();
            $objsUser = $this->getUsersByUserIdsExceptMe($userIdsParticipant);
            if ($objsUser) {
        		//send conv push
		        foreach ($objsUser as $oUser) {
                    $pushPack = array(
                        'userid'    => $oUser->userID,
                        'type'      => 'conv',
                        'id'        => $convId,
                        'subid'     => $position,
                        'title'     => $title,
                        'author'    => $this->oUser->username,
                        'dateline'  => time()
                    );
                    $push_data[] = $pushPack;
		        }
                $this->push($push_data);
            }
        }
        return false;
    }
    
    /**
     * reply conversation push
     *
     * @param  Array  $p
     * @return Boolean
     */
    protected function doPushReplyConversation($p) {
        $push_data = array();
        if (defined('MBQ_IN_IT') && MBQ_IN_IT) {    //mobiquo
            $convId = $p['oMbqEtPc']->convId->oriValue;
            $msgId = $p['oMbqEtPcMsg']->msgId->oriValue;
        } else {    //native plugin
            $convId = $p['convId'];
            $msgId = $p['msgId'];
        }
        if (($oConversation = $this->getConversationByConvId($convId)) && ($oConversationMessage = $this->getConversationMessageByMsgId($msgId))) {
            $title = $oConversation->subject;
            $position = $this->getPcMsgPosition($oConversationMessage);
            $userIdsParticipant = $oConversation->getParticipantIDs();
            $objsUser = $this->getUsersByUserIdsExceptMe($userIdsParticipant);
            if ($objsUser) {
        		//send conv push
		        foreach ($objsUser as $oUser) {
                    $pushPack = array(
                        'userid'    => $oUser->userID,
                        'type'      => 'conv',
                        'id'        => $convId,
                        'subid'     => $position,
                        'title'     => $title,
                        'author'    => $this->oUser->username,
                        'dateline'  => time()
                    );
                    $push_data[] = $pushPack;
		        }
                $this->push($push_data);
            }
        }
        return false;
    }
    
    /**
     * get users by tag
     *
     * @param  String  $content
     * @return  Array
     */
    protected function getUsersByTag($content) {
        $objsUser = array();
        $str =  preg_match_all('/\[url=[^\]]*?\]@([^\[]*?)\[\/url\]/i', $content, $matches);
        if ($matches && isset($matches[1]) && $matches[1]) {
            $objsUser = $this->getUsersByUserLoginNamesExceptMe($matches[1]);
        }
        return $objsUser;
    }
    
    /**
     * get users by quote
     *
     * @param  String  $content
     * @return  Array
     */
    protected function getUsersByQuote($content) {
        $objsUser = array();
        $str =  preg_match_all('/\[quote=\'([^\]]*?)\'[^\]]*?\]/i', $content, $matches);
        if ($matches && isset($matches[1]) && $matches[1]) {
            $objsUser = $this->getUsersByUserLoginNamesExceptMe($matches[1]);
        }
        return $objsUser;
    }
    
    /**
     * get users by user ids except me
     *
     * @param  Array  $userIds
     * @return  Array
     */
    protected function getUsersByUserIdsExceptMe($userIds) {
        $objsUserProfile = UserProfile::getUserProfiles($userIds);
        $objsUser = array();
        foreach ($objsUserProfile as $oUserProfile) {
            $oUser = $oUserProfile->getDecoratedObject();
            if ($oUser->userID && $oUser->userID != $this->oUser->userID) {
                $objsUser[] = $oUser;
            }
        }
        return $objsUser;
    }
    
    /**
     * get users by user ids
     *
     * @param  Array  $userIds
     * @return  Array
     */
    protected function getUsersByUserIds($userIds) {
        $objsUserProfile = UserProfile::getUserProfiles($userIds);
        $objsUser = array();
        foreach ($objsUserProfile as $oUserProfile) {
            $oUser = $oUserProfile->getDecoratedObject();
            if ($oUser->userID) {
                $objsUser[] = $oUser;
            }
        }
        return $objsUser;
    }
    
    /**
     * get users by user login names except me
     *
     * @param  Array  $loginNames
     * @return  Array
     */
    protected function getUsersByUserLoginNamesExceptMe($loginNames) {
        $objsUserProfile = UserProfile::getUserProfilesByUsername($loginNames);
        $objsUser = array();
        foreach ($objsUserProfile as $oUserProfile) {
            $oUser = $oUserProfile->getDecoratedObject();
            if ($oUser->userID && $oUser->userID != $this->oUser->userID) {
                $objsUser[] = $oUser;
            }
        }
        return $objsUser;
    }
    
    /**
     * get topic by topic id
     *
     * @param  Integer  $topicId
     * @return Mixed
     */
    protected function getTopicByTopicId($topicId) {
		$oViewableThreadList = new ViewableThreadList();
		$oViewableThreadList->setObjectIDs(array($topicId));
		$oViewableThreadList->readObjects();
		$objsViewableThread = $oViewableThreadList->getObjects();
		if ($objsViewableThread && ($oViewableThread = array_shift($objsViewableThread)) && ($oThread = $oViewableThread->getDecoratedObject()) && $oThread->threadID) {
		    return $oThread;
		} else {
		    return false;
		}
    }
    
    /**
     * get post by post id
     *
     * @param  Integer  $postId
     * @return Mixed
     */
    protected function getPostByPostId($postId) {
        $oViewablePostList = new ViewablePostList();
		$oViewablePostList->setObjectIDs(array($postId));
		$oViewablePostList->readObjects();
		$objsViewablePost = $oViewablePostList->getObjects();
		if ($objsViewablePost && ($oViewablePost = array_shift($objsViewablePost)) && ($oPost = $oViewablePost->getDecoratedObject()) && $oPost->postID) {
		    return $oPost;
		} else {
		    return false;
		}
    }
    
    /**
     * get conversation by conv id
     *
     * @param  Integer  $convId
     * @return Mixed
     */
    protected function getConversationByConvId($convId) {
        $oUserConversationList = new UserConversationList(WCF::getUser()->userID, '', 0);
		$oUserConversationList->setObjectIDs(array($convId));
		$oUserConversationList->readObjects();
		$objsViewableConversation = $oUserConversationList->getObjects();
		if ($objsViewableConversation && ($oViewableConversation = array_shift($objsViewableConversation)) && ($oConversation = $oViewableConversation->getDecoratedObject()) && $oConversation->conversationID) {
		    return $oConversation;
		} else {
		    return false;
		}
    }
    
    /**
     * get conversation message by msg id
     *
     * @param  Integer  $msgId
     * @return Mixed
     */
    protected function getConversationMessageByMsgId($msgId) {
        $oViewableConversationMessageList = new ViewableConversationMessageList();
		$oViewableConversationMessageList->setObjectIDs(array($msgId));
		$oViewableConversationMessageList->readObjects();
		$objsViewableConversationMessage = $oViewableConversationMessageList->getObjects();
		if ($objsViewableConversationMessage && ($oViewableConversationMessage = array_shift($objsViewableConversationMessage)) && ($oConversationMessage = $oViewableConversationMessage->getDecoratedObject()) && $oConversationMessage->messageID) {
		    return $oConversationMessage;
		} else {
		    return false;
		}
    }
    
    /**
     * get conversation message position
     *
     * @param  Object  $oConversationMessage
     *
     * @return  Integer
     */
    protected function getPcMsgPosition($oConversationMessage) {
        //refer MbqRdEtPcMsg::getObjsMbqEtPcMsg, $mbqOpt['case'] == 'byPc'
        $oViewableConversationMessageList = new ViewableConversationMessageList();
        $oViewableConversationMessageList->sqlOffset = 0;
        $oViewableConversationMessageList->sqlLimit = 1000000;  //get all the conversation message to use
        $oViewableConversationMessageList->getConditionBuilder()->add('conversation_message.conversationID = ?', array($oConversationMessage->conversationID));
        $oViewableConversationMessageList->readObjects();
        $objsViewableConversationMessage = $oViewableConversationMessageList->getObjects();
        $ret = 1;
        foreach ($objsViewableConversationMessage as $oViewableConversationMessage) {
            if ($oViewableConversationMessage->getDecoratedObject()->messageID == $oConversationMessage->messageID) {
                return $ret;
            }
            $ret ++;
        }
        //not found
        return 1;
    }
    
}

?>
