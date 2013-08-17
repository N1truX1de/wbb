<?php

defined('MBQ_IN_IT') or exit;

/**
 * common method class
 * 
 * @since  2012-7-2
 * @author Wu ZeTao <578014287@qq.com>
 */
Class MbqCm extends MbqBaseCm {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * transform timestamp to iso8601 format
     *
     * @param  Integer  $timeStamp
     * TODO:need to be made more general.
     */
    public function datetimeIso8601Encode($timeStamp) {
        //return date("c", $timeStamp);
        return date('Ymd\TH:i:s', $timeStamp).'+00:00';
    }
    
    /**
     * get short content
     *
     * @param  String  $str
     * @param  Integer  $length
     * @return  String
     */
    public function getShortContent($str, $length = 200) {
        /* get short content standard code begin */
        $str = preg_replace('/\<font [^\>]*?\>(.*?)\<\/font\>/is', '$1', $str);
        $str = preg_replace('/\<font\>(.*?)\<\/font\>/is', '$1', $str);
        $str = preg_replace('/\[quote[^\]]*?\].*?\[\/quote\]/is', '[quote]', $str);
        $str = preg_replace_callback('/\[url\=(.*?)\](.*?)\[\/url\]/is', create_function('$matches','return ($matches[1] == $matches[2]) ? "[url]" : $matches[2];'), $str);
        $str = preg_replace('/\[url\](.*?)\[\/url\]/is', '[url]', $str);
        $str = preg_replace_callback('/\[email\=(.*?)\](.*?)\[\/email\]/is', create_function('$matches','return ($matches[1] == $matches[2]) ? "[url]" : $matches[2];'), $str);
        $str = preg_replace('/\[email\](.*?)\[\/email\]/is', '[url]', $str);
        $str = preg_replace_callback('/\[iurl\=(.*?)\](.*?)\[\/iurl\]/is', create_function('$matches','return ($matches[1] == $matches[2]) ? "[url]" : $matches[2];'), $str);
        $str = preg_replace('/\[iurl\](.*?)\[\/iurl\]/is', '[url]', $str);
        $str = preg_replace('/\[img[^\]]*?\].*?\[\/img\]/is', '[img]', $str);
        $str = preg_replace('/\[video[^\]]*?\].*?\[\/video\]/is', '[V]', $str);
        $str = preg_replace('/\[flash[^\]]*?\].*?\[\/flash\]/is', '[V]', $str);
        $str = preg_replace('/\[media[^\]]*?\].*?\[\/media\]/is', '[V]', $str);
        $str = preg_replace('/\[attachment[^\]]*?\].*?\[\/attachment\]/is', '[attach]', $str);
        $str = preg_replace('/\[attach[^\]]*?\].*?\[\/media\]/is', '[attach]', $str);
        $str = preg_replace('/\[php[^\]]*?\].*?\[\/php\]/is', '[php]', $str);
        $str = preg_replace('/\[html[^\]]*?\].*?\[\/html\]/is', '[html]', $str);
        $str = preg_replace('/\[spoiler[^\]]*?\].*?\[\/spoiler\]/is', '[spoiler]', $str);
        $str = preg_replace('/\[thread[^\]]*?\].*?\[\/thread\]/is', '[thread]', $str);
        $str = preg_replace('/\[topic[^\]]*?\].*?\[\/topic\]/is', '[topic]', $str);
        $str = preg_replace('/\[post[^\]]*?\].*?\[\/post\]/is', '[post]', $str);
        $str = preg_replace('/\[ftp[^\]]*?\].*?\[\/ftp\]/is', '[ftp]', $str);
        $str = preg_replace('/\[sql[^\]]*?\].*?\[\/sql\]/is', '[sql]', $str);
        $str = preg_replace('/\[xml[^\]]*?\].*?\[\/xml\]/is', '[xml]', $str);
        $str = preg_replace('/\[hide[^\]]*?\].*?\[\/hide\]/is', '[hide]', $str);
        $str = preg_replace('/\[confidential[^\]]*?\].*?\[\/confidential\]/is', '[hide]', $str);
        $str = preg_replace('/\[ebay[^\]]*?\].*?\[\/ebay\]/is', '[ebay]', $str);
        $str = preg_replace('/\[map[^\]]*?\].*?\[\/map\]/is', '[map]', $str);
        $str = preg_replace('/[\n|\r|\t]/', '', $str);
        //remove useless bbcode begin
        $str = preg_replace_callback('/\[([^\/]*?)\]/i', create_function('$matches','
        $v = strtolower($matches[1]);
        if (strpos($v, "quote") === 0 || strpos($v, "url") === 0 || strpos($v, "img") === 0 || strpos($v, "v") === 0 || strpos($v, "attach") === 0 || strpos($v, "php") === 0 || strpos($v, "html") === 0 || strpos($v, "spoiler") === 0 || strpos($v, "thread") === 0 || strpos($v, "topic") === 0 || strpos($v, "post") === 0 || strpos($v, "ftp") === 0 || strpos($v, "sql") === 0 || strpos($v, "xml") === 0 || strpos($v, "hide") === 0 || strpos($v, "ebay") === 0 || strpos($v, "map") === 0) {
            return "[$matches[1]]";
        } else {
            return "";
        }
        '), $str);
        $str = preg_replace('/\[\/[^\]]*?\]/i', '', $str);
        //remove useless bbcode end
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        $str = function_exists('mb_substr') ? mb_substr($str, 0, $length) : substr($str, 0, $length);
        $str = strip_tags($str);
        /* get short content standard code end */
        return $str;
    }
    
    /**
     * get attachment ids from content
     *
     * @params  String  $content
     * @return  Array
     */
    public function getAttIdsFromContent($content) {
        preg_match_all('/\[attach=(.*?)\]\[\/attach\]/i', $content, $mat);
        if ($mat[1]) {
            return $mat[1];
        } else {
            return array();
        }
    }
    
}

?>