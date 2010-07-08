<?php
/**
 * Dependancy management for Tests
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Dependancy management for Tests using the BackPress dependancy APIs
 *
 */
class HealthCheckTests extends WP_Dependencies {
	// Record the number of Tests we have run
	var $tests_run = 0;
	
	// Record the number of assertions the tests ran
	var $assertions = 0;
	
	function do_item( $classname, $group = false ) {
		if ( HEALTH_CHECK_DEBUG && is_string(HEALTH_CHECK_DEBUG) && HEALTH_CHECK_DEBUG != $classname )
			return false;

		$results = array();

		if ( class_exists( $classname ) ) {
			$class = new $classname;
			if (HealthCheck::_is_health_check_test($class) ) {
				$class->run_test();
				$results = $class->results;
				$this->tests_run++;
				$this->assertions += $class->assertions;
			} else {
				$res = new HealthCheckTestResult();
				$res->markAsFailed( sprintf( __('Class %s has been registered as a test but it is not a subclass of HealthCheckTest.'), $classname), HEALTH_CHECK_ERROR);
				$results[] = $res;
			}
		} else {
			$res = new HealthCheckTestResult();
			$res->markAsFailed( sprintf( __('Class %s has been registered as a test but it has not been defined.'), $classname), HEALTH_CHECK_ERROR);
			$results[] = $res;
		}

		// Save results grouped by severity
		foreach ($results as $res) {
			$GLOBALS['_HealthCheck_Instance']->test_results[$res->severity][] = $res;
		}
	}
}
?>