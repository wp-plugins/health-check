<?php
/**
 * Tests to check for server config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running the latest and greatest branch of Apache
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
 * Check that the HTTP API works
 * 
 * @link http://wordpress.org/extend/plugins/core-control/
 */
class HealthCheck_HTTP_API extends HealthCheckTest {
	function run_test() {
		$url = admin_url('admin-post.php?action=health-check');
		$res = wp_remote_fopen($url);
		
		$message = sprintf(__( 'WordPress failed the HTTP API test. If this check consistently fails, consider installing the <a href="%s">Core Control plugin</a>, and trying a different HTTP Transport.', 'health-check' ), 'http://wordpress.org/extend/plugins/core-control/' );
		$passed = $this->assertEquals(	$res,
										'OK',
										$message,
										HEALTH_CHECK_ERROR );
		
		wp_cache_set('http_api', intval($passed), 'health_check');
	}
}
HealthCheck::register_test('HealthCheck_HTTP_API');


/**
 * Check that a favicon file exists
 * 
 * @link http://codex.wordpress.org/Creating_a_Favicon
 * @link http://core.trac.wordpress.org/ticket/3426
 */
class HealthCheck_Favicon extends HealthCheckTest {
	function run_test() {
		if ( !wp_cache_get('http_api', 'health_check') && !HEALTH_CHECK_DEBUG )
			return;
		
		// the site might be in a subfolder
		$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . '/favicon.ico';
		$res = wp_remote_head($url);
		
		if ( preg_replace("{^[^/]+://}", '', get_option('home')) == $_SERVER['HTTP_HOST'] ) {
			$message = sprintf(__( 'Your WordPress installation doesn\'t seem to have a <a href="%1$s">favicon file</a>. This can <a href="%2$s">significantly impact your site\'s performance</a>. Consider adding such a file so 404 errors don\'t occur on every page load.', 'health-check' ), 'http://codex.wordpress.org/Creating_a_Favicon', 'http://core.trac.wordpress.org/ticket/3426');
			$importance = HEALTH_CHECK_RECOMMENDATION;
		} else {
			$message = sprintf(__( 'Your domain doesn\'t seem to have a <a href="%1$s">favicon file</a>. Consider adding such a file so 404 errors don\'t occur on every page load.', 'health-check' ), 'http://codex.wordpress.org/Creating_a_Favicon');
			$importance = HEALTH_CHECK_INFO;
		}
		$this->assertNotEquals(	$res['response']['code'],
								404,
								$message,
								$importance );
	}
}
HealthCheck::register_test('HealthCheck_Favicon', array( 'HealthCheck_HTTP_API' ) );


/**
 * Check that the cron works
 * 
 * @link http://wordpress.org/extend/plugins/core-control/
 */
class HealthCheck_Cron extends HealthCheckTest {
	function run_test() {
		if ( !wp_cache_get('http_api', 'health_check') && !HEALTH_CHECK_DEBUG )
			return;
		
		if ( !get_transient('health_check_activated')
			|| ( time() - get_transient('health_check_activated') <= 3600 ) ) {
			$message = __( 'The WordPress Cron test has yet to run. Please try again in a few minutes.', 'health-check' );
			$importance = HEALTH_CHECK_INFO;
		} else {
			$message = sprintf(__( 'The WordPress cron doesn\'t seem to be working. If this check consistently fails, consider installing the <a href="%s">Core Control plugin</a>, and trying a different HTTP Transport.', 'health-check' ), 'http://wordpress.org/extend/plugins/core-control/' );
			$importance = HEALTH_CHECK_ERROR;
		}
		$this->assertTrue(	get_transient('health_check_cron_check')
							&& ( time() - get_transient('health_check_cron_check') <= 86400 ),
							$message,
							$importance );
	}
}
HealthCheck::register_test('HealthCheck_Cron', array( 'HealthCheck_HTTP_API' ) );


/**
 * Check that the XML-RPC API works
 */
