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
		$this->assertNotEquals(	$wp_rewrite->permalink_structure,
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
?>