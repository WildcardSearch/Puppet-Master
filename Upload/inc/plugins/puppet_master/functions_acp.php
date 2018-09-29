<?php
/*
 * Plugin Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file provides functions for acp.php
 */

/**
 * retrieve all puppet masters from the db
 *
 * @return array the pm data
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
 * retrieve all puppets for the given owner
 *
 * @return array the puppet data
 */
function _pm_get_all_puppets($ownerid)
{
	global $db;

	$query = $db->simple_select('puppets', '*', "ownerid='{$ownerid}'", array('order_by' => 'disp_order', 'order_dir' => 'ASC'));

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
