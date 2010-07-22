<?php
/*
	Plugin Name: Health Check
	Plugin URI: http://wordpress.org/extend/plugins/health-check/
	Description: Checks the health of your WordPress install (and then deactivates itself)
	Author: The Health Check Team
	Version: 0.1
	Author URI: http://wordpress.org/extend/plugins/health-check/
 */
define('HEALTH_CHECK_PHP_VERSION', '5.2');
define('HEALTH_CHECK_MYSQL_VERSION', '5.0.15');

class HealthCheck {
	
	function action_plugins_loaded() {
		add_action('admin_notices', array('HealthCheck', 'action_admin_notice'));
	}

	function action_admin_notice() {
		global $wpdb;
		$php_version_check = version_compare(HEALTH_CHECK_PHP_VERSION, PHP_VERSION, '<');
		$mysql_version_check = version_compare(HEALTH_CHECK_MYSQL_VERSION, $wpdb->db_version(), '<');
		if ( !$php_version_check ) 
			echo "<div id='health-check-warning-php' class='updated fade'><p><strong>".__('Warning:', 'health-check')."</strong> ".sprintf(__('Your server is running PHP version %1$s. WordPress will require PHP version %2$s from version 3.2 onwards ', 'health-check'), PHP_VERSION, HEALTH_CHECK_PHP_VERSION)."</p></div>";
		if ( !$mysql_version_check )
			echo "<div id='health-check-warning-mysql' class='updated fade'><p><strong>".__('Warning:', 'health-check')."</strong> ".sprintf(__('Your server is running mySQL version %1$s. WordPress will require mySQL version %2$s from version 3.2 onwards ', 'health-check'), $wpdb->db_version(), HEALTH_CHECK_MYSQL_VERSION)."</p></div>";
		deactivate_plugins(__FILE__);
	}

}
/* Initialize ourselves */
add_action('plugins_loaded', array('HealthCheck','action_plugins_loaded'));
?>