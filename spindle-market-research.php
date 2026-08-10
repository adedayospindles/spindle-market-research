<?php
/**
 * Plugin Name:       Spindle Market Research Hub
 * Description:       Financial research dashboard for NGX market data, reports, stock charts, dividends, sector performance, and macroeconomic indicators.
 * Version:           1.8.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Adedayo Agboola & ASA Solutions Limited
 * Plugin URI:        https://github.com/adedayospindles/spindle-market-research
 * Author URI:        https://github.com/adedayospindles
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       spindle-market-research-hub
 */


if ( ! defined( 'WPINC' ) ) {
    die; // Halt execution if accessed directly.
}

// Define absolute pathing constants
define( 'CBAH_VERSION', '1.8.0' );
define( 'CBAH_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CBAH_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// Load Class Architecture
require_once CBAH_PLUGIN_PATH . 'includes/class-cbah-post-types.php';
require_once CBAH_PLUGIN_PATH . 'includes/class-cbah-acf-fields.php';
require_once CBAH_PLUGIN_PATH . 'includes/class-cbah-public-view.php';

/**
 * Handle structural rewrite flush procedures upon activation windows
 */
register_activation_hook( __FILE__, function() {
    $cpts = new CBAH_Post_Types();
    $cpts->register_structures();
    flush_rewrite_rules();
});

/**
 * Initialize Modular Objects
 */
function run_spindle_market_research_hub() {
    // 1. Core Post Types Engine
    $post_types = new CBAH_Post_Types();
    add_action( 'init', array( $post_types, 'register_structures' ) );

    // Programmatic ACF Pro Meta Definitions Engine
    $acf_fields = new CBAH_ACF_Fields();
    add_action( 'acf/init', array( $acf_fields, 'register_local_field_groups' ) );

    // Frontend Output Engine (Shortcodes, Assets, Script Injectors)
    $public_view = new CBAH_Public_View();

    // The dashboard renderer is responsible for enqueueing its own frontend
    // assets. This keeps the plugin modular and prevents the dashboard JS/CSS
    // from being loaded globally on unrelated pages.

    add_shortcode( 'market_research_dashboard', array( $public_view, 'render_dashboard' ) );
    add_shortcode( 'capital_bancorp_home_snapshot', array( $public_view, 'render_homepage_snapshot' ) );
    add_shortcode( 'capital_bancorp_ticker', array( $public_view, 'render_market_ticker' ) );

    // Real-Time AJAX Endpoint Listeners
    add_action( 'wp_ajax_cbah_get_live_metrics', array( $public_view, 'handle_ajax_data_request' ) );
    add_action( 'wp_ajax_nopriv_cbah_get_live_metrics', array( $public_view, 'handle_ajax_data_request' ) );
}
run_spindle_market_research_hub();