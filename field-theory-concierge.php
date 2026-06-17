<?php
/**
 * Plugin Name: Field Theory Concierge
 * Plugin URI: https://fieldtheory.ai
 * Description: A polished conversational concierge experience for Field Theory Lab with portfolio, services, contact, prompt routing, and editable responses.
 * Version: 2.8.0
 * Author: Jamie Rushad Gros
 * Author URI: https://fieldtheory.ai
 * Text Domain: field-theory-concierge
 */

if (!defined('ABSPATH')) exit;

define('FTC_VERSION', '2.8.0');
define('FTC_PATH', plugin_dir_path(__FILE__));
define('FTC_URL', plugin_dir_url(__FILE__));

require_once FTC_PATH . 'includes/defaults.php';
require_once FTC_PATH . 'includes/responses-cpt.php';
require_once FTC_PATH . 'includes/admin.php';
require_once FTC_PATH . 'includes/ajax.php';
require_once FTC_PATH . 'includes/portfolio-cpt.php';

function ftc_activate(){
    ftc_register_portfolio_cpt();
    if (function_exists('ftc_register_response_cpt')) ftc_register_response_cpt();
    if (function_exists('ftc_enable_elementor_for_responses')) ftc_enable_elementor_for_responses();
    ftc_register_faq_cpt();
    ftc_seed_default_faqs();
    ftc_seed_default_services();
    ftc_seed_default_portfolio();
    flush_rewrite_rules();
    if (!get_option('ftc_settings')) update_option('ftc_settings', ftc_default_settings());
    if (!get_option('ftc_responses')) update_option('ftc_responses', ftc_default_responses());
}
register_activation_hook(__FILE__, 'ftc_activate');

function ftc_deactivate(){ flush_rewrite_rules(); }
register_deactivation_hook(__FILE__, 'ftc_deactivate');



function ftc_mobile_theme_color(){
    echo '<meta name="theme-color" content="#101010">' . "\n";
    echo '<meta name="msapplication-navbutton-color" content="#101010">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
}
add_action('wp_head', 'ftc_mobile_theme_color', 1);

function ftc_enqueue_assets(){
    wp_enqueue_style('ftc-app', FTC_URL . 'assets/css/app.css', [], FTC_VERSION);
    wp_enqueue_script('ftc-app', FTC_URL . 'assets/js/app.js', [], FTC_VERSION, true);
    wp_localize_script('ftc-app', 'ftcData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ftc_nonce'),
        'settings' => ftc_get_settings(),
        'responses' => ftc_get_responses(),
        'portfolio' => ftc_get_demo_portfolio(),
    ]);
}
add_action('wp_enqueue_scripts', 'ftc_enqueue_assets');

function ftc_shortcode($atts = []){
    ob_start();
    include FTC_PATH . 'templates/concierge-app.php';
    return ob_get_clean();
}
add_shortcode('ft_concierge', 'ftc_shortcode');
add_shortcode('field_theory_concierge', 'ftc_shortcode');
