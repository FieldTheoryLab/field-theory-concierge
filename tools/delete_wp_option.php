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
require_once(ABSPATH . 'wp-load.php');
$result = delete_option('ftc_bewell_gallery_2844');
echo $result ? "Option deleted successfully" : "Option not found or could not delete";
