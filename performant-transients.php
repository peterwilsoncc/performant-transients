<?php
/**
 * Performant Transients
 *
 * @package           PerformantTransients
 * @author            Peter Wilsom
 * @copyright         2024 Peter Wilsom
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Performant Transients
 * Description:       Reduce the number of database calls querying transients.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.2
 * Author:            Peter Wilsom
 * Author URI:        https://peterwilson.cc/
 * Text Domain:       performant-transients
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace PWCC\PerformantTransients;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Bootstrap the plugin.
 */
function bootstrap() {
	if (
		! function_exists( 'wp_prime_option_caches' )
		|| ! function_exists( 'str_starts_with' )
		|| is_wp_version_compatible( '6.6' )
	) {
		/*
		 * Do nothing if required functions are not available or the
		 * WordPress version includes the functionality natively.
		 */
		return;
	}

	// Runs late to ensure the option value hasn't been set by another plugin.
	add_filter( 'pre_option', __NAMESPACE__ . '\\maybe_prime_transient_cache', 100, 2 );
}
bootstrap();

/**
 * Prime the options cache for transients.
 *
 * If the transient being queried is an expiring transient, prime the
 * cache for both the transient and its timeout options.
 *
 * Runs on the `pre_option` filter.
 *
 * Although this runs on the `pre_option` filter, it does not actually
 * modify the value of the option. This is a hack to work around the
 * lack of an action that fires prior to getting an option.
 *
 * @param mixed  $pre         The pre-flight get_option value.
 * @param string $option_name The option name passed to get_option().
 *
 * @return mixed The unmodified $pre value.
 */
function maybe_prime_transient_cache( $pre, $option_name ) {
	if (
		! str_starts_with( $option_name, '_transient_' )
		|| false !== $pre
	) {
		return $pre;
	}

	if ( str_starts_with( $option_name, '_transient_timeout_' ) ) {
		$transient_name = substr( $option_name, 19 );
	} else {
		$transient_name = substr( $option_name, 11 );
	}

	$alloptions = wp_load_alloptions();
	if ( isset( $alloptions[ '_transient_' . $transient_name ] ) ) {
		/*
		 * If the transinet is in all options it does not have a timeout
		 * and does not need to be primed.
		 */
		return $pre;
	}

	$option_names = array(
		'_transient_' . $transient_name,
		'_transient_timeout_' . $transient_name,
	);

	wp_prime_option_caches( $option_names );

	return $pre;
}
