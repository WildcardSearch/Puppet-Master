<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains the ACP pages and functions
 */

define('PUPPET_MASTER_URL', 'index.php?module=config-puppet_master');
require_once MYBB_ROOT . "inc/plugins/puppet_master/install.php";

/**
 * the ACP page router
 *
 * @return void
 */
$plugins->add_hook('admin_load', 'puppet_master_admin_load');
function puppet_master_admin_load()
{
	// globalize as needed to save wasted work
	global $page;
	if ($page->active_action != 'puppet_master') {
		// not our turn
		return false;
	}

	// now load up, this is our time
	global $mybb, $lang, $html;
	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/acp.php";
	require_once MYBB_ROOT . "inc/plugins/puppet_master/functions_acp.php";

	// URL, link and image markup generator
	$html = new HTMLGenerator(PUPPET_MASTER_URL, array('addon', 'pos', 'topic', 'ajax'));

	// if there is an existing function for the action
	$page_function = 'puppet_master_admin_' . $mybb->input['action'];
	if (function_exists($page_function)) {
		// run it
		$page_function();
	} else {
		puppet_master_admin_main();
	}
	// get out
	exit;
}

/**
 * ACP pages for adding/editing puppet masters
 *
 * @return void
 */
function puppet_master_admin_main()
{
	global $page, $db, $mybb, $lang, $html;

	// POSTing?
	if ($mybb->request_method == 'post') {
		// add a new puppet master
		if ($mybb->input['mode'] == 'add') {
			// valid info?
			if (!isset($mybb->input['username']) ||
				!$mybb->input['username']) {
				flash_message($lang->puppet_master_add_error_bad_username, 'error');
				admin_redirect($html->url());
			}

			$username = $db->escape_string(my_strtolower($mybb->input['username']));
			$query = $db->simple_select('users', '*', "username='{$username}'");
			if ($db->num_rows($query) == 0) {
				flash_message($lang->puppet_master_add_error_bad_username, 'error');
				admin_redirect($html->url());
			}

			// get some info about the proposed pm
			$pm_user = $db->fetch_array($query);
			$uid = (int) $pm_user['uid'];

			// if this user doesn't exist we can't very well make them a pm
			if ($pm_user['uid'] != $uid) {
				flash_message($lang->puppet_master_add_error_bad_username, 'error');
				admin_redirect($html->url());
			}

			// and update their user column
			$this_pm = array(
				"puppet_master" => 1,
				"post_hidden" => (int) $mybb->input['post_hidden']
			);
			$db->update_query('users', $this_pm, "uid='{$uid}'");

			$og_puppet = array(
				"ownerid" => $uid,
				"uid" => $uid,
				"username" => $pm_user['username'],
				"disp_order" => 10
			);

			$first_puppet = new Puppet($og_puppet);
			$first_puppet->save();

			flash_message($lang->puppet_master_add_success, 'success');
			admin_redirect($html->url(array("action" => 'edit', "uid" => $uid)));
		}
	}

	if ($mybb->input['mode'] == 'delete') {
		$uid = (int) $mybb->input['uid'];

		// remove flag from users column
		$this_pm = array(
			"puppet_master" => (int) 0,
			"post_hidden" => (int) 0
		);
		$db->update_query('users', $this_pm, "uid='{$uid}'");

		// delete all the puppets
		$db->delete_query('puppets', "uid='{$uid}'");

		flash_message($lang->puppet_master_delete_success, 'success');
		admin_redirect($html->url());
	}

	$page->add_breadcrumb_item($lang->puppet_master);
	$page->output_header("{$lang->puppet_master} - {$lang->puppet_master_admin_main}");
	puppet_master_output_tabs('puppet_master_main');

	// get all puppet masters (if any)
	$puppet_masters = _pm_get_all_masters();

	$table = new Table;
	$table->construct_header($lang->username, array("width" => '90%'));
	$table->construct_header($lang->puppet_master_controls, array("width" => '10%'));

	// if there are pms
	if (is_array($puppet_masters) &&
		!empty($puppet_masters)) {
		// list them
		foreach ($puppet_masters as $uid => $puppet_master) {
			$edit_url = $html->url(array("action" => 'edit', "uid" => $uid));
			$edit_link = $html->link($edit_url, $puppet_master->get('username'));

			$table->construct_cell("<strong>{$edit_link}</strong>");
			$popup = new PopupMenu("control_{$uid}", $lang->puppet_master_options);
			$popup->add_item($lang->puppet_master_edit, $edit_url);
			$popup->add_item($lang->puppet_master_delete, $html->url(array("action" => 'main', "mode" => 'delete', "uid" => (int) $uid, "my_post_key" => $mybb->post_code)));
			$table->construct_cell($popup->fetch());
			$table->construct_row();
		}
	} else {
		$table->construct_cell("<em>{$lang->puppet_master_no_pms}</em>", array("colspan" => 2));
	}
	$table->output("<strong>{$lang->puppet_masters}:</strong>");

	// add puppet master form
	$form = new Form($html->url(array("action" => 'main', "mode" => 'add')), "post");
	$form_container = new FormContainer($lang->puppet_master_add_a_pm);
	$form_container->output_row($lang->puppet_master_pm_username, '', $form->generate_text_box('username', '', array('id' => 'username')));
	$form_container->output_row('', '', $form->generate_check_box('post_hidden', 1, $lang->puppet_master_post_unapproved, array("checked" => false)));
	$form_container->end();

	// Autocompletion for usernames
	echo '
<link rel="stylesheet" href="../jscripts/select2/select2.css">
<script type="text/javascript" src="../jscripts/select2/select2.min.js?ver=1804"></script>
<script type="text/javascript">
<!--
$("#username").select2({
	placeholder: "'.$lang->search_for_a_user.'",
	minimumInputLength: 2,
	multiple: false,
	ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
		url: "../xmlhttp.php?action=get_users",
		dataType: \'json\',
		data: function (term, page) {
			return {
				query: term // search term
			};
		},
		results: function (data, page) { // parse the results into the format expected by Select2.
			// since we are using custom formatting functions we do not need to alter remote JSON data
			return {results: data};
		}
	},
	initSelection: function(element, callback) {
		var query = $(element).val();
		if (query !== "") {
			$.ajax("../xmlhttp.php?action=get_users&getone=1", {
				data: {
					query: query
				},
				dataType: "json"
			}).done(function(data) { callback(data); });
		}
	}
});
// -->
</script>';

	// finish form and page
	$buttons[] = $form->generate_submit_button($lang->puppet_master_add, array('name' => 'add_puppet_master_submit'));
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

