<?php
/**
 * Twenty Twenty-Two functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Two
 * @since Twenty Twenty-Two 1.0
 */


if (!function_exists('twentytwentytwo_support')):

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return void
	 */
	function twentytwentytwo_support()
	{

		// Add support for block styles.
		add_theme_support('wp-block-styles');

		// Enqueue editor styles.
		add_editor_style('style.css');

	}

endif;

add_action('after_setup_theme', 'twentytwentytwo_support');

if (!function_exists('twentytwentytwo_styles')):

	/**
	 * Enqueue styles.
	 *
	 * @since Twenty Twenty-Two 1.0
	 *
	 * @return void
	 */
	function twentytwentytwo_styles()
	{
		// Register theme stylesheet.
		$theme_version = wp_get_theme()->get('Version');

		$version_string = is_string($theme_version) ? $theme_version : false;
		wp_register_style(
			'twentytwentytwo-style',
			get_template_directory_uri() . '/style.css',
			array(),
			$version_string
		);

		// Enqueue theme stylesheet.
		wp_enqueue_style('twentytwentytwo-style');

	}

endif;

add_action('wp_enqueue_scripts', 'twentytwentytwo_styles');

// Add block patterns
require get_template_directory() . '/inc/block-patterns.php';
require get_template_directory() . '/custom-api.php';

add_filter('rest_jsonp_enabled', '__return_true');

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function prefix_get_endpoint_phrase()
{

	$all_options = wp_load_alloptions();
	$my_options = array();
	foreach ( $all_options as $name => $value ) {
		if (str_contains($name, 'options_production')&& substr($name, 0, 1) != '_') {
			if ($name === 'options_production' ) {
				continue;
			}
			$short_name = explode('options_production_',$name)[1];
			$field_names = stristr($short_name,'_');

			$my_options['options_production'][intval(substr($short_name, 0, 1))][substr($field_names, 1)] = $value;
		}
	}
	// rest_ensure_response() wraps the data we want to return into a WP_REST_Response, and ensures it will be properly returned.
	return rest_ensure_response($my_options);
}

/**
 * This function is where we register our routes for our example endpoint.
 */
function prefix_register_example_routes()
{
	// register_rest_route() handles more arguments but we are going to stick to the basics for now.
	register_rest_route('backoffice/v1', '/production', array(
		// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
		'methods' => WP_REST_Server::READABLE,
		// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
		'callback' => 'prefix_get_endpoint_phrase',
	));
}

add_action('rest_api_init', 'prefix_register_example_routes');