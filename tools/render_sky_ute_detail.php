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

$post_id = 367;
ob_start();
ftc_render_project_detail_markup($post_id);
$html = ob_get_clean();
echo $html;
preg_match_all('/<img[^>]+src="([^"]+)"/', $html, $m);
echo "\n\nIMAGES:\n";
foreach(array_unique($m[1] ?? []) as $src) echo $src.PHP_EOL;
