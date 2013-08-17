<?php

use wcf\data\attachment\GroupedAttachmentList;
use wbb\data\post\ViewablePostList;
use wcf\data\attachment\Attachment;

defined('MBQ_IN_IT') or exit;

MbqMain::$oClk->includeClass('MbqBaseRdEtAtt');

/**
 * attachment read class
 * 
 * @since  2012-8-14
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqRdEtAtt extends MbqBaseRdEtAtt {
    
    public function __construct() {
    }
    
    public function makeProperty(&$oMbqEtAtt, $pName, $mbqOpt = array()) {
        switch ($pName) {
            default:
            MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_PNAME . ':' . $pName . '.');
            break;
        }
    }
    
    /**
     * get attachment objs
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'byForumPostIds' means get data by forum post ids.$var is the ids.
     * @return  Mixed
     */
    public function getObjsMbqEtAtt($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'byForumPostIds') {
            $objsMbqEtAtt = array();
            //ref wbb\page\ThreadPage::readData(),wbb\data\post\ViewablePostList::readObjects(),wcf\data\attachment\GroupedAttachmentList::setPermissions()
            $oViewablePostList = new ViewablePostList();
    		$oViewablePostList->setObjectIDs($var);
    		$oViewablePostList->readObjects();
    		if ($oViewablePostList->getAttachmentList() && ($objsAttachment = $oViewablePostList->getAttachmentList()->getObjects())) {
        		foreach ($objsAttachment as $oAttachment) {
        		    $objsMbqEtAtt[] = $this->initOMbqEtAtt($oAttachment, array('case' => 'oAttachment'));
        		}
        	}
            return $objsMbqEtAtt;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
    
    /**
     * init one attachment by condition
     *
     * @param  Mixed  $var
     * @param  Array  $mbqOpt
     * $mbqOpt['case'] = 'oAttachment' means init attachment by oAttachment
     * $mbqOpt['case'] = 'byAttId' means init attachment by attachment id
     * @return  Mixed
     */
    public function initOMbqEtAtt($var, $mbqOpt) {
        if ($mbqOpt['case'] == 'byAttId') {
            $oAttachment = new Attachment($var);
            if ($oAttachment->attachmentID) {
                $mbqOpt['case'] = 'oAttachment';
                return $this->initOMbqEtAtt($oAttachment, $mbqOpt);
            }
            return false;
        } elseif ($mbqOpt['case'] == 'oAttachment') {
            $oMbqEtAtt = MbqMain::$oClk->newObj('MbqEtAtt');
            $oMbqEtAtt->attId->setOriValue($var->attachmentID);
            $oMbqEtAtt->postId->setOriValue($var->objectID);
            $oMbqEtAtt->filtersSize->setOriValue($var->filesize);
            $oMbqEtAtt->uploadFileName->setOriValue($var->filename);
            $ext = strtolower(MbqMain::$oMbqCm->getFileExtension($var->filename));
            if ($ext == 'jpeg' || $ext == 'gif' || $ext == 'bmp' || $ext == 'png' || $ext == 'jpg') {
                $contentType = MbqBaseFdt::getFdt('MbqFdtAtt.MbqEtAtt.contentType.range.image');
            } elseif ($ext == 'pdf') {
                $contentType = MbqBaseFdt::getFdt('MbqFdtAtt.MbqEtAtt.contentType.range.pdf');
            } else {
                $contentType = MbqBaseFdt::getFdt('MbqFdtAtt.MbqEtAtt.contentType.range.other');
            }     
            $oMbqEtAtt->contentType->setOriValue($contentType);
            if ($var->showAsImage()) {
                if ($var->hasThumbnail()) {
                    $oMbqEtAtt->thumbnailUrl->setOriValue(MbqMain::$oMbqAppEnv->siteRootUrl.'index.php/Attachment/'.$var->attachmentID.'/?thumbnail=1');
                }
            }
            $oMbqEtAtt->url->setOriValue(MbqMain::$oMbqAppEnv->siteRootUrl.'index.php/Attachment/'.$var->attachmentID);
            $oMbqEtAtt->userId->setOriValue($var->userID);
            $oMbqEtAtt->mbqBind['oAttachment'] = $var;
            return $oMbqEtAtt;
        }
        MbqError::alert('', __METHOD__ . ',line:' . __LINE__ . '.' . MBQ_ERR_INFO_UNKNOWN_CASE);
    }
  
}

?>