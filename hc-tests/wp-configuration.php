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
 * @author Denis de Bernardy
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
 * @author Denis de Bernardy
 */
class HealthCheck_Verbose_Rules extends HealthCheckTest {
	function run_test() {
		// Skip if permalinks aren't enabled
		global $wp_rewrite;
		if ( !$wp_rewrite->permalink_structure )
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
 * @author Denis de Bernardy
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

		if ( !$passed ) {
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
?>