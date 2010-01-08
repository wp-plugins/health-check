<?php
/**
 * Tests to check for php config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running at least PHP 5
 * 
 * @todo Provide a link to a codex article
 * @link http://core.trac.wordpress.org/ticket/9751
 * @link http://www.php.net/archive/2007.php#2007-07-13-1
 * @author peterwestwood
 */
class HealthCheck_PHP_Version extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP version %1$s. WordPress will no longer support it in future version because it is <a href="%2$s">no longer receiving security updates</a>. Please contact your host and have them fix this as soon as possible.', 'health-check' ), PHP_VERSION, 'http://www.php.net/archive/2007.php#2007-07-13-1' );
		$this->assertTrue(	version_compare('5.0.0', PHP_VERSION, '<'),
							$message,
							HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_PHP_Version');


/**
 * Check that we don't have safe_mode
 * 
 * @link http://php.net/manual/en/features.safe-mode.php
 * @author Denis de Bernardy
 */
class HealthCheck_SafeMode extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP with safe_mode turned on. In addition to being an <a href="%1$s">architecturally incorrect way to secure a web server</a>, this introduces scores of quirks in PHP. It has been deprecated in PHP 5.3 and dropped in PHP 6.0. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/features.safe-mode.php' );
		$this->assertFalse(	(bool) ini_get('safe_mode'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_SafeMode');


/**
 * Check that we don't have an open_basedir restriction
 * 
 * @link http://php.net/manual/en/features.safe-mode.php
 * @author Denis de Bernardy
 */
class HealthCheck_OpenBaseDir extends HealthCheckTest {
	function run_test() {
		$message = __( 'Your Webserver is running PHP with an open_basedir restriction. This is a constant source of grief in WordPress and other PHP applications. Among other problems, it can prevent uploaded files from being organized in folders, and it can prevent some plugins from working. Please contact your host to have them fix this.', 'health-check' );
		$this->assertFalse(	(bool) ini_get('open_basedir'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_OpenBaseDir');


/**
 * Check that globals aren't registered
 * 
 * @link http://php.net/manual/en/ini.core.php#ini.register-globals
 * @author Denis de Bernardy
 */
class HealthCheck_RegisterGlobals extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP with register globals turned on. This is a source of many application\'s security problems (though not WordPress), and it is a source of constant grief in PHP applications. It has been <a href="%1$s">deprecated in PHP 5.3 and dropped in PHP 6.0</a>. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ini.core.php#ini.register-globals' );
		$this->assertFalse(	(bool) ini_get('register_globals'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_RegisterGlobals');


/**
 * Check that magic quotes are turned off
 * 
 * @link http://php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc
 * @author Denis de Bernardy
 */
class HealthCheck_MagicQuotes extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP with magic quotes turned on. This slows down web applications, and is a source of constant grief in PHP applications. It has been <a href="%1$s">deprecated in PHP 5.3 and dropped in PHP 6.0</a>. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc' );
		$this->assertFalse(	(bool) ini_get('magic_quotes_gpc'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MagicQuotes');


/**
 * Check that long arrays are turned off
 * 
 * @link http://php.net/manual/en/ini.core.php#ini.register-long-arrays
 * @author Denis de Bernardy
 */
class HealthCheck_LongArrays extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP with register long arrays turned on. This slows down web applications. It has been <a href="%1$s">deprecated in PHP 5.3 and dropped in PHP 6.0</a>. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ini.core.php#ini.register-long-arrays' );
		$this->assertFalse(	(bool) ini_get('register_long_arrays'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_LongArrays');


/**
 * Check that there is enough memory
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_MemoryLimit extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP with a low memory limit (%s). This can occasionally prevent WordPress from working. In particular during core upgrades, if you use a theme with lots of functionality, or if you enable multitudes of plugins. Depending on how your server is configured, running into this memory limit would reveal some kind of "Failed to allocate memory" error, an incomplete screen, or a completely blank screen. Please contact your host to have them increase the memory limit to 32M or more. (48M or even 64M might be needed if you enable many plugins.)', 'health-check' ), ini_get('memory_limit') );
		$this->assertTrue(	!ini_get('memory_limit') || ( intval(ini_get('memory_limit')) >= 32 ),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MemoryLimit');


/**
 * Check that the memory limit can be overridden
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_MemoryLimitOverride extends HealthCheckTest {
	function run_test() {
		$original_limit = ini_get('memory_limit');
		$test = 247;
		if ( $test == intval($original) )
			$test++;
		@ini_set('memory_limit', "{$test}M");
		$message = __( 'Your Webserver disallows PHP to increase the memory limit at run time. This can occasionally prevent WordPress from working. In particular during core upgrades, where WordPress tries to increase it to 256M in order to unzip core files. Depending on how your server is configured, running into this memory limit would reveal some kind of "Failed to allocate memory" error, an incomplete screen, or a completely blank screen. Please contact your host to have them fix this.', 'health-check' );
		$this->assertTrue(	( intval(ini_get('memory_limit')) == $test ),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		@ini_set('memory_limit', $original_limit); // restore original limit
	}
}
HealthCheck::register_test('HealthCheck_MemoryLimitOverride');


/**
 * Check for apache functions
 * 
 * @link http://php.net/manual/en/ref.apache.php
 * @author Denis de Bernardy
 */
class HealthCheck_ApacheFunctions extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		if ( !preg_match("/^Apache/i", $_SERVER['SERVER_SOFTWARE']) )
			return;
		$message = sprintf(__( 'Your Webserver does not have <a href="%s">Apache functions</a>. At worst, this can prevent WordPress from detecting Apache\'s mod_rewrite module, thus disallowing the use of fancy urls. At best, this makes detecting the mod_rewrite module slower. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ref.apache.php');
		$this->assertTrue(	function_exists('apache_get_modules'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_ApacheFunctions');


/**
 * Check for mod_rewrite
 * 
 * @link http://php.net/manual/en/ref.apache.php
 * @author Denis de Bernardy
 */
class HealthCheck_ModRewrite extends HealthCheckTest {
	function run_test() {
		// Skip if IIS
		if ( !preg_match("/^Apache/i", $_SERVER['SERVER_SOFTWARE']) )
			return;
		$message = sprintf(__( 'WordPress failed to detect mod_rewrite on your Webserver, thus disallowing the use of fancy urls. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/ref.apache.php');
		$this->assertTrue(	apache_mod_loaded('mod_rewrite'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_ModRewrite');


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
 */
class HealthCheck_PHP_DefaultCharset extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Default character set configured in php.ini %s contains illegal characters. Please contact your host to have them fix this.', 'health-check' ), $configured);
		$configured = ini_get('default_charset');
		$filtered = preg_replace('|[^a-z0-9_.\-:]|i', '', $configured);
		$this->assertEquals($configured, $filtered,
							$message,
							HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_PHP_DefaultCharset');


/**
 * Check libxml2 versions for known issue with XML-RPC
 * 
 * Based on code in Joseph Scott's libxml2-fix plugin
 * which you should install if this test fails for you
 * as a stop gap solution whilest you get your server upgraded
 * 
 * @link http://josephscott.org/code/wordpress/plugin-libxml2-fix/
 * @link http://core.trac.wordpress.org/ticket/7771
 * 
 * @author peterwestwood
 */
class HealthCheck_PHP_libxml2_XMLRPC extends HealthCheckTest {
	function run_test() {
		$message = sprintf(	__('Your webserver is running PHP version %1$s with libxml2 version %2$s which will cause problems with the XML-RPC remote posting functionality. You can read more <a href="%3$s">here</a>. Please contact your host to have them fix this.', 'health-check'),
							PHP_VERSION,
							LIBXML_DOTTED_VERSION,
							'http://josephscott.org/code/wordpress/plugin-libxml2-fix/');
		$this->assertNotEquals( '2.6.27', LIBXML_DOTTED_VERSION, $message, HEALTH_CHECK_ERROR );
		$this->assertNotEquals( '2.7.0', LIBXML_DOTTED_VERSION, $message, HEALTH_CHECK_ERROR );
		$this->assertNotEquals( '2.7.1', LIBXML_DOTTED_VERSION, $message, HEALTH_CHECK_ERROR );
		$this->assertNotEquals( '2.7.2', LIBXML_DOTTED_VERSION, $message, HEALTH_CHECK_ERROR );
		$this->assertFalse( ( LIBXML_DOTTED_VERSION == '2.7.3' && version_compare( PHP_VERSION, '5.2.9', '<' ) ), $message, HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_PHP_libxml2_XMLRPC');
?>