<?php
/*
 * Plugin Name: YourCode for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.wildcardsworld.com
 */

/**
 * builds a url from standard options array
 *
 * @param  array keyed to standard url options
 * @return string URL
 */
function _pm_url($options = array(), $url = '')
{
	if (!$url) {
		$url = PUPPET_MASTER_URL;
	}

	$sep = '&amp;';
	if (strpos($url, '?') === false) {
		$sep = '?';
	}

	// check for the allowed options
	foreach (array('script', 'style', 'action', 'mode', 'type', 'name', 'id', 'uid', 'page', 'my_post_key') as $item) {
		if (isset($options[$item]) &&
			$options[$item]) {
			// and add them if set
			$url .= "{$sep}{$item}={$options[$item]}";
			$sep = '&amp;';
		}
	}
	return $url;
}

/**
 * builds an HTML anchor from the provided options
 *
 * @param  string the address
 * @param  string the title of the link
 * @param  array options to effect the HTML output
 * @return string link
 */
function _pm_link($url, $caption = "", $options = "")
{
	if (is_array($options) &&
		!empty($options)) {
		foreach (array('onclick', 'style', 'class', 'title') as $key) {
			if (isset($options[$key]) &&
				$options[$key]) {
				$$key = <<<EOF
{$key}="{$options[$key]}"
EOF;
			}
		}
	}

	if (!isset($caption) ||
		!$caption) {
		$caption = $url;
	}
	if (!isset($title) ||
		!$title) {
		$title = " title=\"{$caption}\"";
	}

	return <<<EOF
<a href="{$url}"{$title}{$onclick}{$style}{$class}>{$caption}</a>
EOF;
}

/**
 * retrieve information for all existing puppet master
 *
 * @return array|bool
 */
function _pm_get_all_masters()
{
	global $db;

	$query = $db->simple_select('users', 'username, uid, post_hidden', "puppet_master='1'");

	if ($db->num_rows($query) > 0) {
		$puppet_masters = array();

		while ($puppet_master = $db->fetch_array($query)) {
			$this_pm = new PuppetMaster();
			$this_pm->set($puppet_master);
			$puppet_masters[$puppet_master['uid']] = $this_pm;
		}
		return $puppet_masters;
	}
	return false;
}

/**
 * retrieve information for all puppets
 *
 * @return array|bool
 */
function _pm_get_all_puppets($ownerid)
{
	global $db;

	$query = $db->simple_select('puppets', '*', "ownerid='{$ownerid}'", array("order_by" => 'disp_order', "order_dir" => 'ASC'));

	if ($db->num_rows($query) > 0) {
		$puppets = array();

		while ($puppet = $db->fetch_array($query)) {
			$puppets[$puppet['id']] = new Puppet($puppet);
		}
		return $puppets;
	}
	return false;
}

?>
