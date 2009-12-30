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
	static $test_results = array();
	static $tests_run = 0;
	static $assertions = 0;
	
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
		
		//Check the nonce and otherwise only display the entry page
		if ( HealthCheck::_verify_nonce('health-check') ) {
			$step = HealthCheck::_fetch_array_key($_GET, 'step', 0);
		} else {
			$step = 0;
		}
		
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2><?php _e('Health Check','health_check'); ?></h2>
		<p><?php _e('Welcome to your WordPress health check centre.','health_check');?></p>
<?php
		if (0 == $step) {
?>
		<p><?php _e('Click on go to run a number of tests on your site and report back on any issues.','health_check');?></p>
		<p class="submit"><a type="submit" class="button-primary" href="<?php echo wp_nonce_url( admin_url( 'tools.php?page=health_check&step=1'), 'health-check');?>"><?php _e('Go','health_check') ?></a></p>
<?php
		} elseif ( 1 == $step ) {
			//Lazy load our includes and all the tests we will run
			HealthCheck::load_includes();
			HealthCheck::load_tests();
			HealthCheck::run_tests();
			HealthCheck::output_test_stats();
		}
?>
	</div>
<?php
	}
	
	/**
	 * Run all the tests that have been registered and store the results for outputting in a sorted fashion
	 * 
	 * @return none
	 */
	function run_tests() {
		foreach (HealthCheck::$registered_tests as $classname) {
			$results = array();
			
			if ( class_exists( $classname ) ) {
				$class = new $classname;
				if (HealthCheck::_is_health_check_test($class) ) {
					$class->run_test();
					$results = $class->results;
					HealthCheck::$tests_run++;
					HealthCheck::$assertions += $class->assertions;
				} else {
					$res = new HealthCheckTestResult();
					$res->markAsFailed( sprintf( __('Class %s has been registered as a test but it is not a subclass of HealthCheckTest.'), $classname), HEALTH_CHECK_ERROR);
					$results[] = $res;
				}
			} else {
				$res = new HealthCheckTestResult();
				$res->markAsFailed( __('Class %s has been registered as a test but it has not been defined.'), HEALTH_CHECK_ERROR);
				$results[] = $res;
			}
			// Save results grouped by severity
			foreach ($results as $res) {
				HealthCheck::$test_results[$res->severity][] = $res;
			}
		}
	}
	
	function output_test_stats() {
		$passed				= empty( HealthCheck::$test_results[HEALTH_CHECK_OK] )				? 0 : count( HealthCheck::$test_results[HEALTH_CHECK_OK] );
		$errors				= empty( HealthCheck::$test_results[HEALTH_CHECK_ERROR] )			? 0 : count( HealthCheck::$test_results[HEALTH_CHECK_ERROR] );
		$recommendations	= empty( HealthCheck::$test_results[HEALTH_CHECK_RECOMMENDATION] )	? 0 : count( HealthCheck::$test_results[HEALTH_CHECK_RECOMMENDATION] );
?>
		<p><?php echo sprintf( __('Out of %1$d tests with %2$d assertions run %3$d passed, %4$d detected errors, and %5$d failed with recommendations.','health_check'), HealthCheck::$tests_run, HealthCheck::$assertions, $passed, $errors, $recommendations );?></p>
<?php
		if ($errors) {
			echo '<div id="health-check-errors">';
			foreach (HealthCheck::$test_results[HEALTH_CHECK_ERROR] as $res) {
				echo sprintf( __('ERROR: %s') ,$res->message) . '<br/>';
			}
			echo '</div>';
		}
		if ($recommendations) {
			echo '<div id="health-check-recommendations">';
			foreach (HealthCheck::$test_results[HEALTH_CHECK_RECOMMENDATION] as $res) {
				echo sprintf( __('RECOMMENDATION: %s') ,$res->message) . '<br/>';
			}
			echo '</div>';
		}
		if ($passed) {
			echo '<div id="health-check-ok">';
			foreach (HealthCheck::$test_results[HEALTH_CHECK_OK] as $res) {
				if ( !empty($res->message) )
					echo $res->message . '<br/>';
			}
			echo '</div>';
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
		//Uncomment for testing purposes only
		//require_once($hc_tests_dir . 'dummy-test.php');
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

	/**
	 * Check to see if the supplied object is an instance of a class which extends HealthCheckTest
	 * 
	 * @param object $object The objct to check
	 * @return bool True if it is, false if it isn't
	 */
	function _is_health_check_test( $object ) {
		return is_subclass_of( $object, 'HealthCheckTest');
	}

	/**
	 * Verify the nonce in the url
	 * 
	 * @param $action The nonce action to verify
	 * @return bool Whether or not it verified
	 */
	function _verify_nonce($action) {
		$_wpnonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
		return wp_verify_nonce($_wpnonce, $action);
	}
}
/* Initialise outselves */
add_action('plugins_loaded', array('HealthCheck','action_plugins_loaded'));

?>