<?php
/*
	Plugin Name: Health Check
	Plugin URI: http://wordpress.org/extend/plugins/health-check/
	Description: Checks the health of your WordPress install
	Author: The Health Check Team
	Version: 0.1-alpha
	Author URI: http://wordpress.org/extend/plugins/health-check/
 */

class health_check {
	
	function action_plugins_loaded() {
		add_action('admin_menu', array('health_check', 'action_admin_menu'));
	}

	function action_admin_menu() {
		add_management_page(__('Health Check','health_check'), 'Health Check', 'manage_options', 'health_check', array('health_check','display_page'));
	}

	function display_page() {
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		//Lazy load all the tests we will run
		health_check::load_tests();
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Health Check','health_check'); ?></h2>
		<p><?php _e('Welcome to your WordPress health check centre.','health_check');?></p>
		<p><?php _e('I am now going to run a number of tests on your site and report back on any issues I find.','health_check');?></p>
		<?php do_action('health_check_run_tests'); ?>
	</div>
<?php
	}
	
	function load_tests() {
		//TODO include the test files.
		$tests_dir = plugin_dir_path(__FILE__) . 'tests/';
		require_once($tests_dir . 'php-configuration.php');
	}
}
/* Initialise outselves */
add_action('plugins_loaded', array('health_check','action_plugins_loaded'));

?>