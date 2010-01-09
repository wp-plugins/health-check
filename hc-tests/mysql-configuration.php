<?php
/**
 * Tests to check for MySQL config issues.
 *
 * @package HealthCheck
 * @subpackage Tests
 */

/**
 * Check that we are running at least MySQL 5, and ideally the latest and greatest branch
 * 
 * @link http://sql-info.de/mysql/gotchas.html
 * @author Denis de Bernardy
 */
class HealthCheck_MySQL_Version extends HealthCheckTest {
	function run_test() {
		global $wpdb;
		$version = $wpdb->db_version();
		
		$message = sprintf( __( 'Your Webserver is running MySQL version %1$s, but its latest stable branch is %2$s. WordPress highly recommends MySQL 5 or higher, because <a href="%3$s">MySQL 4 is full of gotchas</a>. Please contact your host and have them upgrade MySQL accordingly.', 'health-check' ), $version, HEALTH_CHECK_MYSQL_VERSION, 'http://sql-info.de/mysql/gotchas.html' );
		$passed = $this->assertTrue(	version_compare('5.0.0', $version, '<'),
										$message,
										HEALTH_CHECK_RECOMMENDATION );

		if ( $passed ) { // no point in raising this twice
			$message = sprintf( __( 'Your Webserver is running MySQL version %1$s, but its latest stable branch is %2$s. Please contact your host and have them upgrade MySQL.', 'health-check' ), $version, HEALTH_CHECK_MYSQL_VERSION );
			// invert the check because version_compare('1.0', '1.0.0', '>=') returns false
			$this->assertTrue(	version_compare($version, HEALTH_CHECK_MYSQL_VERSION, '>='),
								$message,
								HEALTH_CHECK_INFO );
			
		}
	}
}
HealthCheck::register_test('HealthCheck_MySQL_Version');


/**
 * Check that we can use mysql_real_escape_string()
 * 
 * @link http://core.trac.wordpress.org/ticket/11819
 * @link http://php.net/manual/en/function.mysql-set-charset.php
 * @link http://php.net/manual/en/function.mysql-real-escape-string.php
 * @author Denis de Bernardy
 */
class HealthCheck_MySQL_Escape extends HealthCheckTest {
	function run_test() {
		$message = sprintf( __( 'Your Webserver does not support the <a href="%1$s">mysql_set_charset()</a> function, which we\'ve found is <a href="%2$s">needed</a> in order to safely use the <a href="%3$s">mysql_real_escape_string()</a> function. This can have security implications on your site, depending on the plugins you use. Please contact your host and have them upgrade your server accordingly.', 'health-check' ), 'http://php.net/manual/en/function.mysql-set-charset.php', 'http://core.trac.wordpress.org/ticket/11819', 'http://php.net/manual/en/function.mysql-real-escape-string.php' );
		$this->assertTrue(	function_exists('mysql_set_charset'),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MySQL_Escape');


/**
 * Check that the MySQL database has the same charset as WordPress
 *
 * Ideally, the database should entirely run in UTF-8. Whether it does can be
 * tested by running the following MySQL statement:
 *
 * SHOW VARIABLES LIKE 'char%';
 *
 * The my.cnf file needs to contain the following for all of it to use utf8:
 *
 * [client]
 * default-character-set=utf8
 *
 * [mysqld]
 * character-set-server=utf8
 *
 * We've no means to check how this is set using mysql_client_encoding(), however.
 * The client library bundled with PHP does not read the my.cnf option file:
 *
 * http://bugs.mysql.com/bug.php?id=11362
 *	
 * Nonetheless, we can check the database's charset. It should be using the same as
 * the one defined in the wp-config.php file, in the event a plugin creates a table.
 *
 * @link http://dev.mysql.com/doc/refman/5.0/en/charset-collation-effect.html
 * @link http://dev.mysql.com/doc/refman/5.0/en/charset-configuration.html
 * @link http://bugs.mysql.com/bug.php?id=11362
 * @author Denis de Bernardy
 */
class HealthCheck_MySQL_Charset extends HealthCheckTest {
	function run_test() {
		// Skip test if we've no DB_CHARSET
		if ( !defined('DB_CHARSET') || !DB_CHARSET )
			return;
		
		global $wpdb;
		
		$db_charset = $wpdb->get_var('SHOW CREATE DATABASE ' . DB_NAME, 1);
		preg_match("/CHARACTER SET ([a-z0-9_]+)/i", $db_charset, $db_charset);
		$db_charset = end($db_charset);
		
		$table_charset = $wpdb->get_var('SHOW CREATE TABLE ' . $wpdb->posts, 1);
		preg_match("/CHARSET\s*=\s*([a-z0-9_]+)/i", $table_charset, $table_charset);
		$table_charset = end($table_charset);
		
		$message = sprintf( __( 'Your WordPress installation is using the %1$s character set, but its database is using the %2$s character set. WordPress handles this quite fine, but not all plugins do when they create or alter database tables. Using the wrong character set or collation can lead to <a href="%3$s">weird side effects</a>. To change this, run (or have your host run) the following SQL in PhpMyAdmin: <code>ALTER DATABASE `%4$s` DEFAULT CHARACTER SET %2$s</code>.', 'health-check' ), $db_charset, DB_CHARSET, 'http://dev.mysql.com/doc/refman/5.0/en/charset-collation-effect.html', DB_NAME);
		$this->assertEquals(strtolower($db_charset), strtolower(DB_CHARSET),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
		
		$message = sprintf( __( 'Your WordPress installation is using the %1$s character set, but your database tables are using the %2$s character set. (Usually, this will be `latin1\' with `latin1_swedish_ci\' collation, i.e. MySQL\'s weird factory setting.) Using the wrong character set or collation can lead to <a href="%3$s">weird side effects</a>. Following the <a href="%4$s">instructions to fix this</a> can be a bit challenging for non-technically oriented users, however, so keep in mind that this is a mere recommendation.', 'health-check' ), $table_charset, DB_CHARSET, 'http://dev.mysql.com/doc/refman/5.0/en/charset-collation-effect.html', 'http://codex.wordpress.org/Converting_Database_Character_Sets');
		$this->assertEquals(strtolower($table_charset), strtolower(DB_CHARSET),
							$message,
							HEALTH_CHECK_RECOMMENDATION );
	}
}
HealthCheck::register_test('HealthCheck_MySQL_Charset');
?>