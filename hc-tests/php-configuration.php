<?php
/**
 * Tests to check for php config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */
/**
 * Check that default_charset is not set to a bad value in php.ini
 * 
 * Validates against the following rules:
 * 
 * 	Max 40 chars
 * 	A-Z
 *  
 * @link http://www.w3.org/International/O-HTTP-charset
 * @link http://www.iana.org/assignments/character-sets
 * @link http://blog.ftwr.co.uk/archives/2009/09/29/missing-dashboard-css-and-the-perils-of-smart-quotes/
 * @author peterwestwood
 *
 */
class HealthCheck_PHP_DefaultCharset extends HealthCheckTest {
	function run_test() {
		$configured = ini_get('default_charset');
		$filtered = preg_replace('|[^a-z0-9_.\-:]|i', '', $configured);
		$this->assertEquals($configured, $filtered, sprintf( __( 'Default character set configured in php.ini %s contains illegal characters.', 'health-check' ), $configured), HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_PHP_DefaultCharset');
?>