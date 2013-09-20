<!-- Tapatalk Banner head start -->
<link href="{@$__wcf->getPath('wbb')}{@WBB_TAPATALK_DIR}/smartbanner/appbanner.css" rel="stylesheet" type="text/css" media="screen" />
<script>
	var is_mobile_skin	= 0;
	var app_ios_id		= "{@WBB_TAPATALK_APP_IOS_ID}";
	var app_android_id	= "{@WBB_TAPATALK_APP_ANDROID_ID}";
	var app_kindle_url	= "{@WBB_TAPATALK_APP_KINDLE_URL}";
	var app_banner_message	= "{if WBB_TAPATALK_APP_BANNER_MESSAGE}{@WBB_TAPATALK_APP_BANNER_MESSAGE}{else}{literal}Follow {your_forum_name} <br /> with {app_name} for [os_platform]{/literal}{/if}";
	var app_forum_name	= "{PAGE_TITLE|language}";
	var app_location_url	= "tapatalk://{@'~^https?://~'|preg_replace:'':$__wcf->getPath('wbb')}?location=index";
	var functionCallAfterWindowLoad = 0
</script>
<script src="{@$__wcf->getPath('wbb')}{@WBB_TAPATALK_DIR}/smartbanner/appbanner.js"></script>
<!-- Tapatalk Banner head end-->