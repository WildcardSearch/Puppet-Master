<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * the forum side hook implementations and initialization
 */

puppet_master_initialize();

/*
 * puppet_master_insert_options()
 *
 * if the user is a puppet master show the options
 *
 * @return: n/a
 */
function puppet_master_insert_options()
{
	global $mybb, $is_selected;

	// if the user is a puppet master
	if(!$mybb->user['puppet_master'])
	{
		return;
	}

	global $puppet_options, $puppet_list_box, $db, $templates, $lang;
	if(!$lang->puppet_master)
	{
		$lang->load('puppet_master');
	}

	$uid = (int) $mybb->user['uid'];

	// set the defaults
	if($mybb->user['post_hidden'])
	{
		$is_hidden = ' checked="checked"';
	}

	$query = $db->simple_select('puppets', '*', "ownerid='{$uid}'", array("order_by" => 'disp_order', "order_dir" => 'ASC'));

	// if the pm has no puppets get out
	if($db->num_rows($query) == 0)
	{
		return;
	}

	// build a list box
	$puppets = '';
	while($puppet = $db->fetch_array($query))
	{
		$is_selected = '';
		if($puppet['uid'] == $mybb->input['which_puppet'])
		{
			$is_selected = ' selected';
		}
		eval("\$puppets .= \"" . $templates->get('puppetmaster_puppet_option') . "\";");
	}

	eval("\$puppet_list_box = \"" . $templates->get('puppetmaster_puppet_select') . "\";");

	// tailor language / add post hidden option where appropriate
	switch(THIS_SCRIPT)
	{
		case 'private.php':
			$this_action = $lang->puppet_master_action_message;
			break;
		case 'editpost.php':
			$this_action = $lang->puppet_master_action_edit;
			break;
		case 'newthread.php':
		case 'newreply.php':
		case 'showthread.php':
			$this_action = $lang->puppet_master_action_post;
			eval("\$post_unapproved = \"" . $templates->get('puppetmaster_post_unapproved') . "\";");
			break;
		default:
			$this_action = $lang->puppet_master_action_moderate;
	}

	// store options to be displayed
	eval("\$all_puppet_options = \"" . $templates->get('puppetmaster_all_puppet_options') . "\";");

	// only showthread.php needs and unwrapped set of inputs
	if(THIS_SCRIPT != 'showthread.php')
	{
		eval("\$puppet_options = \"" . $templates->get('puppetmaster_puppet_options_showthread') . "\";");
	}
	else
	{
		eval("\$puppet_options = \"" . $templates->get('puppetmaster_puppet_options') . "\";");
	}
}

/*
 * puppet_master_cloak()
 *
 * check whether to post as puppet or not
 *
 * @return: n/a
 */
function puppet_master_cloak()
{
	global $mybb, $db, $thread, $uid, $username;

	// if the user opted to post as a puppet account . . .
	if(!$mybb->input['which_puppet'] || $mybb->input['which_puppet'] == $mybb->user['uid'])
	{
		return;
	}

	if(THIS_SCRIPT == 'private.php')
	{
		$fake_location = "/private.php";
	}
	elseif((THIS_SCRIPT == 'newreply.php' || THIS_SCRIPT == 'newthread.php') && $mybb->input['previewpost'])
	{
		// use the puppet uid instead of their real uid
		$uid = (int) $mybb->input['which_puppet'];
		$mybb->user = get_user($uid);
		$username = $mybb->user['username'];
		return;
	}
	else
	{
		$tid = (int) $mybb->input['tid'];
		$fid = (int) $thread['fid'];

		// Mark thread as read
		require_once MYBB_ROOT."inc/functions_indicators.php";
		mark_thread_read($tid, $fid);

		$fake_location = "/showthread.php?tid={$mybb->input['tid']}";
	}

	// use the puppet uid instead of their real uid
	$uid = (int) $mybb->input['which_puppet'];
	$mybb->user = get_user($uid);

	// and update their online status
	$query = $db->simple_select('sessions', 'sid', "uid='{$uid}'");

	// if the user has a session then fetch it
	if($db->num_rows($query) == 1)
	{
		$sid = $db->fetch_field($query, 'sid');
	}

	// if not
	if(!$sid)
	{
		// create it
		$mybb->session->create_session($uid);
		$sid = $mybb->session->sid;
	}

	if($sid)
	{
		// update the session with fake data
		$fake_session = array
		(
			"sid"					=>	$sid,
			"uid"					=>	$uid,
			"time"				=>	TIME_NOW,
			"ip"					=>	PM_FAKE_IP,
			"location"			=>	$fake_location
		);
		$db->update_query('sessions', $fake_session, "uid='{$uid}'");
	}
}

