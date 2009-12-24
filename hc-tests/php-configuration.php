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

/**
 * Check that we are running at least PHP 5
 * 
 * @todo Provide a link to a codex article
 * @link http://core.trac.wordpress.org/ticket/9751
 * @author peterwestwood
 *
 */
class HealthCheck_PHP_Version extends HealthCheckTest {
	function run_test() {
		$this->assertTrue(version_compare('5.0.0', PHP_VERSION, '<'), sprintf( __( 'Your Webserver is currently using PHP version %s, which is no longer recieving security updates and will no longer be supported by WordPress in an upcoming version', 'health-check' ), PHP_VERSION ), HEALTH_CHECK_WARNING );
	}
}
HealthCheck::register_test('HealthCheck_PHP_Version');
?>