<?php
/**
 * Dummy tests to check that everything is working.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Dummy test to check that everything is working for passing tests.
 *
 */
class HealthCheck_DummyTest_Pass extends HealthCheckTest {
	function run_test() {
		$result = new HealthCheckTestResult();
		$result->markAsPassed( __( 'Dummy Test Ran ok.', 'health-check' ) );
		$this->results[] = $result;
	}
}

HealthCheck::register_test( 'HealthCheck_DummyTest_Pass' );

/**
 * Dummy test to check that everything is working for failing with recommendation tests.
 *
 */
class HealthCheck_DummyTest_Recommendation extends HealthCheckTest {
	function run_test() {
		$this->assertTrue(false, __( 'Dummy Test Ran ok.', 'health-check' ), HEALTH_CHECK_RECOMMENDATION );
	}
}

HealthCheck::register_test( 'HealthCheck_DummyTest_Recommendation' );

/**
 * Dummy test to check that everything is working for failing with error tests.
 *
 */
class HealthCheck_DummyTest_Error extends HealthCheckTest {
	function run_test() {
		$this->assertTrue(false, __( 'Dummy Test Ran ok.', 'health-check' ) );
	}
}

HealthCheck::register_test( 'HealthCheck_DummyTest_Error' );

?>