class HealthCheck_XMLRPC extends HealthCheckTest {
	function run_test() {
		if ( ( !wp_cache_get('http_api', 'health_check') || !get_option('enable_xmlrpc') || !is_file(ABSPATH . '/xmlrpc.php') ) && !HEALTH_CHECK_DEBUG )
			return;
		
		$url = trailingslashit(get_option('home'));
		$res = wp_remote_fopen($url);

		ob_start();
		rsd_link();
		$rsd_link = trim(ob_get_contents());
		ob_end_clean();

		$message = __( 'Your WordPress installation doesn\'t seem to be exposing its XML-RPC interface. Please make sure that you theme\'s <code>header.php</code> file contains the following template tag: <code>&lt;php wp_head() ?&gt;</code>.', 'health-check' );
		$this->assertTrue(	strpos($res, $rsd_link) !== false,
							$message,
							HEALTH_CHECK_ERROR );
		
		$url = site_url('/xmlrpc.php?rsd');
		$res = wp_remote_fopen($url);
		$charset = strtoupper(get_option('blog_charset'));
		$checked = false;
		$success = false;
		if ( extension_loaded('simplexml') ) {
			$checked = true;
			$success = @simplexml_load_string($res);
		} elseif ( function_exists('xml_parser_create') && in_array($charset, array('UTF-8', 'ISO-8859-1', 'US-ASCII')) ) {
			// http://php.net/manual/en/function.xml-parser-create.php
			$checked = true;
			$parser = xml_parser_create($charset);
			$success = @xml_parse($parser, $res, true);
			@xml_parser_free($parser);
		}
		
		if ( $checked || HEALTH_CHECK_DEBUG ) {
			$message = sprintf( __( 'Your WordPress installation\'s XML-RPC interface doesn\'t return a valid XML response. Typically, this means that your host is blocking %s or it is inserting ads in it. Please get in touch with them to have them fix this.', 'health-check' ), $url );
			$passed = $this->assertTrue((bool) $success,
										$message,
										HEALTH_CHECK_ERROR );
		} else { // skip the next check, since we can't parse the reply
			$passed = false;
		}

		if ( $passed || HEALTH_CHECK_DEBUG ) {
			require_once ABSPATH . WPINC . '/class-IXR.php';
			$rpc = new IXR_Client($url);

			$message = sprintf( __( 'Your WordPress installation\'s XML-RPC interface doesn\'t seem to be working. Chances are that your host is blocking %s. Please get in touch with them to have them fix this.', 'health-check' ), $url );
			$this->assertTrue(	$rpc->query('system.listMethods'),
								$message,
								HEALTH_CHECK_ERROR );
		}
	}
}
HealthCheck::register_test('HealthCheck_XMLRPC', array( 'HealthCheck_HTTP_API' ) );


/**
 * Check the memcache status
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
 * Check that the wp-content dir and/or the uploads dir is writable
 */
class HealthCheck_Permissions extends HealthCheckTest {
	function run_test() {
		$func = create_function('', 'return 0;');
		add_filter('pre_option_uploads_use_yearmonth_folders', $func);
		$upload_dir = wp_upload_dir();
		remove_filter('pre_option_uploads_use_yearmonth_folders', $func);
		
		$message = __( 'Your WordPress installation cannot write to your <code>wp-content/uploads</code> folder. This prevents file uploads from working properly.', 'health-check' );
		$passed = $this->assertTrue(!is_wp_error($upload_dir) && is_writable($upload_dir['basedir']),
									$message,
									HEALTH_CHECK_ERROR );

		if ( $passed ) {
			wp_cache_set('test_dir', $upload_dir['basedir'], 'health_check');
			wp_cache_set('test_url', $upload_dir['baseurl'], 'health_check');
			$message = __( 'Your WordPress installation cannot write to your <code>wp-content</code> folder. This can prevent some plugins working properly.', 'health-check' );
			$importance = HEALTH_CHECK_INFO;
		} else {
			$message = __( 'Your WordPress installation cannot write to your <code>wp-content</code> folder. This prevents WordPress from setting up an uploads folder, and it can prevent some plugins working properly.', 'health-check' );
			$importance = HEALTH_CHECK_ERROR;
		}

		$passed = $this->assertTrue(is_writable(WP_CONTENT_DIR),
									$message,
									$importance );
		if ( $passed ) {
			// prefer to do tests in WP_CONTENT_DIR in case an access restriction script is around
			wp_cache_set('test_dir', WP_CONTENT_DIR, 'health_check');
			wp_cache_set('test_url', WP_CONTENT_URL, 'health_check');
		}

		$message = __( 'Your WordPress installation cannot write to your <code>wp-config.php</code> file. This prevent can prevent cache plugins from enabling/disabling themselves automatically.', 'health-check' );
		$passed = $this->assertTrue(is_writable(ABSPATH . 'wp-config.php'),
									$message,
									HEALTH_CHECK_INFO );

		$message = __( 'Your WordPress installation cannot write to your <code>.htaccess</code> file. This prevent can prevent plugins from some of their functionality automatically.', 'health-check' );
		$passed = $this->assertTrue(is_writable(ABSPATH . '.htaccess'),
									$message,
									HEALTH_CHECK_INFO );
	}
}
HealthCheck::register_test('HealthCheck_Permissions');


