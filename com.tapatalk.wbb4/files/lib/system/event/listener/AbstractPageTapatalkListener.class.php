<?php
namespace wbb\system\event\listener;
use wcf\system\event\IEventListener;
use wcf\system\WCF;

/**
 * Builds the global Tapatalk smartbanner HTML and assigns it.
 * 
 * @author	Alexander Ebert
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.tapatalk.wbb4
 * @subpackage	system.event.listener
 */
class AbstractPageTapatalkListener implements IEventListener {
	/**
	 * @see	\wcf\system\event\IEventListener::execute()
	 */
	public function execute($eventObj, $className, $eventName) {
		// set variables required for the smartbanner feature
		$functionCallAfterWindowLoad = 0;
		
		$app_forum_name = WCF::getLanguage()->get(PAGE_TITLE);
		$board_url = WCF::getPath('wbb');
		$tapatalk_dir = WBB_TAPATALK_DIR;
		$tapatalk_dir_url = $board_url . $tapatalk_dir;
		$is_mobile_skin = 0;
		$app_location_url = 'tapatalk://' . preg_replace('~^https?://~', '', $board_url) . '?location=index';
		
		$app_banner_message = (WBB_TAPATALK_APP_BANNER_MESSAGE ?: WCF::getLanguage()->get('wcf.user.3rdparty.tapatalk.app_banner_message'));
		$app_ios_id = WBB_TAPATALK_APP_IOS_ID;
		$app_android_id = WBB_TAPATALK_APP_ANDROID_ID;
		$app_kindle_url = WBB_TAPATALK_APP_KINDLE_URL;
		
		// for full view ads
		$api_key = WBB_TAPATALK_API_KEY;
		$app_ads_enable = WBB_TAPATALK_APP_FULL_BANNER;
		
		if (file_exists($tapatalk_dir . '/smartbanner/head.inc.php')) {
			include($tapatalk_dir . '/smartbanner/head.inc.php');
		}
		
		if (isset($app_head_include)) {
			// rebuild the output HTML since we must place the meta tags for Twitter somewhere else
			WCF::getTPL()->assign(array(
				'tapatalkSmartbanner' => $app_banner_head . $app_indexing,
				'tapatalkTwitterAppCard' => $twitter_card_head
			));
		}
	}
}
