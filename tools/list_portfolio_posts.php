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

$q = new WP_Query([
    'post_type' => 'ftc_portfolio',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'order' => 'ASC',
]);

echo 'Total portfolio posts: '.$q->found_posts.PHP_EOL;
while($q->have_posts()){
    $q->the_post();
    $id = get_the_ID();
    $slug = get_post_field('post_name', $id);
    $order = get_post_field('menu_order', $id);
    $featured = get_post_meta($id, '_ftc_featured', true);
    $card = function_exists('ftc_portfolio_card_image_url') ? ftc_portfolio_card_image_url($id) : '';
    $gallery_ids = get_post_meta($id, '_ftc_gallery_ids', true);
    $gallery_urls = str_replace("\n", ' | ', (string)get_post_meta($id, '_ftc_gallery_urls', true));
    echo sprintf("%d | order=%s | featured=%s | %s | card=%s | ids=%s | urls=%s\n", $id, $order, $featured, $slug, $card, $gallery_ids, $gallery_urls);
}
wp_reset_postdata();