/**
 * Check for executable files
 */
class HealthCheck_Executable extends HealthCheckTest {
	function run_test() {
		foreach ( array(
			ABSPATH . 'wp-admin',
			ABSPATH . WPINC,
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
HealthCheck::register_test('HealthCheck_Executable');


/**
 * Check for apache functions and mod_rewrite
 * 
 * @link http://php.net/manual/en/ref.apache.php
 * @todo Remove error suppression
 */
class HealthCheck_ModRewrite extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;
		
		$test_dir = rtrim(wp_cache_get('test_dir', 'health_check'), '/');
		$test_url = rtrim(wp_cache_get('test_url', 'health_check'), '/');
		$http_api = wp_cache_get('http_api', 'health_check');
		$checked = false;
		switch ( true ) {
		default:
			if ( !$test_dir || !$test_url || !$http_api || !wp_mkdir_p($test_dir . '/health-check') )
				break;
			// we might be able to test that mod_rewrite actually *works*
			$test_path = parse_url($test_url);
			$test_path = $test_path['path'];
			$htaccess = <<<EOS

RewriteEngine On
RewriteBase $test_path/health-check/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ $test_path/health-check/test.txt [L]

EOS;
			// a reliable file lock is only around in PHP 5.1
			// and there's little point in acquiring any
			if ( !( $fp = @fopen("$test_dir/health-check/.htaccess", 'w') )
				|| !@fwrite($fp, $htaccess) || !@fclose($fp) )
				break; // bail

			if ( !( $fp = @fopen("$test_dir/health-check/test.txt", 'w') )
				|| !@fwrite($fp, 'OK') || !@fclose($fp) )
				break; // bail

			$res = wp_remote_get("$test_url/health-check/");

			$checked = true;

			$message = sprintf(__('Your Webserver does not have mod_rewrite, or WordPress cannot enable its custom rewrite rules using a .htaccess file. This will prevent <a href="%s">fancy URLs</a> from working on your site without prepending them with /index.php. Please contact your host to have them fix this.', 'health-check'), 'options-permalink.php');
			$this->assertTrue(	( $res['response']['code'] == 200 ) && ( $res['body'] == 'OK' ),
								$message,
								HEALTH_CHECK_RECOMMENDATION );
		}

		@unlink("$test_dir/health-check/.htaccess");
		@unlink("$test_dir/health-check/test.txt");
		@rmdir("$test_dir/health-check/");

		if ( !$checked || HEALTH_CHECK_DEBUG ) {
			$message = sprintf(__('WordPress failed to detect mod_write on your Webserver. It might be around... or not. If can prevent <a href="%s">fancy URLs</a> from working on your site without prepending them with /index.php. Please contact your host to have them fix this.', 'health-check'), 'options-permalink.php');
			$this->assertTrue(	apache_mod_loaded('mod_rewrite'),
								$message,
								HEALTH_CHECK_INFO );
		}
	}
}
HealthCheck::register_test('HealthCheck_ModRewrite');


/**
 * Check for mod_security
 * 
 * @link http://wordpress.org/search/mod_security?forums=1
 * @link http://wordpress.org/support/topic/256526
 */
class HealthCheck_ModSecurity extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		global $is_apache;
		if ( !$is_apache && !HEALTH_CHECK_DEBUG )
			return;

		$message = sprintf(__( 'Your Webserver has mod_security turned on. While it\'s generally fine to have it turned on, this Apache module ought to be a primary suspect if you experience very weird WordPress issues. In particular random 403/404 errors, random errors when uploading files, random errors when saving a post, or any other random looking errors for that matter. Please contact your host if you experience any of them, and highlight <a href="%s$1">these support threads</a>. Alternatively, visit <a href="%2$s">this support thread</a> for ideas on how to turn it off, if your host refuses to help.', 'health-check' ), 'http://wordpress.org/search/mod_security?forums=1', 'http://wordpress.org/support/topic/256526');
		$passed = $this->assertFalse(	apache_mod_loaded('mod_security'),
										$message,
										HEALTH_CHECK_INFO );

		if ( !$passed && !HEALTH_CHECK_DEBUG )
			return; // stop here, since we're already sure it's around

		$test_dir = rtrim(wp_cache_get('test_dir', 'health_check'), '/');
		$test_url = rtrim(wp_cache_get('test_url', 'health_check'), '/');
		$http_api = wp_cache_get('http_api', 'health_check');
		switch ( true ) {
		default:
			if ( !$test_dir || !$test_url || !$http_api || !wp_mkdir_p($test_dir . '/health-check') )
				break;
			// try to detect mod_security in a fast cgi environment
			$test_path = parse_url($test_url);
			$test_path = $test_path['path'];
			$htaccess = <<<EOS

SecFilterEngine On

EOS;
			// a reliable file lock is only around in PHP 5.1
			// and there's little point in acquiring any
			if ( !( $fp = @fopen("$test_dir/health-check/.htaccess", 'w') )
				|| !@fwrite($fp, $htaccess) || !@fclose($fp) )
				break; // bail

			if ( !( $fp = @fopen("$test_dir/health-check/test.txt", 'w') )
				|| !@fwrite($fp, 'OK') || !@fclose($fp) )
				break; // bail

			$res = wp_remote_get("$test_url/health-check/test.txt");

			$this->assertEquals($res['response']['code'],
								500,
								$message,
								HEALTH_CHECK_INFO );
		}

		@unlink("$test_dir/health-check/.htaccess");
		@unlink("$test_dir/health-check/test.txt");
		@rmdir("$test_dir/health-check/");
	}
}
HealthCheck::register_test('HealthCheck_ModSecurity');


