<?php
/**
 * Tests to check for php config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running at least PHP 5, and ideally the latest and greatest PHP branch
 * 
 * @todo Provide a link to a codex article
 * @link http://core.trac.wordpress.org/ticket/9751
 * @link http://www.php.net/archive/2007.php#2007-07-13-1
 * @author Peter Westwood, Denis de Bernardy
 */
class HealthCheck_PHP_Version extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver is running PHP version %1$s, but its latest stable branch is %2$s. WordPress will no longer support PHP 4 in a future version because it is <a href="%3$s">no longer receiving security updates</a>. Please contact your host and have them upgrade PHP as soon as possible.', 'health-check' ), PHP_VERSION, HEALTH_CHECK_PHP_VERSION, 'http://www.php.net/archive/2007.php#2007-07-13-1' );
		$passed = $this->assertTrue(	version_compare('5.0.0', PHP_VERSION, '<'),
										$message,
										HEALTH_CHECK_RECOMMENDATION );

		if ( $passed || HEALTH_CHECK_DEBUG ) { // no point in raising this twice
			$message = sprintf( __( 'Your Webserver is running PHP version %1$s, but its latest stable branch is %2$s. Please contact your host and have them upgrade PHP.', 'health-check' ), PHP_VERSION, HEALTH_CHECK_PHP_VERSION );
			// invert the check because version_compare('1.0', '1.0.0', '>=') returns false
			$this->assertTrue(	version_compare(PHP_VERSION, HEALTH_CHECK_PHP_VERSION, '>='),
								$message,
								HEALTH_CHECK_INFO );
		}
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
		// Let the test pass if we're already at 256M
		@ini_set('memory_limit', '256M');
		$message = __( 'Your Webserver disallows PHP to increase the memory limit at run time. This can occasionally prevent WordPress from working. In particular during core upgrades, where WordPress tries to increase it to 256M in order to unzip core files. Depending on how your server is configured, running into this memory limit would reveal some kind of "Failed to allocate memory" error, an incomplete screen, or a completely blank screen. Please contact your host to have them fix this.', 'health-check' );
		$this->assertEquals(256, 
							intval( ini_get('memory_limit') ),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		@ini_set('memory_limit', $original_limit); // restore original limit
	}
}
HealthCheck::register_test('HealthCheck_MemoryLimitOverride');


/**
 * Check that user aborts can be ignored
 * 
 * @link http://php.net/manual/en/function.ignore-user-abort.php
 * @author Denis de Bernardy
 */
