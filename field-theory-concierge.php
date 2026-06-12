<?php
/**
 * Plugin Name: Field Theory Concierge
 * Plugin URI: https://fieldtheory.ai
 * Description: Conversational concierge experience for Field Theory Lab.
 * Version: 2.0.0
 * Author: Jamie Rushad Gros
 * Author URI: https://fieldtheory.ai
 * Text Domain: field-theory-concierge
 */

if (!defined('ABSPATH')) {
    exit;
}

define(
    'FTC_VERSION',
    '2.0.0'
);

define(
    'FTC_PLUGIN_PATH',
    plugin_dir_path(__FILE__)
);

define(
    'FTC_PLUGIN_URL',
    plugin_dir_url(__FILE__)
);

/**
 * Includes
 */

require_once FTC_PLUGIN_PATH . 'includes/admin.php';
require_once FTC_PLUGIN_PATH . 'includes/settings.php';
require_once FTC_PLUGIN_PATH . 'includes/ajax.php';
require_once FTC_PLUGIN_PATH . 'includes/portfolio-cpt.php';
require_once FTC_PLUGIN_PATH . 'includes/services-cpt.php';
require_once FTC_PLUGIN_PATH . 'includes/prompts.php';

/**
 * Assets
 */

function ftc_enqueue_assets() {

    wp_enqueue_style(
        'ftc-app',
        FTC_PLUGIN_URL . 'assets/css/app.css',
        [],
        FTC_VERSION
    );

    wp_enqueue_script(
        'ftc-app',
        FTC_PLUGIN_URL . 'assets/js/app.js',
        [],
        FTC_VERSION,
        true
    );

    wp_localize_script(
        'ftc-app',
        'ftcData',
        [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('ftc_nonce'),
        ]
    );
}

add_action(
    'wp_enqueue_scripts',
    'ftc_enqueue_assets'
);

/**
 * Shortcode
 */

function ftc_shortcode() {

    ob_start();

    include FTC_PLUGIN_PATH .
        'templates/concierge-app.php';

    return ob_get_clean();
}

add_shortcode(
    'ft_concierge',
    'ftc_shortcode'
);