<?php
/*
 * Health Check 
 */
// http://blog.ftwr.co.uk/archives/2009/09/29/missing-dashboard-css-and-the-perils-of-smart-quotes/
add_action('health_check_run_tests','health_check_php_default_charset');
function health_check_php_default_charset() {
	//TODO validate the default_charset option
}
?>