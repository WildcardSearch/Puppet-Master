<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains the installation routines
 */

/**
 * information about the plugin used by MyBB for display as well as to connect with updates
 *
 * @return array the plugin info
 */
function puppet_master_info()
{
	global $mybb, $lang, $cp_style, $cache;

	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	$extra_links = "<br />";
	$settings_link = puppet_master_build_settings_link();
	if ($settings_link) {
		// only show Manage Puppets link if active
		$plugin_list = $cache->read('plugins');
		$manage_link = '';
		if (!empty($plugin_list['active']) &&
			is_array($plugin_list['active']) &&
			in_array('puppet_master', $plugin_list['active'])) {
			$url = PUPPET_MASTER_URL;
			$manage_link = <<<EOF

					<li style="list-style-image: url(styles/{$cp_style}/images/puppet_master/manage.png)">
						<a href="{$url}" title="{$lang->puppet_master_manage_puppets}">{$lang->puppet_master_manage_puppets}</a>
					</li>
EOF;

		}

		$extra_links = <<<EOF

				<ul>{$manage_link}
					<li style="list-style-image: url(styles/{$cp_style}/images/puppet_master/settings.png)">
						{$settings_link}
					</li>
				</ul>
EOF;

		$button_pic = "styles/{$cp_style}/images/puppet_master/donate.png";
		$border_pic = "styles/{$cp_style}/images/puppet_master/pixel.png";
		$puppet_master_description = <<<EOF
<table width="100%">
	<tbody>
		<tr>
			<td>
				{$lang->puppet_master_description}{$extra_links}
			</td>
			<td style="text-align: center;">
				<img src="styles/{$cp_style}/images/puppet_master/logo.png" alt="{$lang->puppet_master_logo}"/><br /><br />
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="VA5RFLBUC4XM4">
					<input type="image" src="{$button_pic}" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="{$border_pic}" width="1" height="1">
				</form>
			</td>
		</tr>
	</tbody>
</table>
EOF;
	} else {
		$puppet_master_description = $lang->puppet_master_description;
	}

	$name = <<<EOF
<span style="font-familiy: arial; font-size: 1.5em; color: black; text-shadow: 2px 2px 2px dimgray;">{$lang->puppet_master}</span>
EOF;
	$author = <<<EOF
</a></small></i><a href="http://www.rantcentralforums.com" title="Rant Central"><span style="font-family: Courier New; font-weight: bold; font-size: 1.2em; color: #0e7109;">Wildcard</span></a><i><small><a>
EOF;

	// This array returns information about the plug-in, some of which was prefabricated above based on whether the plugin has been installed or not.
	return array (
		"name" => $name,
		"description" => $puppet_master_description,
		"website" => 'https://github.com/WildcardSearch/Puppet-Master',
		"author" => $author,
		"authorsite" => 'http://www.rantcentralforums.com',
		"version" => PUPPET_MASTER_VERSION,
		"compatibility" => '18*',
		"codename" => 'puppet_master',
	);
}

/**
 * if the table exists report installed
 *
 * @return bool
 */
function puppet_master_is_installed()
{
	return puppet_master_get_settingsgroup();
}

/**
 * add the tables and the column to the users table
 *
 * @return void
 */
function puppet_master_install()
{
	global $lang;

	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	PuppetMasterInstaller::getInstance()->install();
}

/**
 * edit templates/permissions
 *
 * @return void
 */
function puppet_master_activate()
{
	global $templates;

	// change the permissions to on by default
	change_admin_permission('config', 'puppet_master');

	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread_quickreply', "#" . preg_quote('{$closeoption}') . "#i", '{$closeoption}{$puppet_options}');
	find_replace_templatesets('newreply', "#" . preg_quote('{$modoptions}') . "#i", '{$modoptions}{$puppet_options}');
	find_replace_templatesets('newthread', "#" . preg_quote('{$modoptions}') . "#i", '{$modoptions}{$puppet_options}');
	find_replace_templatesets('editpost', "#" . preg_quote('{$subscriptionmethod}') . "#i", '{$puppet_options}{$subscriptionmethod}');
	find_replace_templatesets('editpost_moderate', "#" . preg_quote('</table>') . "#i", '{$puppet_options}</table>');
	find_replace_templatesets('showthread_inlinemoderation', "#" . preg_quote('</form>') . "#i", '{$puppet_list_box}</form>');
	find_replace_templatesets('showthread_moderationoptions', "#" . preg_quote('</form>') . "#i", '{$puppet_list_box}</form>');
	find_replace_templatesets('private_send', "#</table>(.*?)</form>#is", '{$puppet_options}</table>$1</form>');

	// if we just upgraded . . .
	$old_version = puppet_master_get_cache_version();
	$info = puppet_master_info();
	if ($old_version &&
		version_compare($old_version, $info['version'], '<')) {
		puppet_master_install();

		if (version_compare($old_version, '2.1', '<')) {
			$removedFiles = array(
				'inc/classes/installer.php',
				'inc/classes/malleable.php',
				'inc/classes/storable.php',
				'inc/classes/html_generator.php',
			);

			foreach ($removedFiles as $file) {
				@unlink(MYBB_ROOT . $file);
			}

			@my_rmdir_recursive(MYBB_ROOT . 'inc/plugins/puppet_master/images');
			@rmdir(MYBB_ROOT . 'inc/plugins/puppet_master/images');
		}

		if (version_compare($old_version, '2.1.1', '<')) {
			$removedFiles = array(
				'inc/classes/acp.php',
				'inc/classes/puppet_master.php',
				'inc/classes/HTMLGenerator.php',
				'inc/classes/MalleableObject.php',
				'inc/classes/StorableObject.php',
				'inc/classes/WildcardPluginInstaller.php',
			);

			foreach ($removedFiles as $file) {
				@unlink(MYBB_ROOT . $file);
			}
		}
	}

	puppet_master_set_cache_version();
}

