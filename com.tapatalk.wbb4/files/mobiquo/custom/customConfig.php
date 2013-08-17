<?php

defined('MBQ_IN_IT') or exit;

/**
 * user custom config,to replace some config of MbqMain::$oMbqConfig->cfg.
 * you can change any config if you need,please refer to MbqConfig.php for more details.
 * 
 * @since  2012-7-19
 * @author Wu ZeTao <578014287@qq.com>
 */
MbqMain::$customConfig['base']['is_open'] = MbqBaseFdt::getFdt('MbqFdtConfig.base.is_open.range.yes');
MbqMain::$customConfig['base']['version'] = 'wb40_0.0.2';
MbqMain::$customConfig['base']['api_level'] = 3;

MbqMain::$customConfig['subscribe']['module_enable'] = MbqMain::$oClk->newObj('MbqValue', array('oriValue' => MbqBaseFdt::getFdt('MbqFdtConfig.subscribe.module_enable.range.enable')));

MbqMain::$customConfig['user']['guest_okay'] = MbqBaseFdt::getFdt('MbqFdtConfig.user.guest_okay.range.support');
MbqMain::$customConfig['user']['user_id'] = MbqBaseFdt::getFdt('MbqFdtConfig.user.user_id.range.support');

MbqMain::$customConfig['forum']['no_refresh_on_post'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.no_refresh_on_post.range.support');
MbqMain::$customConfig['forum']['get_latest_topic'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.get_latest_topic.range.support');
MbqMain::$customConfig['forum']['guest_search'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.guest_search.range.support');
MbqMain::$customConfig['forum']['mark_read'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.mark_read.range.support');
MbqMain::$customConfig['forum']['report_post'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.report_post.range.support');
MbqMain::$customConfig['forum']['can_unread'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.can_unread.range.support');
MbqMain::$customConfig['forum']['subscribe_load'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.subscribe_load.range.support');
MbqMain::$customConfig['forum']['goto_post'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.report_post.range.support');
MbqMain::$customConfig['forum']['goto_unread'] = MbqBaseFdt::getFdt('MbqFdtConfig.forum.goto_unread.range.support');

MbqMain::$customConfig['pc']['module_enable'] = MbqMain::$oClk->newObj('MbqValue', array('oriValue' => MbqBaseFdt::getFdt('MbqFdtConfig.pc.module_enable.range.enable')));
MbqMain::$customConfig['pc']['conversation'] = MbqBaseFdt::getFdt('MbqFdtConfig.pc.conversation.range.support');

?>