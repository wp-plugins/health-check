<?php
/**
 * Base class for all the different Tests to extend
 *
 * @package HealthCheck
 * @subpackage Tests
 */
/**
 * Base class for all the different Tests to extend
 * 
 * Provides utility functionality where necessary
 * 
 * @author peterwestwood
 *
 */
class HealthCheckTest {
	var $result = null;
	
	function HealthCheckTest() {
		$this->result = new HealthCheckTestResult();	
	}
	
	function run_test() {
		$this->result->markAsFailed(__('ERROR: Test class does not implement run_test()','health_check'));
	}
}
?>