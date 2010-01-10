<?php
/**
 * Class to encapsulate the test results
 *
 * @package HealthCheck
 * @subpackage Tests
 */

// Severity levels for the reports
define( 'HEALTH_CHECK_UNKNOWN',			-1 );
define( 'HEALTH_CHECK_OK',				 1 );
define( 'HEALTH_CHECK_INFO',			 2 );
define( 'HEALTH_CHECK_PENDING',			 3 );
define( 'HEALTH_CHECK_RECOMMENDATION',	 4 );
define( 'HEALTH_CHECK_ERROR',			 5 );

// Debug mode: set to true in wp-config.php in order to invert successful/failed tests
if ( !defined('HEALTH_CHECK_DEBUG') )
	define( 'HEALTH_CHECK_DEBUG', false );

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
	 * @param string $message Message to display to the user
	 * @param int $severity Optional. Severity level for this failure default is HEALTH_CHECK_ERROR.
	 * @return none
	 */
	function markAsFailed($message, $severity = HEALTH_CHECK_ERROR) {
		$this->passed = false;
		$this->message = $message;
		$this->severity = $severity;
	}

	/**
	 * Mark this test result as a success.
	 * 
	 * @param string $message Optional Message to display to the user
	 * @return none
	 */
	function markAsPassed($message = '') {
		$this->passed = true;
		$this->message = $message;
		$this->severity = HEALTH_CHECK_OK;
	}
}
?>