<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains data used by classes/installer.php
 */

$tables = array (
	"puppets" => array (
		"id" => 'INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY',
		"uid" => 'INT(10) NOT NULL',
		"ownerid" => 'INT(10) NOT NULL',
		"username" => 'VARCHAR(120) NOT NULL',
		"disp_order" => 'INT(10) NOT NULL',
		"dateline" => 'INT(10)'
	)
);

$columns = array (
	"users" => array (
		"puppet_master" => 'INT(1) DEFAULT 0',
		"post_hidden" => 'INT(1) DEFAULT 0'
	)
);

$settings = array(
	"puppet_master_settings" => array(
		"group" => array(
			"name" => 'puppet_master_settings',
			"title" => 'Puppet Master',
			"description" => $lang->puppet_master_settingsgroup_description,
			"disporder" => '101',
			"isdefault" => 0
		),
		"settings" => array(
			"puppet_master_on" => array(
				"sid" => 'NULL',
				"name" => 'puppet_master_on',
				"title" => $lang->puppet_master_on_title,
				"description" => $lang->puppet_master_on_desc,
				"optionscode" => 'onoff',
				"value" => '1',
				"disporder" => '10'
			),
		),
	),
);

$templates = array(
	"puppetmaster" => array(
		"group" => array(
			"prefix" => 'puppetmaster',
			"title" => $lang->puppet_master,
		),
		"templates" => array(
			"puppetmaster_puppet_option" => <<<EOF
<option value="{\$puppet['uid']}"{\$is_selected}>{\$puppet['username']}</option>
EOF
			,
			"puppetmaster_puppet_select" => <<<EOF
<select name="which_puppet">
	{\$puppets}
</select>
EOF
			,
			"puppetmaster_post_unapproved" => <<<EOF
<br /><label><input type="checkbox" class="checkbox" name="post_hidden" value="1"{\$is_hidden}/>&nbsp;<strong>{\$lang->puppet_master_post_unapproved}</strong></label>
EOF
			,
			"puppetmaster_all_puppet_options" => <<<EOF
<label><strong>{\$this_action} As: </strong>{\$puppet_list_box}</label>{\$post_unapproved}
EOF
			,
			"puppetmaster_puppet_options_showthread" => <<<EOF
<tr>
	<td class="trow2" valign="top">
		<strong>{\$lang->puppet_master_puppet_options}</strong>
	</td>
	<td class="trow2">
		<span class="smalltext">{\$all_puppet_options}</span>
	</td>
</tr>
EOF
			,
			"puppetmaster_puppet_options" => <<<EOF
<br /><br />{\$all_puppet_options}
EOF
		),
	),
);

?>
