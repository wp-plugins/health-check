<?php
/**
 * Tests to check for server config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running the latest and greatest version of Apache
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_Apache_Version extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache )
			return;
		
		preg_match("{Apache/(\d+(?:\.\d+)*)}", $_SERVER['SERVER_SOFTWARE'], $version);
		$version = end($version);
		if ( !$version ) // server software is being silenced...
			return;
		
		$message = sprintf( __( 'Your Webserver is running Apache version %1$s, but the latest version is %2$s. Please contact your host and have them upgrade Apache.', 'health-check' ), $version, HEALTH_CHECK_APACHE_VERSION );
		$this->assertTrue(	version_compare(HEALTH_CHECK_APACHE_VERSION, $version, '<='),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_Apache_Version');


/**
 * Check that we are retrieving the correct IP Address behind a load balancer
 * 
 * @link http://core.trac.wordpress.org/ticket/9235
 * @author Denis de Bernardy
 */
class HealthCheck_IP_Address extends HealthCheckTest {
	function run_test() {
		$using = array();
		$found = false;
		foreach ( array(
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			) as $check ) {
			if ( !isset($_SERVER[$check]) )
				continue;
			$using[] = "\$_SERVER[\"$check\"]";
			$found |= ( strpos($_SERVER[$check], $_SERVER['REMOTE_ADDR']) !== false );
		}
		
		$using = implode(__('</code>, <code>', 'health-check'), $using);
		
		$message = sprintf( __( 'Your Webserver is running behind a load balancer, but the <code>$_SERVER["REMOTE_ADDR"]</code> variable, which WordPress uses as the client\'s IP address, doesn\'t seem to be properly set. WordPress doesn\'t try to automatically extract the real IP address because there are <a href="%1$s">as many setups as there are servers</a>. To fix this, add a few lines in your wp-config.php in order to extract it. One of the following variables should contain the relevant IP address: <code>%2$s</code>.', 'health-check' ), 'http://core.trac.wordpress.org/ticket/9235', $using );
		$this->assertTrue(	!$using || $found,
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_IP_Address');
?>