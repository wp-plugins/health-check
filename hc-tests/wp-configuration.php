<?php
/**
 * Tests to check for WP config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we're using a permalink structure
 * 
 * @link ftp://ftp.research.microsoft.com/pub/tr/TR-2007-01.pdf
 * @link http://www.w3.org/Provider/Style/URI
 */
class HealthCheck_Permalinks extends HealthCheckTest {
	function run_test() {
		global $wp_rewrite;
		
		$message = sprintf(__( 'You\'ve have configured WordPress to use a <a href="%1$s">fancy URL structure</a>. It\'s an important UI element, since users spend <a href="%2$s">a fourth of their gaze time</a> looking at URLs in search results. Note that your post URLs should <a href="%3$s">ideally include date information</a>; for this reason, WordPress recommends either of the default date-based structures.', 'health-check' ), 'options-permalink.php', 'ftp://ftp.research.microsoft.com/pub/tr/TR-2007-01.pdf', 'http://www.w3.org/Provider/Style/URI' );
		$this->assertNotEquals(	!$wp_rewrite->permalink_structure,
								'',
								$message,
								HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_Permalinks');


/**
 * Check that we're not using verbose rewrite rules
 * 
 * @todo find trac tickets that highlight verbose rule problems
 */
class HealthCheck_Verbose_Rules extends HealthCheckTest {
	function run_test() {
		// Skip if permalinks aren't enabled
		global $wp_rewrite;
		if ( !$wp_rewrite->permalink_structure && !HEALTH_CHECK_DEBUG )
			return;
		
		$message = sprintf(__( 'You\'ve configured WordPress to use a fancy URL structure (<code>%1$s</code>) that requires the use of verbose rewrite rules. On sites with multitudes of attachments or static pages, WordPress ends up pulling a large serialized array from the database on every page load, which is resource intensive. To avoid the problem, use a permalink structure whose left-most rewrite tag is numerical, i.e. <code>%%post_id%%</code>, <code>%%year%%</code>, <code>%%monthnum%%</code> or <code>%%day%%</code>. WordPress recommends either of the default date-based structures.', 'health-check' ), $wp_rewrite->permalink_structure );
		$this->assertFalse(	$wp_rewrite->use_verbose_page_rules,
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_Verbose_Rules');


/**
 * Check that memcache can fit alloptions
 * 
 * @link http://code.google.com/p/memcached/wiki/FAQ
 * @link http://www.php.net/manual/en/function.memcache-setcompressthreshold.php
 */
class HealthCheck_Oversized_Options extends HealthCheckTest {
	function run_test() {
		global $_wp_using_ext_object_cache, $wpdb;
		$options_size = strlen(serialize(wp_load_alloptions()));
		// .8 because we're assuming the default (roughly 20%) compression savings for large
		$options_size = round($options_size * .8 / 1024);
		
		$message = sprintf(__( 'Your Webserver\'s memcache-based persistent cache can store items <a href="%1$s">no larger than 1MB</a>, but your WordPress %2$s table contains roughly %3$skB of data assuming 20%% compression savings. You might want to investigate which options are taking such a large amount of space.', 'health-check' ), 'http://code.google.com/p/memcached/wiki/FAQ', $wpdb->options, $options_size );
		$passed = $this->assertTrue(	!$_wp_using_ext_object_cache
										|| !method_exists('Memcache', 'addServer')
										|| ( $options_size <= 820 ), // 800kB
										$message,
										HEALTH_CHECK_INFO );

		if ( !$passed || HEALTH_CHECK_DEBUG ) {
			// highlight options that are larger than ~50kB (e.g. rewrite_rules and yarpp's cache)
			$large_options = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE LENGTH(option_value) >= 51200 ORDER BY LENGTH(option_value) DESC");
			$large_options = implode(__('</code>, <code>', 'health-check'), $large_options);

			$message = sprintf(__( 'Your largest options for reference: <code>%s</code>', 'health-check' ), $large_options );
			$passed = $this->assertEquals(	$large_options,
											'',
											$message,
											HEALTH_CHECK_INFO );
		}
	}
}
HealthCheck::register_test('HealthCheck_Oversized_Options');


/**
 * Check that the RSS feed doesn't contain leading white space
 */
class HealthCheck_RSS_Feed extends HealthCheckTest {
	function run_test() {
		if ( !wp_cache_get('http_api', 'health_check') && !HEALTH_CHECK_DEBUG )
			return;
		
		$url = get_feed_link('rss2');
		$res = wp_remote_fopen($url);

		$message = __( 'WordPress has detected that your installation\'s RSS feed contains leading white space characters. Typically, these are present because your <code>wp-config.php</code> file or your theme\'s <code>functions.php</code> file contains leading or trailing white space. More rarely, it is due to plugin files. This white space can prevent your RSS feed from working, and should be removed. Edit these files one by one, and trim any white space before the <code>&lt;?php</code> at the very beginning of the file, and after the <code>?&gt;</code> at the very end of the file (if the latter is present).', 'health-check' );
		$this->assertNotEquals(	trim(substr($res, 1)),
								'',
								$message,
								HEALTH_CHECK_ERROR );
	}
}
HealthCheck::register_test('HealthCheck_RSS_Feed');


/**
 * Check for inactive widgets (this can slow down the widgets screen tremendously)
 * 
 * @link http://core.trac.wordpress.org/ticket/10021
 */
class HealthCheck_InactiveWidgets extends HealthCheckTest {
	function run_test() {
		$sidebars_widgets = wp_get_sidebars_widgets();
		$message = sprintf(__( 'Quite a few widgets are inactive on your site. This can <a href="%1$s">slow down</a> the widgets screen. Consider deleting a few under <a href="%2$s">Appearance / Widgets</a>.', 'health-check' ), 'http://core.trac.wordpress.org/ticket/10021', 'widgets.php' );
		$this->assertTrue(	empty($sidebars_widgets['wp_inactive_widgets'])
							// allow for a few saved ad/text widgets
							|| ( count($sidebars_widgets['wp_inactive_widgets']) <= 3 ),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_InactiveWidgets');


/**
 * Check for drop in files
 * 
 * @link http://core.trac.wordpress.org/ticket/11861
 */
class HealthCheck_DropInFiles extends HealthCheckTest {
	function run_test() {
		$files = array();
		foreach ( array(
			'db.php',
			'advanced-cache.php',
			'object-cache.php',
			) as $file ) {
			if ( file_exists(WP_CONTENT_DIR . '/' . $file) )
				$files[] = $file;
		}
		$files = implode(__('</code>, <code>', 'health-check'), $files);
		$message = sprintf(__( 'Your WordPress installation has drop-in files in its wp-content folder: <code>%1$s</code>. In the event that you added them manually, be sure to keep them up to date. Forgetting to do so can create issues that are <a href="%2$s">very hard to diagnose</a>.', 'health-check' ), $files, 'http://core.trac.wordpress.org/ticket/11861' );
		$this->assertEquals($files,
							'',
							$message,
							HEALTH_CHECK_INFO );
		
		$files = array();
		if ( defined('WPMU_PLUGIN_DIR') && is_dir(WPMU_PLUGIN_DIR) ) {
			foreach ( glob(WPMU_PLUGIN_DIR . '/*.php') as $file )
				$files[] = basename($file);
		}
		$files = implode(__('</code>, <code>', 'health-check'), $files);
		$message = sprintf(__( 'Your WordPress installation has drop-in files in its wp-content/mu-plugins folder: <code>%1$s</code>. In the event that you added them manually, be sure to keep them up to date. Forgetting to do so can create issues that are <a href="%2$s">very hard to diagnose</a>.', 'health-check' ), $files, 'http://core.trac.wordpress.org/ticket/11861' );
		$this->assertEquals($files,
							'',
							$message,
							HEALTH_CHECK_INFO );
	}
}
HealthCheck::register_test('HealthCheck_DropInFiles');
?>