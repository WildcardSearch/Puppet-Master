<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.6.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains the installation routines
 */

/*
 * puppet_master_info()
 *
 * information about the plugin used by MyBB for display as well as to connect with updates
 *
 * @return: (array) the plugin info
 */
function puppet_master_info()
{
	global $mybb, $lang;

	if(!$lang->puppet_master)
	{
		$lang->load('puppet_master');
	}

	$extra_links = "<br />";
	$settings_link = puppet_master_build_settings_link();
	if($settings_link)
	{
		$url = PUPPET_MASTER_URL;
		$extra_links = <<<EOF

				<ul>
					<li style="list-style-image: url(../inc/plugins/puppet_master/images/settings.gif)">
						{$settings_link}
					</li>
					<li style="list-style-image: url(../inc/plugins/puppet_master/images/manage.gif)">
						<a href="{$url}">{$lang->puppet_master_manage_puppets}</a>
					</li>
				</ul>
EOF;
	}

	$button_pic = $mybb->settings['bburl'] . '/inc/plugins/puppet_master/images/donate.gif';
	$border_pic = $mybb->settings['bburl'] . '/inc/plugins/puppet_master/images/pixel.gif';
	$puppet_master_description = <<<EOF
<table width="100%">
	<tbody>
		<tr>
			<td>
				{$lang->puppet_master_description}{$extra_links}
			</td>
			<td style="text-align: center;">
				<img src="{$mybb->settings['bburl']}/inc/plugins/puppet_master/images/logo.png" alt="{$lang->puppet_master_logo}"/><br /><br />
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

	$name = <<<EOF
<span style="font-familiy: arial; font-size: 1.5em; color: black; text-shadow: 2px 2px 2px dimgray;">{$lang->puppet_master}</span>
EOF;
	$author = <<<EOF
</a></small></i><a href="http://www.rantcentralforums.com" title="Rant Central"><span style="font-family: Courier New; font-weight: bold; font-size: 1.2em; color: #0e7109;">Wildcard</span></a><i><small><a>
EOF;

	// This array returns information about the plug-in, some of which was prefabricated above based on whether the plugin has been installed or not.
	return array
	(
		"name" => $name,
		"description" => $puppet_master_description,
		"website" => 'https://github.com/WildcardSearch/Puppet-Master',
		"author" => $author,
		"authorsite" => 'http://www.rantcentralforums.com',
		"version" => '1.1',
		"compatibility" => '16*',
		"guid" => '9b2d03ebbf540d83b2f97726d7426052',
	);
}

/*
 * puppet_master_is_installed()
 *
 * if the table exists report installed
 *
 * @return: (bool) true if installed, false if not
 */
function puppet_master_is_installed()
{
	return puppet_master_get_settingsgroup();
}

/*
 * puppet_master_install()
 *
 * add the tables and the column to the users table
 *
 * @return: n/a
 */
function puppet_master_install()
{
	global $lang;

	if(!$lang->puppet_master)
	{
		$lang->load('puppet_master');
	}

	// settings tables, templates, groups and setting groups
	if(!class_exists('WildcardPluginInstaller'))
	{
		require_once MYBB_ROOT . 'inc/plugins/puppet_master/classes/installer.php';
	}

	$installer = new WildcardPluginInstaller(MYBB_ROOT . 'inc/plugins/puppet_master/install_data.php');
	$installer->install();
}

/*
 * puppet_master_activate()
 *
 * edit templates/permissions
 *
 * @return: n/a
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
	if(version_compare($old_version, $info['version'], '<'))
	{
		puppet_master_install();
	}

	puppet_master_set_cache_version();
}

/*
 * puppet_master_deactivate()
 *
 * undo template/permissions changes
 *
 * @return: n/a
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

/*
 * puppet_master_uninstall()
 *
 * undo all db changes
 *
 * @return: n/a
 */
function puppet_master_uninstall()
{
	global $lang;

	if(!$lang->puppet_master)
	{
		$lang->load('puppet_master');
	}

	// settings tables, templates, groups and setting groups
	if(!class_exists('WildcardPluginInstaller'))
	{
		require_once MYBB_ROOT . 'inc/plugins/puppet_master/classes/installer.php';
	}

	$installer = new WildcardPluginInstaller(MYBB_ROOT . 'inc/plugins/puppet_master/install_data.php');
	$installer->uninstall();

	puppet_master_unset_cache();
}

/*
 * puppet_master_get_cache_version()
 *
 * check cached version info
 *
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: the version that is currently cached
 */
function puppet_master_get_cache_version()
{
	global $cache;

	// get currently installed version, if there is one
	$puppet_master = $cache->read('puppet_master');
	if(is_array($puppet_master) && isset($puppet_master['version']))
	{
        return $puppet_master['version'];
	}
    return 0;
}

/*
 * puppet_master_set_cache_version()
 *
 * set cached version info
 *
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: n/a
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

/*
 * puppet_master_unset_cache_version()
 *
 * remove cached info
 *
 * derived from the work of pavemen in MyBB Publisher
 *
 * @return: n/a
 */
function puppet_master_unset_cache()
{
	global $cache;

	$puppet_master = $cache->read('puppet_master');
	$puppet_master = null;
	$cache->update('puppet_master', $puppet_master);
}

/*
 * settings
 */

/*
 * puppet_master_get_settingsgroup()
 *
 * retrieves the plugin's settings group gid if it exists
 * attempts to cache repeat calls
 *
 * @return: (int) the gid of the setting group
 */
function puppet_master_get_settingsgroup()
{
	static $puppet_master_settings_gid;

	// if we have already stored the value
	if(isset($puppet_master_settings_gid))
	{
		// don't waste a query
		$gid = (int) $puppet_master_settings_gid;
	}
	else
	{
		global $db;

		// otherwise we will have to query the db
		$query = $db->simple_select("settinggroups", "gid", "name='puppet_master_settings'");
		$gid = (int) $db->fetch_field($query, 'gid');
	}
	return $gid;
}

/*
 * puppet_master_build_settings_url()
 *
 * builds the url to modify plug-in settings if given valid info
 *
 * @param - $gid is an integer representing a valid settings group id
 * @return: (string) the URL to edit the settings
 */
function puppet_master_build_settings_url($gid)
{
	if($gid)
	{
		return "index.php?module=config-settings&amp;action=change&amp;gid=" . $gid;
	}
}

/*
 * puppet_master_build_settings_link()
 *
 * builds a link to modify plug-in settings if it exists
 *
 * @return: (string) the HTML anchor to edit the settings
 */
function puppet_master_build_settings_link()
{
	global $lang;

	if(!$lang->puppet_master)
	{
		$lang->load('puppet_master');
	}

	$gid = puppet_master_get_settingsgroup();

	// does the group exist?
	if(!$gid)
	{
		return false;
	}

	// if so build the URL
	$url = puppet_master_build_settings_url($gid);

	// did we get a URL?
	if(!$url)
	{
		return false;
	}

	// if so build the link
	return "<a href=\"{$url}\" title=\"{$lang->puppet_master_plugin_settings}\">{$lang->puppet_master_plugin_settings}</a>";
}

?>
