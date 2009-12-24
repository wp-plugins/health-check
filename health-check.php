<?php
/*
	Plugin Name: Health Check
	Plugin URI: http://wordpress.org/extend/plugins/health-check/
	Description: Checks the health of your WordPress install
	Author: The Health Check Team
	Version: 0.1-alpha
	Author URI: http://wordpress.org/extend/plugins/health-check/
 */

class HealthCheck {
	
	/*
	 * An array containing the names of all the classes that have registered as tests.
	 */
	static $registered_tests = array();
	
	function action_plugins_loaded() {
		add_action('admin_menu', array('HealthCheck', 'action_admin_menu'));
	}

	function action_admin_menu() {
		add_management_page(__('Health Check','health_check'), __('Health Check','health_check'), 'manage_options', 'health_check', array('HealthCheck','display_page'));
	}

	function display_page() {
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		$step = HealthCheck::_fetch_array_key($_GET, 'step', 0);
		
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Health Check','health_check'); ?></h2>
		<p><?php _e('Welcome to your WordPress health check centre.','health_check');?></p>
<?php
		if (0 == $step) {
?>
		<p><?php _e('Click on go to run a number of tests on your site and report back on any issues.','health_check');?></p>
		<p class="submit"><a type="submit" class="button-primary" href="<?php echo admin_url("tools.php?page=health_check&step=1");?>"><?php _e('Go','health_check') ?></a></p>
		
<?php
		} else {
			//Lazy load our includes and all the tests we will run
			HealthCheck::load_includes();
			HealthCheck::load_tests();
			HealthCheck::run_tests();
		}
?>
	</div>
<?php
	}
	
	function run_tests() {
		foreach (HealthCheck::$registered_tests as $classname) {
			if ( class_exists( $classname ) ) {
				$class = new $classname;
				if (HealthCheck::_is_health_check_test($class) ) {
					$class->run_test();
					$res = $class->result;
				} else {
					$res = new HealthCheckTestResult();
					$res->markAsFailed( sprintf( __('Class %s has been registered as a test but it is not a subclass of HealthCheckTest.'), $classname), HEALTH_CHECK_ERROR);
				}
			} else {
				$res = new HealthCheckTestResult();
				$res->markAsFailed( __('Class %s has been registered as a test but it has not been defined.'), HEALTH_CHECK_ERROR);
			}
			//TODO better formatting
			echo $res->message;
		}
	}
	
	/**
	 * Make note of the name of the registered test class ready for when we want to run the tests.
	 * 
	 * @param string $classname The name of a class which subclasses HealthCheckTest.
	 * @return none
	 */
	function register_test($classname) {
		HealthCheck::$registered_tests[] = $classname;
	}
	/**
	 * Load all the test classes we have.
	 * 
	 * Each test class must also be registered by calling HealthCheck::register_test()
	 * 
	 * @return none
	 */
	function load_tests() {
		$hc_tests_dir = plugin_dir_path(__FILE__) . 'hc-tests/';
		if ( WP_DEBUG )
			require_once($hc_tests_dir . 'dummy-test.php');
		require_once($hc_tests_dir . 'php-configuration.php');
	}
	
	/**
	 * Load in our include files.
	 * 
	 * @return none
	 */
	function load_includes() {
		$hc_includes = plugin_dir_path(__FILE__) . 'hc-includes/';
		require_once($hc_includes . 'class.health-check-test.php');
		require_once($hc_includes . 'class.health-check-test-result.php');
	}
	
	/**
	 * Retrieves a value from an array by key without a notice
	 */
	function _fetch_array_key( $array, $key, $default = '' ) {
		return isset( $array[$key] )? $array[$key] : $default;
	}
	
	function _is_health_check_test( $object ) {
		return is_subclass_of( $object, 'HealthCheckTest');
	}
}
/* Initialise outselves */
add_action('plugins_loaded', array('HealthCheck','action_plugins_loaded'));

?>