/*
 * puppet_master_hide()
 *
 * check whether to post unapproved
 *
 * @return: n/a
 */
function puppet_master_hide()
{
	global $pid, $tid, $mybb, $db, $thread_info;

	if($mybb->input['post_hidden'] != 1)
	{
		return;
	}

	require_once 'inc/class_moderation.php';
	$mod = new Moderation;
	$mod->unapprove_posts(array($pid));
}

/*
 * puppet_master_mod_tools()
 *
 * cloaks moderator tool usage
 *
 * @return: n/a
 */
function puppet_master_mod_tools()
{
	global $mybb;

	// if the user is cloaking . . .
	if(!$mybb->input['which_puppet'] || $mybb->input['which_puppet'] == $mybb->user['uid'])
	{
		return;
	}

	// use the puppet details
	$mybb->user = get_user($mybb->input['which_puppet']);

	// and generate an appropriate key (mod tools will fail without this)
	$mybb->input['my_post_key'] = generate_post_check();
}

/*
 * puppet_master_initialize()
 *
 * add the appropriate hooks and get an IP to obfuscate with
 *
 * @return: n/a
 */
function puppet_master_initialize()
{
	global $mybb, $plugins, $templatelist;

	if(!$mybb->settings['puppet_master_on'])
	{
		return;
	}

	$do_templates = true;
	switch(THIS_SCRIPT)
	{
		case 'private.php':
			$plugins->add_hook("private_send_start", "puppet_master_insert_options");
			$plugins->add_hook("private_send_do_send", "puppet_master_cloak");
			break;
		case 'showthread.php':
			$plugins->add_hook("showthread_start", "puppet_master_insert_options");
			break;
		case 'newreply.php':
			$plugins->add_hook("newreply_start", "puppet_master_insert_options");

			if($mybb->input['previewpost'] && !$mybb->input['ajax'])
			{
				$plugins->add_hook("newreply_start", "puppet_master_cloak");
			}
			else
			{
				$plugins->add_hook("newreply_do_newreply_start", "puppet_master_cloak");
			}

			$plugins->add_hook("newreply_do_newreply_end", "puppet_master_hide");
			break;
		case 'newthread.php':
			$plugins->add_hook("newthread_start", "puppet_master_insert_options");

			if($mybb->input['previewpost'])
			{
				$plugins->add_hook("newthread_start", "puppet_master_cloak");
			}
			else
			{
				$plugins->add_hook("newthread_do_newthread_start", "puppet_master_cloak");
			}

			$plugins->add_hook("newthread_do_newthread_end", "puppet_master_hide");
			break;
		case 'editpost.php':
			$plugins->add_hook("editpost_start", "puppet_master_insert_options");
			$plugins->add_hook("editpost_do_editpost_start", "puppet_master_cloak");
			break;
		case 'moderation.php':
			$plugins->add_hook("moderation_start", "puppet_master_mod_tools");
			break;
		default:
			$do_templates = false;
	}

	if($do_templates)
	{
		$templatelist .= ',puppetmaster_puppet_option,puppetmaster_puppet_select,puppetmaster_post_unapproved,puppetmaster_all_puppet_options,puppetmaster_puppet_options_showthread,puppetmaster_puppet_options';
	}

	if(defined('PM_FAKE_IP'))
	{
		return;
	}

	$hostname = str_replace(array('http://', 'https://'), '', $mybb->settings['bburl']);
	$hostname_array = explode('/', $hostname);
	$hostname = $hostname_array[0];

	$fake_ip = gethostbyname($hostname);
	if($fake_ip != $hostname)
	{
		define('PM_FAKE_IP', $fake_ip);
	}
	else
	{
		define('PM_FAKE_IP', '127.0.0.1');
	}
}

?>