/**
 * undo template/permissions changes
 *
 * @return void
 */
function puppet_master_deactivate()
{
	// remove the permissions
	change_admin_permission('config', 'puppet_master', -1);

	require_once MYBB_ROOT . '/inc/adminfunctions_templates.php';
	find_replace_templatesets('showthread_quickreply', "#" . preg_quote('{$puppet_options}') . "#i", '');
	find_replace_templatesets('newreply', "#" . preg_quote('{$puppet_options}') . "#i", '');
	find_replace_templatesets('newthread', "#" . preg_quote('{$puppet_options}') . "#i", '');
	find_replace_templatesets('editpost', "#" . preg_quote('{$puppet_options}') . "#i", '');
	find_replace_templatesets('editpost_moderate', "#" . preg_quote('{$puppet_options}') . "#i", '');
	find_replace_templatesets('showthread_inlinemoderation', "#" . preg_quote('{$puppet_list_box}') . "#i", '');
	find_replace_templatesets('showthread_moderationoptions', "#" . preg_quote('{$puppet_list_box}') . "#i", '');
	find_replace_templatesets('private_send', "#" . preg_quote('{$puppet_options}') . "#i", '');
}

/**
 * undo all db changes
 *
 * @return void
 */
function puppet_master_uninstall()
{
	global $lang;

	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	PuppetMasterInstaller::getInstance()->uninstall();

	puppet_master_unset_cache();
}

/**
 * check cached version info
 *
 * @return string|int the version that is currently cached or 0 on error
 */
function puppet_master_get_cache_version()
{
	global $cache;

	// get currently installed version, if there is one
	$puppet_master = $cache->read('puppet_master');
	if (is_array($puppet_master) &&
		isset($puppet_master['version'])) {
        return $puppet_master['version'];
	}
    return 0;
}

/**
 * set cached version info
 *
 * @return void
 */
function puppet_master_set_cache_version()
{
	global $cache;

	// get version from this plugin file
	$puppet_master_info = puppet_master_info();

	// update version cache to latest
	$puppet_master = $cache->read('puppet_master');
	$puppet_master['version'] = $puppet_master_info['version'];
	$cache->update('puppet_master', $puppet_master);
}

/**
 * remove cached info
 *
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return void
 */
function puppet_master_unset_cache()
{
	global $cache;

	$cache->update('puppet_master', null);
}

/* settings */

/**
 * retrieves the plugin's settings group gid if it exists
 * attempts to cache repeat calls
 *
 * @return int gid
 */
function puppet_master_get_settingsgroup()
{
	static $puppet_master_settings_gid;

	// if we have already stored the value
	if (isset($puppet_master_settings_gid)) {
		// don't waste a query
		$gid = (int) $puppet_master_settings_gid;
	} else {
		global $db;

		// otherwise we will have to query the db
		$query = $db->simple_select("settinggroups", "gid", "name='puppet_master_settings'");
		$gid = (int) $db->fetch_field($query, 'gid');
	}
	return $gid;
}

/**
 * builds the url to modify plug-in settings if given valid info
 *
 * @param  int group id
 * @return string the URL to edit the settings
 */
function puppet_master_build_settings_url($gid)
{
	if ($gid) {
		return "index.php?module=config-settings&amp;action=change&amp;gid=" . $gid;
	}
}

/**
 * builds a link to modify plug-in settings if it exists
 *
 * @return string the HTML anchor to edit the settings
 */
function puppet_master_build_settings_link()
{
	global $lang;

	if (!$lang->puppet_master) {
		$lang->load('puppet_master');
	}

	$gid = puppet_master_get_settingsgroup();

	// does the group exist?
	if (!$gid) {
		return false;
	}

	// if so build the URL
	$url = puppet_master_build_settings_url($gid);

	// did we get a URL?
	if (!$url) {
		return false;
	}

	// if so build the link
	return "<a href=\"{$url}\" title=\"{$lang->puppet_master_plugin_settings}\">{$lang->puppet_master_plugin_settings}</a>";
}

?>
