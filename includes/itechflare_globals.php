<?php

//Defined Textdomain
define('ITECHARFALYTEXTDOMAIN', wp_get_theme()->get( 'TextDomain' ));

global $itech_arfaly_globals;

$itech_arfaly_globals = array();

$itech_arfaly_globals['errors'] = array();
$itech_arfaly_globals['notifications'] = array();
$itech_arfaly_globals['version'] = '1.3';
$itech_arfaly_globals['domain'] = 'itech_arfaly';
$itech_arfaly_globals['wpurl'] = get_bloginfo('wpurl').'/';
$itech_arfaly_globals['admin_url'] = $itech_arfaly_globals['wpurl'].'wp-admin/';
$itech_arfaly_globals['option_name'] = 'itech_plugin_data';
$itech_arfaly_globals['destination_folder'] = 'uploads';
$itech_arfaly_globals['max_file_size'] = 1024 * 1024 * 10;

?>
