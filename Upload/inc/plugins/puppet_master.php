<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * the main plug-in file; splits forum and ACP scripts to decrease footprint
 */

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

define('PUPPET_MASTER_VERSION', '2.1.4');

// register custom class autoloader
spl_autoload_register('puppetMasterClassAutoload');

// load the install/admin routines only if in ACP.
if (defined('IN_ADMINCP')) {
    require_once MYBB_ROOT.'inc/plugins/puppet_master/acp.php';
} else {
	require_once MYBB_ROOT.'inc/plugins/puppet_master/forum.php';
}

 /**
  * class autoloader
  *
  * @param string the name of the class to load
  */
function puppetMasterClassAutoload($className) {
	$path = MYBB_ROOT."inc/plugins/puppet_master/classes/{$className}.php";

	if (file_exists($path)) {
		require_once $path;
	}
}

?>
