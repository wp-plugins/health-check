<?php
/**
 * Class to encapsulate the test results
 *
 * @package HealthCheck
 * @subpackage Tests
 */

// Severity levels for the reports
define( 'HEALTH_CHECK_UNKNOWN',		-1 );
define( 'HEALTH_CHECK_OK',			 1 );
define( 'HEALTH_CHECK_WARNING',		 2 );
define( 'HEALTH_CHECK_ERROR',		 3 );

/**
 * Class to encapsulate the test results
 * 
 * @author peterwestwood
 *
 */
class HealthCheckTestResult {
	var $passed = false;
	var $message = "";
	var $severity = HEALTH_CHECK_UNKNOWN;
	
	function HealthCheckTestResult() {}
	
	/**
	 * Mark this test result as a failure.
	 * 
	 * @param $message Message to display to the user
	 * @param $severity Optional. Severity level for this failure default is HEALTH_CHECK_ERROR.
	 * @return none
	 */
	function markAsFailed($message, $severity = HEALTH_CHECK_ERROR) {
		$this->passed = $false;
		$this->message = $message;
		$this->severity = $severity;
	}
}
?>