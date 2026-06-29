<?php
if(PHP_SAPI !== 'cli'){
    http_response_code(403);
    exit('Forbidden');
}
$wp_root = getenv('FTC_WP_ABSPATH') ?: 'C:/Users/jamie/Local Sites/ftl-2026/app/public/';
if(!defined('ABSPATH')) define('ABSPATH', rtrim($wp_root, '/\\') . '/');
if(!is_readable(ABSPATH . 'wp-load.php')){
    exit("wp-load.php not found. Set FTC_WP_ABSPATH.\n");
}
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
require_once ABSPATH . 'wp-load.php';

$post = function_exists('ftc_get_sky_ute_portfolio_post') ? ftc_get_sky_ute_portfolio_post() : null;
echo 'Post: '.($post ? $post->ID.' '.$post->post_title : 'NOT FOUND').PHP_EOL;
echo 'Option before: '.get_option('ftc_sky_ute_casino_images_2854').PHP_EOL;
if($post){
    echo 'Gallery ids before: '.get_post_meta($post->ID, '_ftc_gallery_ids', true).PHP_EOL;
    echo 'Gallery urls before: '.str_replace("\n", ' | ', (string)get_post_meta($post->ID, '_ftc_gallery_urls', true)).PHP_EOL;
    echo 'Placeholder flag before: '.get_post_meta($post->ID, '_ftc_allow_placeholder_image', true).PHP_EOL;
}

if(function_exists('ftc_repair_sky_ute_gallery')){
    ftc_repair_sky_ute_gallery(true);
}
if(function_exists('ftc_migrate_2854_sky_ute_casino_images')){
    ftc_migrate_2854_sky_ute_casino_images(true);
}

if($post){
    echo 'Gallery ids after: '.get_post_meta($post->ID, '_ftc_gallery_ids', true).PHP_EOL;
    echo 'Gallery urls after: '.str_replace("\n", ' | ', (string)get_post_meta($post->ID, '_ftc_gallery_urls', true)).PHP_EOL;
    echo 'Thumbnail after: '.get_post_thumbnail_id($post->ID).PHP_EOL;
    if(function_exists('ftc_portfolio_card_image_url')){
        echo 'Card image: '.ftc_portfolio_card_image_url($post->ID).PHP_EOL;
    }
}
echo 'Option after: '.get_option('ftc_sky_ute_casino_images_2854').PHP_EOL;
