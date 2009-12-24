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
	var $result = null;
	
	function HealthCheckTest() {
		$this->result = new HealthCheckTestResult();	
	}
	
	function run_test() {
		$this->result->markAsFailed(__('ERROR: Test class does not implement run_test()','health_check'));
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
	function assertEquals($expected, $actual, $message, $severity) {
		if ( $expected !== $actual ) {
			$this->result->markAsFailed($message, $severity);
		} else {
			$this->result->markAsPassed();
		}
		return $this->result->passed;
	}
	
	/**
	 * Check that $actual is true
	 * 
	 * @param mixed $actual The actual value
	 * @param string $message The message to display if they don't match
	 * @param int $severity The severity if they don't match
	 * @return bool Whether or not it was equal.
	 */
	function assertTrue($actual, $message, $severity) {
		if ( !$actual ) {
			$this->result->markAsFailed($message, $severity);
		} else {
			$this->result->markAsPassed();
		}
		return $this->result->passed;
	}
}
?>