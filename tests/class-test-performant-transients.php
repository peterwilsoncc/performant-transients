<?php
/**
 * Test the filter
 *
 * @package PWCC\PersistentTransients\Tests
 */

namespace PWCC\PersistentTransients\Tests;

use WP_UnitTestCase;

/**
 * Test the rewrite rules.
 */
class Test_Performant_Transients extends WP_UnitTestCase {
	/**
	 * Ensure get_transient() makes a single database request.
	 *
	 * @covers ::maybe_prime_transient_cache
	 */
	public function test_get_transient_with_timeout_makes_a_single_database_call() {
		global $wpdb;
		$key                        = 'test_transient';
		$value                      = 'test_value';
		$timeout                    = 100;
		$expected_query             = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ('_transient_{$key}','_transient_timeout_{$key}')";
		$unexpected_query_transient = "SELECT option_value FROM $wpdb->options WHERE option_name = '_transient_{$key}' LIMIT 1";
		$unexpected_query_timeout   = "SELECT option_value FROM $wpdb->options WHERE option_name = '_transient_timeout_{$key}' LIMIT 1";
		$queries                    = array();

		set_transient( $key, $value, $timeout );

		// Clear the cache of both the transient and the timeout.
		$option_names = array(
			'_transient_' . $key,
			'_transient_timeout_' . $key,
		);
		foreach ( $option_names as $option_name ) {
			wp_cache_delete( $option_name, 'options' );
		}

		add_filter(
			'query',
			function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			}
		);

		$before_queries = get_num_queries();
		$this->assertSame( $value, get_transient( $key ) );
		$transient_queries = get_num_queries() - $before_queries;
		$this->assertSame( 1, $transient_queries, 'Expected a single database query to retrieve the transient.' );
		$this->assertContains( $expected_query, $queries, 'Expected query to prime both transient options in a single call.' );
		// Note: Some versions of PHPUnit and/or the test suite may report failures as asserting to contain rather than not to contain.
		$this->assertNotContains( $unexpected_query_transient, $queries, 'Unexpected query of transient option individually.' );
		$this->assertNotContains( $unexpected_query_timeout, $queries, 'Unexpected query of transient timeout option individually.' );
	}

	/**
	 * Ensure set_transient() primes the option cache checking for an existing transient.
	 *
	 * @covers ::maybe_prime_transient_cache
	 */
	public function test_set_transient_primes_option_cache() {
		global $wpdb;
		$key                        = 'test_transient';
		$value                      = 'test_value';
		$timeout                    = 100;
		$expected_query             = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ('_transient_{$key}','_transient_timeout_{$key}')";
		$unexpected_query_transient = "SELECT option_value FROM $wpdb->options WHERE option_name = '_transient_{$key}' LIMIT 1";
		$unexpected_query_timeout   = "SELECT option_value FROM $wpdb->options WHERE option_name = '_transient_timeout_{$key}' LIMIT 1";
		$queries                    = array();

		add_filter(
			'query',
			function ( $query ) use ( &$queries ) {
				$queries[] = $query;
				return $query;
			}
		);

		/*
		 * Prime the alloptions cache.
		 *
		 * This is required because the test suite flushes the cache prior to each test.
		 */
		wp_load_alloptions();

		$before_queries = get_num_queries();
		$this->assertTrue( set_transient( $key, $value, $timeout ) );
		$transient_queries = get_num_queries() - $before_queries;
		$this->assertSame( 3, $transient_queries, 'Expected three database queries setting the transient.' );
		$this->assertContains( $expected_query, $queries, 'Expected query to prime both transient options in a single call.' );
		// Note: Some versions of PHPUnit and/or the test suite may report failures as asserting to contain rather than not to contain.
		$this->assertNotContains( $unexpected_query_transient, $queries, 'Unexpected query of transient option individually.' );
		$this->assertNotContains( $unexpected_query_timeout, $queries, 'Unexpected query of transient timeout option individually.' );
	}
}