/**
 * edit ACP page
 *
 * @return void
 */
function puppet_master_admin_edit()
{
	global $page, $db, $mybb, $lang, $html;

	// POSTing?
	if ($mybb->request_method == 'post') {
		// add a new puppet master
		if ($mybb->input['mode'] == 'add') {
			// valid info?
			if (!isset($mybb->input['username']) ||
				!$mybb->input['username']) {
				flash_message($lang->puppet_master_add_puppet_error_bad_username, 'error');
				admin_redirect($html->url());
			}

			$username = $db->escape_string(my_strtolower($mybb->input['username']));
			$query = $db->simple_select('users', '*', "username='{$username}'");
			if ($db->num_rows($query) == 0) {
				flash_message($lang->puppet_master_add_puppet_error_bad_username, 'error');
				admin_redirect($html->url());
			}

			// get some info about the proposed pm
			$puppet_user = $db->fetch_array($query);
			$uid = (int) $puppet_user['uid'];
			$ownerid = (int) $mybb->input['ownerid'];

			// valid UID?
			if ($puppet_user['uid'] != $uid) {
				// if not then reject it
				flash_message($lang->puppet_master_add_puppet_error_bad_username, 'error');
				admin_redirect($html->url(array("action" => 'edit', "uid" => $ownerid)));
			}

			// if the puppet master doesn't already have this puppet
			$query = $db->simple_select('puppets', '*', "uid='{$uid}' AND ownerid='{$ownerid}'");
			if ($db->num_rows($query) == 0) {
				$num_puppets = (int) $db->num_rows($query);

				if (isset($mybb->input['disp_order']) &&
					$mybb->input['disp_order']) {
					$disp_order = (int) $mybb->input['disp_order'];
				} else {
					$disp_order = (int) $num_puppets * 10 + 10;
				}

				// add it
				$this_puppet = array (
					"uid" => $uid,
					"username" => $puppet_user['username'],
					"ownerid" => $ownerid,
					"disp_order" => $disp_order
				);
				$db->insert_query('puppets', $this_puppet);

				flash_message($lang->puppet_master_add_puppet_success, 'success');
			} else {
				flash_message($lang->puppet_master_add_puppet_error_duplicate, 'error');
			}
			admin_redirect($html->url(array("action" => 'edit', "uid" => $ownerid)));
		// save preferences
		} elseif ($mybb->input['mode'] == 'save') {
			$uid = (int) $mybb->input['uid'];

			if (isset($mybb->input['post_hidden'])) {
				$post_hidden = 1;
			}

			$defaults = array (
				"post_hidden" => (int) $post_hidden
			);

			$db->update_query('users', $defaults, "uid='{$uid}'");

			flash_message($lang->puppet_master_save_preferences_success, 'success');
			admin_redirect($html->url(array("action" => 'edit', "uid" => $uid)));
		} elseif ($mybb->input['mode'] == 'order') {
			if (is_array($mybb->input['disp_order']) &&
				!empty($mybb->input['disp_order'])) {
				foreach ($mybb->input['disp_order'] as $id => $order) {
					$this_puppet = new Puppet($id);
					$this_puppet->set('disp_order', $order);
					$this_puppet->save();
				}
				flash_message($lang->puppet_master_order_success, 'success');
			} else {
				flash_message($lang->puppet_master_order_error, 'error');
			}
			admin_redirect($html->url(array("action" => 'edit', "uid" => $mybb->input['uid'])));
		}
	}

	// edit puppet master
	if ($mybb->input['mode'] == 'delete') {
		$puppet = new Puppet((int) $mybb->input['id']);

		if ($puppet->is_valid()) {
			$success = $puppet->remove();
		}

		if ($success) {
			flash_message($lang->puppet_master_delete_puppet_success, 'success');
		} else {
			flash_message($lang->puppet_master_delete_puppet_error, 'error');
		}
		admin_redirect($html->url(array("action" => 'edit', "uid" => $mybb->input['uid'])));
	}

	$page->add_breadcrumb_item($lang->puppet_master, $html->url());
	$page->add_breadcrumb_item($lang->puppet_master_admin_edit);
	$page->output_header("{$lang->puppet_master} - {$lang->puppet_master_admin_edit}");
	puppet_master_output_tabs('puppet_master_edit');

	// valid info?
	if (isset($mybb->input['uid']) &&
		$mybb->input['uid']) {
		$uid = (int) $mybb->input['uid'];
		$query = $db->simple_select('users', 'uid, username, post_hidden', "uid='{$uid}' AND puppet_master='1'");
		$data = $db->fetch_array($query);
		$puppet_master = new PuppetMaster();
		$puppet_master->set($data);
		$puppets = _pm_get_all_puppets($uid);

		$form = new Form($html->url(array("action" => 'edit', "mode" => 'order')), 'post');
		$form_container = new FormContainer($lang->sprintf($lang->puppet_master_manage_puppets_for, $puppet_master->get('username')));

		$form_container->output_row_header($lang->username, array("width" => '30%'));
		$form_container->output_row_header($lang->puppet_master_display_order, array("width" => '30%'));
		$form_container->output_row_header($lang->puppet_master_controls, array("width" => '30%'));

		if (is_array($puppets) &&
			!empty($puppets)) {
			foreach ($puppets as $id => $puppet) {
				if ($puppet->get('uid') != $uid) {
					$delete_link = $html->link($html->url(array("action" => 'edit', "mode" => 'delete', "id" => $id, "uid" => $uid)), $lang->puppet_master_delete);
				} else {
					$delete_link = $lang->puppet_master;
				}
				$form_container->output_cell("<strong>{$puppet->get('username')}</strong>");
				$form_container->output_cell($form->generate_text_box("disp_order[{$id}]", $puppet->get('disp_order'), array("style" => 'width: 50px;')) . $form->generate_hidden_field('uid', $uid));
				$form_container->output_cell($delete_link);
				$form_container->construct_row();
			}
		} else {
			$form_container->output_cell("<em>{$lang->puppet_master_no_puppets}</em>", array("colspan" => 3));
			$form_container->construct_row();
		}
		$form_container->end();
		$buttons = array($form->generate_submit_button($lang->puppet_master_order, array('name' => 'order')));
		$form->output_submit_wrapper($buttons);
		$form->end();
	} else {
		flash_message($lang->puppet_master_invalid_pm, 'error');
		admin_redirect($html->url());
	}

	echo('<br />');

	// add new puppet form
	$form = new Form($html->url(array("action" => 'edit', "mode" => 'add')), "post");
	$form_container = new FormContainer($lang->sprintf($lang->puppet_master_add_puppet_for, $puppet_master->get('username')));

	$form_container->output_row($lang->puppet_master_puppet_username, '', $form->generate_text_box('username', '', array('id' => 'username')));
	$form_container->output_row($lang->puppet_master_display_order, '', $form->generate_text_box('disp_order', count($puppets) * 10 + 10) . $form->generate_hidden_field('ownerid', $uid));
	$form_container->end();

	// Autocompletion for usernames
	echo '
<link rel="stylesheet" href="../jscripts/select2/select2.css">
<script type="text/javascript" src="../jscripts/select2/select2.min.js?ver=1804"></script>
<script type="text/javascript">
<!--
$("#username").select2({
	placeholder: "'.$lang->search_for_a_user.'",
	minimumInputLength: 2,
	multiple: false,
	ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
		url: "../xmlhttp.php?action=get_users",
		dataType: \'json\',
		data: function (term, page) {
			return {
				query: term // search term
			};
		},
		results: function (data, page) { // parse the results into the format expected by Select2.
			// since we are using custom formatting functions we do not need to alter remote JSON data
			return {results: data};
		}
	},
	initSelection: function(element, callback) {
		var query = $(element).val();
		if (query !== "") {
			$.ajax("../xmlhttp.php?action=get_users&getone=1", {
				data: {
					query: query
				},
				dataType: "json"
			}).done(function(data) { callback(data); });
		}
	}
});
// -->
</script>';

	// finish form and page
	$buttons = array($form->generate_submit_button($lang->puppet_master_add, array('name' => 'add_puppet_submit')));
	$form->output_submit_wrapper($buttons);
	$form->end();

	echo('<br />');

	// save defaults form
	$form = new Form($html->url(array("action" => 'edit', "mode" => 'save')), "post");
	$form_container = new FormContainer($lang->sprintf($lang->puppet_master_save_defaults_for, $puppet_master->get('username')));
	$form_container->output_row('', '', $form->generate_check_box('post_hidden', 1, $lang->puppet_master_post_unapproved, array("checked" => $puppet_master->get('post_hidden'))) . $form->generate_hidden_field('uid', $uid));
	$form_container->end();

	// finish form
	$buttons = array();
	$buttons = array($form->generate_submit_button($lang->puppet_master_save, array('name' => 'save_preferences_submit')));
	$form->output_submit_wrapper($buttons);
	$form->end();

	$page->output_footer();
}

