<?php

use wcf\data\user\UserProfile;
use wcf\system\user\authentication\UserAuthenticationFactory;
use wcf\system\user\authentication\EmailUserAuthentication;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtUser');

/**
 * user read class
 * 
 * @since  2012-8-6
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtUser extends MbqBaseRdEtUser {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtUser, $pName, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get user objs
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byUserIds' means get data by user ids.$var is the ids.
     * @return  Array
     */
    public function getObjsMbqEtUser($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'byUserIds') {
            $objsUserProfile = UserProfile::getUserProfiles($var);
            $objsMbqEtUser = array();
            foreach ($objsUserProfile as $oUserProfile) {
                $objsMbqEtUser[] = $this->initOMbqEtUser($oUserProfile, array('case' => 'oUserProfile'));
            }
            return $objsMbqEtUser;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one user by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'oUserProfile' means init user by oUserProfile.$var is oUserProfile.
     * $mbqOpt['case'] = 'byUserId' means init user by user id.$var is user id.
     * $mbqOpt['case'] = 'byLoginName' means init user by login name.$var is login name.
     * @return  Mixed
     */
    public function initOMbqEtUser($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'oUserProfile') {
            $oMbqEtUser = MbqMain::$oClk->newObj('MbqEtUser');
            $oUser = $var->getDecoratedObject();
            $oMbqEtUser->userId->setOriValue($oUser->userID);
            $oMbqEtUser->loginName->setOriValue($oUser->username);
            $oMbqEtUser->userName->setOriValue($oUser->username);
            $oMbqEtUser->userGroupIds->setOriValue($oUser->getGroupIDs());
            $oMbqEtUser->iconUrl->setOriValue($var->getAvatar()->getURL());
            $oMbqEtUser->postCount->setOriValue($oUser->wbbPosts);
            $oMbqEtUser->canSearch->setOriValue(MbqBaseFdt::getFdt('MbqFdtUser.MbqEtUser.canSearch.range.yes'));
            $oMbqEtUser->canWhosonline->setOriValue(MbqBaseFdt::getFdt('MbqFdtUser.MbqEtUser.canWhosonline.range.yes'));
            $oMbqEtUser->regTime->setOriValue($oUser->registrationDate);
            $oMbqEtUser->lastActivityTime->setOriValue($oUser->lastActivityTime);
            if ($var->isOnline()) {
                $oMbqEtUser->isOnline->setOriValue(MbqBaseFdt::getFdt('MbqFdtUser.MbqEtUser.isOnline.range.yes'));
            } else {
                $oMbqEtUser->isOnline->setOriValue(MbqBaseFdt::getFdt('MbqFdtUser.MbqEtUser.isOnline.range.no'));
            }
            $oMbqEtUser->maxAttachment->setOriValue(10);    //todo,hard code
            $oMbqEtUser->maxPngSize->setOriValue(1024 * 1024);     //todo,hard code
            $oMbqEtUser->maxJpgSize->setOriValue(1024 * 1024);      //todo,hard code
            $oMbqEtUser->mbqBind['oUserProfile'] = $var;
            return $oMbqEtUser;
        } elseif ($mbqOpt['case'] == 'byUserId') {
            $userIds = array($var);
            $objsMbqEtUser = $this->getObjsMbqEtUser($userIds, array('case' => 'byUserIds'));
            if (is_array($objsMbqEtUser) && (count($objsMbqEtUser) == 1)) {
                return $objsMbqEtUser[0];
            }
            return false;
        } elseif ($mbqOpt['case'] == 'byLoginName') {
            $oUserProfile = UserProfile::getUserProfileByUsername($var);
            if ($oUserProfile) {
                return $this->initOMbqEtUser($oUserProfile, array('case' => 'oUserProfile'));
            }
            return false;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * get user display name
     *
     * @param  Object  $oMbqEtUser
     * @return  String
     */
    public function getDisplayName($oMbqEtUser) {
        return $oMbqEtUser->loginName->oriValue;
    }
    
    /**
     * login
     *
     * @param  String  $loginName
     * @param  String  $password
     * @return  Boolean  return true when login success.
     */
    public function login($loginName, $password) {
        //ref wcf\acp\form\LoginForm::validateUser()
		try {
			$oUser = UserAuthenticationFactory::getInstance()->getUserAuthentication()->loginManually($loginName, $password);
		}
		catch (UserInputException $e) {
			if ($e->getField() == 'username') {
				try {
					$oUser = EmailUserAuthentication::getInstance()->loginManually($loginName, $password);
				}
				catch (UserInputException $e2) {
					//if ($e2->getField() == 'username') throw $e;
					//throw $e2;
					return false;
				}
			}
			else {
				//throw $e;
				return false;
			}
		}
		if (!$oUser || !$oUser->userID) return false;
		//ref wcf\form\LoginForm::save()
		// set cookies
		UserAuthenticationFactory::getInstance()->getUserAuthentication()->storeAccessData($oUser, $loginName, $password);
		// change user
		WCF::getSession()->changeUser($oUser);
		MbqMain::$oMbqAppEnv->oCurrentUser = $oUser;
        $this->initOCurMbqEtUser();
        return true;
    }
    
    /**
     * logout
     *
     * @return  Boolean  return true when logout success.
     */
    public function logout() {
        //ref wcf\action\LogoutAction::execute()
        // do logout
		WCF::getSession()->delete();
		
		// remove cookies
		if (isset($_COOKIE[COOKIE_PREFIX.'userID'])) {
			HeaderUtil::setCookie('userID', 0);
		}
		if (isset($_COOKIE[COOKIE_PREFIX.'password'])) {
			HeaderUtil::setCookie('password', '');
		}
        
        return true;
    }
    
    /**
     * init current user obj if login
     */
    public function initOCurMbqEtUser() {
        if (MbqMain::$oMbqAppEnv->oCurrentUser) {
            MbqMain::$oCurMbqEtUser = $this->initOMbqEtUser(MbqMain::$oMbqAppEnv->oCurrentUser->userID, array('case' => 'byUserId'));
        }
    }
  
}

?>