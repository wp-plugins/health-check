<?php
/*
	Plugin Name: Health Check
	Plugin URI: http://wordpress.org/extend/plugins/health-check/
	Description: Checks the health of your WordPress install
	Author: Peter Westwood
	Version: 0.1-alpha
	Author URI: http://blog.ftwr.co.uk/
 */

class health_check {
	
	function action_plugins_loaded() {
		add_action('admin_menu', array('health_check', 'action_admin_menu'));
		add_action('update_option_wp_beta_tester_stream', array('health_check', 'action_update_option_wp_beta_tester_stream'));
	}

	function action_admin_menu() {
		add_management_page(__('Health Check','health_check'), 'Health Check', 'manage_options', 'health_check', array('health_check','display_page'));
	}

	function display_page() {
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}		
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Health Check','health_check')?></h2>
	</div>
<?php
	}
}
/* Initialise outselves */
add_action('plugins_loaded', array('health_check','action_plugins_loaded'));

?>