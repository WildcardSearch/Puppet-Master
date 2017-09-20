<?php
/*
 * Plugin Name: Puppet Master for MyBB 1.8.x
 * Copyright 2013 WildcardSearch
 * http://www.rantcentralforums.com
 */

class Puppet extends StorableObject
{
	protected $ownerid = 0;
	protected $uid = 0;
	protected $username = '';
	protected $disp_order = 0;
	protected $table_name = 'puppets';
}

?>