class HealthCheck_UserAbort extends HealthCheckTest {
	function run_test() {
		$old = ignore_user_abort();
		@ignore_user_abort(!$old);
		$message = sprintf(__( 'Your Webserver disallows to override <a href="%s">user abort</a> settings. This can cause multitudes of quirks in the WordPress cron API, it can prevent future posting and pinging from working, and it can make core upgrades fail miserably. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/function.ignore-user-abort.php');
		$this->assertNotEquals(	$old,
								ignore_user_abort(),
								$message,
								HEALTH_CHECK_RECOMMENDATION );
		@ignore_user_abort($old);
	}
}
HealthCheck::register_test('HealthCheck_UserAbort');


/**
 * Check that the max execution time can be overridden
 * 
 * @link http://php.net/manual/en/function.set-time-limit.php
 * @author Denis de Bernardy
 */
class HealthCheck_MaxExecutionTime extends HealthCheckTest {
	function run_test() {
		$old = ini_get('max_execution_time');
		$new = $old + 60;
		@set_time_limit($new);
		$message = sprintf(__( 'Your Webserver disallows to override the <a href="%s">maximum script execution time</a>. This can cause multitudes of quirks in the WordPress cron API, it can prevent future posting and pinging from working, and it can make core upgrades fail miserably. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/function.set-time-limit.php');
		$this->assertTrue(	$new <= ini_get('max_execution_time'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MaxExecutionTime');


/**
 * Check the max upload size and the post max size
 * 
 * @author Denis de Bernardy
 */
class HealthCheck_UploadSize extends HealthCheckTest {
	function run_test() {
		$upload_max_filesize = intval(ini_get('upload_max_filesize'));
		$post_max_size = intval(ini_get('post_max_size'));
		$message = sprintf(__( 'Your Webserver disallows uploads for files larger than %1$sMB. If you are using your site to host photography, podcasts or videos, consider increasing the limit (upload_max_filesize) to 8MB or higher. Please contact your host to have them fix this.', 'health-check' ), $upload_max_filesize);
		$this->assertTrue(	$upload_max_filesize >= 8,
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		$message = sprintf(__( 'Your Webserver allows uploaded files to be as large as %1$sMB, but only allows HTTP POST requests to be as large as %2$sMB. The latter figure (post_max_size) should be greater than the former (upload_max_filesize). Please contact your host to have them fix this.', 'health-check' ), $upload_max_filesize, $post_max_size);
		$this->assertTrue(	$upload_max_filesize <= $post_max_size,
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_UploadSize');


/**
 * Check for multibyte string sanitization functionality
 * 
 * @link http://php.net/manual/en/intro.mbstring.php
 * @link http://php.net/manual/en/intro.iconv.php
 * @link http://php.net/manual/en/reference.pcre.pattern.modifiers.php
 * @author Denis de Bernardy
 */
class HealthCheck_MB_String extends HealthCheckTest {
	function run_test() {
		$message = sprintf(__( 'Your Webserver does not support <a href="%1$s">multibyte string functions</a>. This can result in improperly sanitized strings when WordPress handles trackbacks, pingbacks, and RSS feeds that use multibyte characters. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/intro.mbstring.php');
		$this->assertTrue(	function_exists('mb_detect_encoding'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		$message = sprintf(__( 'Your Webserver does not support <a href="%s">iconv</a> functions. This can result in improperly sanitized strings when WordPress handles trackbacks, pingbacks, and RSS feeds that use multibyte characters. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/intro.iconv.php');
		$this->assertTrue(	function_exists('iconv'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		$message = sprintf(__( 'Your Webserver does not support <a href="%s">UTF-8 regular expressions</a> (the /u modifier is not working). This can result in improperly sanitized strings when WordPress handles trackbacks, pingbacks, and RSS feeds that use multibyte characters. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/reference.pcre.pattern.modifiers.php');
		$this->assertTrue(	@preg_match("/^\pL/u", 'a'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MB_String');


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
 * @author Peter Westwood
 */
class HealthCheck_PHP_DefaultCharset extends HealthCheckTest {
	function run_test() {
		$configured = ini_get('default_charset');
		$filtered = preg_replace('|[^a-z0-9_.\-:]|i', '', $configured);
		$message = sprintf( __( 'Default character set configured in php.ini %s contains illegal characters. Please contact your host to have them fix this.', 'health-check' ), $configured);
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
 * @author Peter Westwood
 */
class HealthCheck_PHP_libxml2_XMLRPC extends HealthCheckTest {
	function run_test() {
		if ( !function_exists('jms_libxml2_fix') ) {
			$message = sprintf(	__('Your webserver is running PHP version %1$s with libxml2 version %2$s which will cause problems with the XML-RPC remote posting functionality. You can read more <a href="%3$s">here</a>. Please contact your host to have them fix this.', 'health-check'),
								PHP_VERSION,
								LIBXML_DOTTED_VERSION,
								'http://josephscott.org/code/wordpress/plugin-libxml2-fix/');
			$importance = HEALTH_CHECK_ERROR;
		} else {
			$message = sprintf(	__('Your webserver is running PHP version %1$s with libxml2 version %2$s which will cause problems with the XML-RPC remote posting functionality. A <a href="%3$s">workaround</a> is active on your site already. But you should contact your host to have them fix this nonetheless.', 'health-check'),
								PHP_VERSION,
								LIBXML_DOTTED_VERSION,
								'http://josephscott.org/code/wordpress/plugin-libxml2-fix/');
			$importance = HEALTH_CHECK_INFO;
			
		}
		
		$this->assertNotEquals( '2.6.27', LIBXML_DOTTED_VERSION, $message, $importance );
		$this->assertNotEquals( '2.7.0', LIBXML_DOTTED_VERSION, $message, $importance );
		$this->assertNotEquals( '2.7.1', LIBXML_DOTTED_VERSION, $message, $importance );
		$this->assertNotEquals( '2.7.2', LIBXML_DOTTED_VERSION, $message, $importance );
		$this->assertFalse( ( LIBXML_DOTTED_VERSION == '2.7.3' && version_compare( PHP_VERSION, '5.2.9', '<' ) ), $message, $importance );
	}
}
HealthCheck::register_test('HealthCheck_PHP_libxml2_XMLRPC');


/**
 * Check that we've the GD library
 * 
 * @link http://php.net/manual/en/book.image.php
 * @author Denis de Bernardy
 */
class HealthCheck_GD extends HealthCheckTest {
	function run_test() {
		$message = sprintf(__( 'Your Webserver does not have the <a href="%s">GD library</a>. WordPress uses it for image manipulations, such as thumbnail generation and image rotations. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/book.image.php');
		$this->assertTrue(	extension_loaded('gd'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_GD');


/**
 * Check that we've the JSON library
 * 
 * @link http://php.net/manual/en/book.image.php
 * @author Denis de Bernardy
 */
class HealthCheck_JSON extends HealthCheckTest {
	function run_test() {
		$message = sprintf(__( 'Your Webserver does not have the <a href="%s">JSON library</a>. WordPress uses it for AJAX. Compatibility code is included, but PHP\'s native library is faster. Please contact your host to have them fix this.', 'health-check' ), 'http://php.net/manual/en/book.json.php');
		$this->assertTrue(	extension_loaded('json'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_JSON');


/**
 * Check if we've the Suhosin patch
 * 
 * @link http://www.hardened-php.net/suhosin/
 * @author Denis de Bernardy
 */
class HealthCheck_Suhosin extends HealthCheckTest {
	function run_test() {
		$check = true;
		// Maybe suhosin is just sticking to logging, so let's stay permissive
		switch ( extension_loaded('suhosin') && !ini_get('suhosin.simulation') ) {
		case true:
			// these should all be sufficiently large
			foreach ( array(
				'cookie',
				'get',
				'post',
				'request',
				) as $var ) {
				foreach ( array(
					'max_array_depth' => 512,
					'max_array_index_length' => 2048,
					'max_name_length' => 512,
					'max_totalname_length' => 8192,
					'max_value_length' => 4000000,
					'max_vars' => 2048,
					'max_varname_length' => 8192,
					) as $setting => $limit ) {
					$setting = intval(ini_get("suhosin.$var.$setting"));
					if ( $setting && $setting < $limit ) {
						$check = false;
						break 3;
					}
				}
			}
			if ( ini_get('suhosin.upload.max_uploads') < 100 ) {
				$check = false;
				break;
			}
			
			// WP tries to set the memory limit to 256M in several places
			if ( intval(ini_get('memory_limit')) < 256 && intval(ini_get('suhosin.memory_limit')) < 256 ) {
				$check = false;
				break;
			}
		}
		
		$message = sprintf(__( 'Your Webserver is using the <a href="%1$s">Suhosin patch</a> with over-zealous security settings. Suhosin is an extreme source of grief for large PHP applications, and ought to be a primary suspect if you experience very weird WordPress issues. The symptoms include messages with cryptic resource limits, partially loaded pages, and partially saved data. This plugin checks for <a href="%2$s">filtering restrictions</a> and memory restrictions (256M min). Please contact your host to have them increased.', 'health-check' ), 'http://www.hardened-php.net/suhosin/', 'http://www.hardened-php.net/suhosin/configuration.html#filtering_options');
		$this->assertTrue(	$check,
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_Suhosin');
?>