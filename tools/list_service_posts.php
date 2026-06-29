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

echo 'ftc_five_service_categories_2856: ' . var_export(get_option('ftc_five_service_categories_2856'), true) . PHP_EOL;
echo 'ftc_six_service_categories_2857: ' . var_export(get_option('ftc_six_service_categories_2857'), true) . PHP_EOL;
echo 'FTC_VERSION: ' . (defined('FTC_VERSION') ? FTC_VERSION : 'n/a') . PHP_EOL;

$q = new WP_Query([
    'post_type' => 'ftc_service',
    'post_status' => 'any',
    'posts_per_page' => -1,
    'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'order' => 'ASC',
]);

echo 'Total service posts: ' . $q->found_posts . PHP_EOL;
while ($q->have_posts()) {
    $q->the_post();
    $id = get_the_ID();
    $slug = get_post_field('post_name', $id);
    $status = get_post_field('post_status', $id);
    $order = get_post_field('menu_order', $id);
    $title = get_the_title();
    echo sprintf("%d | status=%s | order=%s | %s | %s\n", $id, $status, $order, $slug, $title);
}
wp_reset_postdata();

$published = function_exists('ftc_get_services') ? ftc_get_services(-1) : [];
echo 'Published catalog count via ftc_get_services: ' . count($published) . PHP_EOL;
foreach ($published as $item) {
    echo '  - ' . $item['title'] . PHP_EOL;
}
