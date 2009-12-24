<?php
/**
 * Dummy test to check that everything is working.
 *
 * @package HealthCheck
 * @subpackage Tests
 */
/**
 * Dummy test to check that everything is working.
 * @author peterwestwood
 *
 */
class HealthCheck_DummyTest extends HealthCheckTest {
	function run_test() {
		$this->result->markAsFailed(__('Dummy Test Ran ok.','health_check'), HEALTH_CHECK_OK);
	}
}

HealthCheck::register_test('HealthCheck_DummyTest');
?>