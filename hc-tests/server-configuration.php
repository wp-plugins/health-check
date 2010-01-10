<?php
/**
 * Tests to check for server config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running the latest and greatest branch of Apache
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_Apache_Version extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;
		
		preg_match("{Apache/(\d+(?:\.\d+)*)}", $_SERVER['SERVER_SOFTWARE'], $version);
		$version = end($version);
		if ( !$version && !HEALTH_CHECK_DEBUG ) // server software is being silenced...
			return;
		
		$message = sprintf( __( 'Your Webserver is running Apache version %1$s, but its latest stable branch is %2$s. Please contact your host and have them upgrade Apache.', 'health-check' ), $version, HEALTH_CHECK_APACHE_VERSION );
		// invert the check because version_compare('1.0', '1.0.0', '>=') returns false
		$this->assertTrue(	version_compare($version, HEALTH_CHECK_APACHE_VERSION, '>='),
							$message,
							HEALTH_CHECK_INFO );
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


/**
 * Check for apache functions and mod_rewrite
 * 
 * @link http://php.net/manual/en/ref.apache.php
 * @author Denis de Bernardy
 */
class HealthCheck_ModRewrite extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;
		$message = sprintf(__( 'Your Webserver does not have <a href="%s">Apache functions</a>. These make it easier for WordPress to detect the availability of Apache modules such as mod_rewrite. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ref.apache.php');
		$passed = $this->assertTrue(function_exists('apache_get_modules'),
									$message,
									HEALTH_CHECK_RECOMMENDATION );

		if ( !$passed ) {
			$message = sprintf(__( 'WordPress failed to detect Apache\'s mod_rewrite module on your Webserver, from lack of proper means to detect it. WordPress assumes it is present, but <a href="%s">Apache functions</a> would be needed to ensure proper detection. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ref.apache.php');
		} else {
			$message = sprintf(__( 'WordPress failed to detect Apache\'s mod_rewrite module on your Webserver. <a href="%s">Fancy permalinks</a> will not work without it, unless you prepend your permalink structure with /index.php.', 'health-check' ), 'options-permalink.php');
		}
		$this->assertTrue(	apache_mod_loaded('mod_rewrite'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_ModRewrite');


/**
 * Check for mod_security
 * 
 * @link http://wordpress.org/search/mod_security?forums=1
 * @link http://wordpress.org/support/topic/256526
 * @author Denis de Bernardy
 */
class HealthCheck_ModSecurity extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;
		$message = sprintf(__( 'Your Webserver has mod_security turned on. While it\'s generally fine to have it turned on, this Apache module ought to be your primary suspect if you experience very weird WordPress issues. In particular random 403/404 errors, random errors when uploading files, random errors when saving a post, or any other random looking errors for that matter. Please contact your host if you experience any of them, and highlight <a href="%s$1">these support threads</a>. Alternatively, visit <a href="%2$s">this support thread</a> for ideas on how to turn it off, if your host refuses to help.', 'health-check' ), 'http://wordpress.org/search/mod_security?forums=1', 'http://wordpress.org/support/topic/256526');
		$this->assertFalse(	apache_mod_loaded('mod_security'),
							$message,
							HEALTH_CHECK_INFO );
	}
}
HealthCheck::register_test('HealthCheck_ModSecurity');


/**
 * Check the memcache status
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_Memcache_Status extends HealthCheckTest {
	function run_test() {
		// skip if we're not using Memcache
		global $_wp_using_ext_object_cache;
		if ( ( !$_wp_using_ext_object_cache || !method_exists('Memcache', 'addServer') ) && !HEALTH_CHECK_DEBUG )
			return;
		
		// some object cache modules are happy with $memcached_servers not being set
		global $memcached_servers;
		if ( isset($memcached_servers) )
			$buckets = $memcached_servers;
		else
			$buckets = array('127.0.0.1');
		reset($buckets);
		if ( is_int(key($buckets)) )
			$buckets = array('default' => $buckets);
		
		$failed = array();
		foreach ( $buckets as $bucket => $servers) {
			$test = new Memcache();
			foreach ( $servers as $server  ) {
				@ list ( $node, $port ) = explode(':', $server);
				if ( !$port )
					$port = ini_get('memcache.default_port');
				$port = intval($port);
				if ( !$port )
					$port = 11211;
				$success = @ $test->connect($node, $port);
				if ( !$success )
					$failed[] = "$node:$port";
				@ $test->close();
			}
		}
		
		$failed = implode(__('</code>, <code>', 'health-check'), $failed);
		
		$message = sprintf(__( 'Your Webserver seems to be using a Memcache-based persistent cache module, and the following memcache nodes seem to be down: <code>%s</code>.', 'health-check' ), $failed);
		$this->assertFalse(	(bool) $failed,
							$message,
							HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_Memcache_Status');


/**
 * Check file permissions
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_Permissions extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;
		
		foreach ( array(
			ABSPATH . 'wp-admin',
			ABSPATH . 'wp-includes',
			WP_CONTENT_DIR,
			) as $dir ) {
			$this->count_executable_files($dir, true);
		}
		
		// ABSPATH could contain a cgi folder, etc., so non-recursive
		$count = $this->count_executable_files(ABSPATH, false);
		
		$message = sprintf(__( 'Your WordPress installation contains %d executable files. This is a security issue. Please contact your host and have them fix this at once.', 'health-check' ), $count);
		$this->assertEquals($count,
							0,
							$message,
							HEALTH_CHECK_ERROR );
	}
	
	function count_executable_files($dir, $recursive = false) {
		static $count = 0;
		$dir = rtrim($dir, '/');
		if ( !( $handle = opendir($dir) ) )
			return $count;
		
		while ( ( $file = readdir($handle) ) !== false ) {
			if ( in_array($file, array('.', '..')) )
				continue;
			$file = "$dir/$file";
			if ( is_file($file) ) {
				if ( is_executable($file) )
					$count++;
			} elseif ( is_dir($file) && $recursive ) {
				$this->count_executable_files($file, $recursive);
			}
		}
		
		return $count;
	}
}
HealthCheck::register_test('HealthCheck_Permissions');
?>