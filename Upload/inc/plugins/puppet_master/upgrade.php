<?php
/*
 * Plug-in Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 *
 * this file contains upgrade functionality
 */

global $lang, $puppetMasterOldVersion, $db;

if (!$lang->puppet_master) {
	$lang->load('puppet_master');
}

PuppetMasterInstaller::getInstance()->install();

$removedAdminFolders = $removedForumFolders = $removedAdminFiles = $removedForumFiles = array();

/* < 2.1 */
if (version_compare($puppetMasterOldVersion, '2.1', '<')) {
	$removedForumFiles = array(
		'inc/classes/installer.php',
		'inc/classes/malleable.php',
		'inc/classes/storable.php',
		'inc/classes/html_generator.php',
	);

	$removedForumFolders[] = 'inc/plugins/puppet_master/images';
}

/* < 2.1.1 */
if (version_compare($puppetMasterOldVersion, '2.1.1', '<')) {
	$removedForumFiles = array_merge($removedForumFiles, array(
		'inc/classes/acp.php',
		'inc/classes/puppet_master.php',
		'inc/classes/HTMLGenerator.php',
		'inc/classes/MalleableObject.php',
		'inc/classes/StorableObject.php',
		'inc/classes/WildcardPluginInstaller.php',
	));
}

/* < 2.1.3 */
if (version_compare($puppetMasterOldVersion, '2.1.3', '<')) {
	$removedForumFiles[] = 'inc/classes/WildcardPluginInstaller010202.php';
}

/* remove file(s)/folder(s) */
if (!empty($removedForumFiles)) {
	foreach ($removedForumFiles as $file) {
		@unlink(MYBB_ROOT.$file);
	}
}

if (!empty($removedForumFolders)) {
	foreach ($removedForumFolders as $folder) {
		@my_rmdir_recursive(MYBB_ROOT.$folder);
		@rmdir(MYBB_ROOT.$folder);
	}
}

if (!empty($removedAdminFiles)) {
	foreach ($removedAdminFiles as $file) {
		@unlink(MYBB_ADMIN_DIR.$file);
	}
}

if (!empty($removedAdminFolders)) {
	foreach ($removedAdminFolders as $folder) {
		@my_rmdir_recursive(MYBB_ADMIN_DIR.$folder);
		@rmdir(MYBB_ADMIN_DIR.$folder);
	}
}

?>
