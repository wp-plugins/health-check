<?php
/**
 * Base class for all the different Tests to extend
 *
 * @package HealthCheck
 * @subpackage Tests
 */
/**
 * Base class for all the different Tests to extend
 * 
 * Provides utility functionality where necessary
 * 
 * @author peterwestwood
 *
 */
class HealthCheckTest {
	//Store all the test results
	var $results = array();
	//Count the number of assertions in this test
	var $assertions = 0;
	
	function run_test() {
		// If this has not been overridden then the test will always fail
		$this->assertTrue(false, __('ERROR: Test class does not implement run_test()','health-check'), HEALTH_CHECK_ERROR);
	}
	
	/**
	 * Check that $expected and $actual are equal
	 * 
	 * @param mixed $expected The expected value
	 * @param mixed $actual The actual value
	 * @param string $message The message to display if they don't match
	 * @param int $severity The severity if they don't match
	 * @return bool Whether or not it was equal.
	 */
	function assertEquals($expected, $actual, $message, $severity = HEALTH_CHECK_ERROR) {
		$result = new HealthCheckTestResult();
		if ( ( $expected !== $actual ) xor HEALTH_CHECK_DEBUG ) {
			$result->markAsFailed($message, $severity);
		} else {
			$result->markAsPassed();
		}
		
		$this->results[] = $result;
		$this->assertions++;
		
		return $result->passed;
	}

	/**
	 * Check that $expected and $actual are not equal
	 * 
	 * @param mixed $expected The unexpected value
	 * @param mixed $actual The actual value
	 * @param string $message The message to display if they don't match
	 * @param int $severity The severity if they don't match
	 * @return bool Whether or not it was equal.
	 */
	function assertNotEquals($unexpected, $actual, $message, $severity = HEALTH_CHECK_ERROR) {
		$result = new HealthCheckTestResult();
		if ( ( $unexpected === $actual ) xor HEALTH_CHECK_DEBUG ) {
			$result->markAsFailed($message, $severity);
		} else {
			$result->markAsPassed();
		}
		
		$this->results[] = $result;
		$this->assertions++;
		
		return $result->passed;
	}

	/**
	 * Check that $actual is true
	 * 
	 * @param mixed $actual The actual value
	 * @param string $message The message to display if they don't match
	 * @param int $severity The severity if they don't match
	 * @return bool Whether or not it was equal.
	 */
	function assertTrue($actual, $message, $severity = HEALTH_CHECK_ERROR) {
		$result = new HealthCheckTestResult();
		if ( ( !$actual ) xor HEALTH_CHECK_DEBUG ) {
			$result->markAsFailed($message, $severity);
		} else {
			$result->markAsPassed();
		}
		
		$this->results[] = $result;
		$this->assertions++;
		
		return $result->passed;
	}

	/**
	 * Check that $actual is false
	 * 
	 * @param mixed $actual The actual value
	 * @param string $message The message to display if they don't match
	 * @param int $severity The severity if they don't match
	 * @return bool Whether or not it was equal.
	 */
	function assertFalse($actual, $message, $severity = HEALTH_CHECK_ERROR) {
		$result = new HealthCheckTestResult();
		if ( ( $actual ) xor HEALTH_CHECK_DEBUG ) {
			$result->markAsFailed($message, $severity);
		} else {
			$result->markAsPassed();
		}
		
		$this->results[] = $result;
		$this->assertions++;
		
		return $result->passed;
	}
}
?>