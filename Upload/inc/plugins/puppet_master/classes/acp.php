<?php
/*
 * Plugin Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 */

if (!class_exists('MalleableObject')) {
	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/MalleableObject.php";
}
if (!class_exists('StorableObject')) {
	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/StorableObject.php";
}
if (!class_exists('Puppet')) {
	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/puppet.php";
}
if (!class_exists('PuppetMaster')) {
	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/puppet_master.php";
}
if (!class_exists('HTMLGenerator')) {
	require_once MYBB_ROOT . "inc/plugins/puppet_master/classes/HTMLGenerator.php";
}

?>