/**
 * Check that the Webserver's process user is the same as the file owner
 * 
 * @link http://httpd.apache.org/docs/2.2/suexec.html
 */
class HealthCheck_ProcessUser extends HealthCheckTest {
	function run_test() {
		$test_dir = rtrim(wp_cache_get('test_dir', 'health_check'), '/');
		if ( ( !$test_dir || !function_exists('getmyuid') || !function_exists('fileowner') ) && !HEALTH_CHECK_DEBUG )
			return; // can't test...
		$test_file = $test_dir . '/health-check-' . time();
		$fp = @fopen($test_file, 'w');
		$check = false;
		if ( $fp ) {
			$check = ( getmyuid() == @fileowner($test_file) );
			@fclose($fp);
			@unlink($test_file);
		}
		
		$message = sprintf( __( 'Your Webserver is not running as the filesystem owner of your WordPress files. Were it doing so, WordPress upgrades (core, theme and plugin) would be faster and more reliable. This can be achieved by using <a href="%s">suExec</a>, for instance. Please enquire with your host.', 'health-check' ), 'http://httpd.apache.org/docs/2.2/suexec.html');
		$passed = $this->assertTrue($check,
									$message,
									HEALTH_CHECK_INFO );
	}
}
HealthCheck::register_test('HealthCheck_ProcessUser');
?>