/**
 * add our page to the list of possible actions
 *
 * @return void
 */
$plugins->add_hook('admin_config_action_handler', 'puppet_master_admin_action');
function puppet_master_admin_action(&$action)
{
	$action['puppet_master'] = array('active' => 'puppet_master');
}

/**
 * add our item to the config side menu
 *
 * @return void
 */
$plugins->add_hook('admin_config_menu', 'puppet_master_admin_menu');
function puppet_master_admin_menu(&$sub_menu)
{
	global $lang;
	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	end($sub_menu);
	$key = (key($sub_menu)) + 10;
	$sub_menu[$key] = array(
		'id' => 'puppet_master',
		'title' => $lang->puppet_master,
		'link' => PUPPET_MASTER_URL
	);
}

/**
 * add our permissions setting to the admin permissions page
 *
 * @return void
 */
$plugins->add_hook('admin_config_permissions', 'puppet_master_admin_permissions');
function puppet_master_admin_permissions(&$admin_permissions)
{
	global $lang;
	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	$admin_permissions['puppet_master'] = $lang->puppet_master_permissions;
}

/**
 * output the tabs
 *
 * @param  string the current page key
 * @return void
 */
function puppet_master_output_tabs($current)
{
	global $page, $mybb, $lang, $html;

	// set up tabs
	$sub_tabs['puppet_master_main'] = array(
		'title' => $lang->puppet_master_admin_main,
		'link' => $html->url(),
		'description' => $lang->puppet_master_admin_main_desc
	);
	if ($current == 'puppet_master_edit') {
		$sub_tabs['puppet_master_edit'] = array(
			'title' => $lang->puppet_master_admin_edit,
			'link' => $html->url(array("action" => 'edit')),
			'description' => $lang->puppet_master_admin_edit_desc
		);
	}
	$page->output_nav_tabs($sub_tabs, $current);
}

?>
