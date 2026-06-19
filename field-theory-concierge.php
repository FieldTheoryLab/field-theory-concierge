<?php
/**
 * Plugin Name: Field Theory Concierge
 * Plugin URI: https://fieldtheory.ai
 * Description: A polished conversational concierge experience for Field Theory Lab with portfolio, services, contact, prompt routing, and editable responses.
 * Version: 2.8.43
 * Author: Jamie Rushad Gros
 * Author URI: https://fieldtheory.ai
 * Text Domain: field-theory-concierge
 */

if (!defined('ABSPATH')) exit;

define('FTC_VERSION', '2.8.43');
define('FTC_PATH', plugin_dir_path(__FILE__));
define('FTC_URL', plugin_dir_url(__FILE__));
define('FTC_THREE_URL', 'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js');

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

function ftc_register_public_routes(){
    add_rewrite_rule('^get-started/?$', 'index.php?ftc_route=get-started', 'top');
    add_rewrite_rule('^portfolio/?$', 'index.php?ftc_route=portfolio', 'top');
    add_rewrite_rule('^portfolio/([^/]+)/?$', 'index.php?ftc_route=portfolio&ftc_item=$matches[1]', 'top');
    add_rewrite_rule('^services/?$', 'index.php?ftc_route=services', 'top');
    add_rewrite_rule('^services/([^/]+)/?$', 'index.php?ftc_route=services&ftc_item=$matches[1]', 'top');
    add_rewrite_rule('^services/([^/]+)/([^/]+)/?$', 'index.php?ftc_route=services&ftc_item=$matches[1]&ftc_child=$matches[2]', 'top');
    add_rewrite_rule('^about/?$', 'index.php?ftc_route=about', 'top');
    add_rewrite_rule('^contact/?$', 'index.php?ftc_route=contact', 'top');
    add_rewrite_rule('^faq/?$', 'index.php?ftc_route=faq', 'top');
    add_rewrite_rule('^testimonials/?$', 'index.php?ftc_route=testimonials', 'top');
    add_rewrite_rule('^privacy/?$', 'index.php?ftc_route=privacy', 'top');
}
add_action('init', 'ftc_register_public_routes', 30);

function ftc_route_query_vars($vars){
    $vars[] = 'ftc_route';
    $vars[] = 'ftc_item';
    $vars[] = 'ftc_child';
    return $vars;
}
add_filter('query_vars', 'ftc_route_query_vars');

function ftc_maybe_flush_public_routes(){
    $key = 'ftc_public_routes_version';
    if(get_option($key) === FTC_VERSION) return;
    ftc_register_public_routes();
    flush_rewrite_rules(false);
    update_option($key, FTC_VERSION);
}
add_action('init', 'ftc_maybe_flush_public_routes', 99);

function ftc_route_clean_slug($value){
    $value = sanitize_title(wp_unslash((string)$value));
    return trim($value, '/');
}

function ftc_route_compact_key($value){
    if(function_exists('ftc_normalize_prompt_text')) $value = ftc_normalize_prompt_text($value);
    else $value = strtolower(preg_replace('/[^a-z0-9]+/', ' ', (string)$value));
    return preg_replace('/\s+/', '', $value);
}

function ftc_public_route_from_path(){
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';
    $path = wp_parse_url($request_uri, PHP_URL_PATH);
    if(!$path) return ['route'=>'','item'=>'','child'=>''];

    $path = rawurldecode($path);
    $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
    $home_path = $home_path ? trim($home_path, '/') : '';
    $relative = trim($path, '/');

    if($home_path !== '' && strpos($relative . '/', $home_path . '/') === 0){
        $relative = trim(substr($relative, strlen($home_path)), '/');
    }

    if($relative === '') return ['route'=>'','item'=>'','child'=>''];

    $parts = array_values(array_filter(explode('/', $relative), 'strlen'));
    $first = ftc_route_clean_slug($parts[0] ?? '');

    if(in_array($first, ['get-started','about','contact','faq','testimonials','privacy'], true) && count($parts) === 1){
        return ['route'=>$first,'item'=>'','child'=>''];
    }

    if($first === 'portfolio' && count($parts) <= 2){
        return [
            'route'=>'portfolio',
            'item'=>ftc_route_clean_slug($parts[1] ?? ''),
            'child'=>'',
        ];
    }

    if($first === 'services' && count($parts) <= 3){
        return [
            'route'=>'services',
            'item'=>ftc_route_clean_slug($parts[1] ?? ''),
            'child'=>ftc_route_clean_slug($parts[2] ?? ''),
        ];
    }

    return ['route'=>'','item'=>'','child'=>''];
}

function ftc_current_route_vars(){
    static $vars = null;
    if($vars !== null) return $vars;

    $vars = [
        'route'=>ftc_route_clean_slug(get_query_var('ftc_route')),
        'item'=>ftc_route_clean_slug(get_query_var('ftc_item')),
        'child'=>ftc_route_clean_slug(get_query_var('ftc_child')),
    ];

    if($vars['route'] === ''){
        $vars = ftc_public_route_from_path();
    }

    return $vars;
}

function ftc_find_post_for_public_route($post_type, $slug){
    $slug = ftc_route_clean_slug($slug);
    if($slug === '') return null;
    $post = get_page_by_path($slug, OBJECT, $post_type);
    if($post && $post->post_status === 'publish') return $post;

    $needle = ftc_route_compact_key($slug);
    $items = get_posts([
        'post_type'=>$post_type,
        'post_status'=>'publish',
        'posts_per_page'=>-1,
        'orderby'=>'menu_order title',
        'order'=>'ASC',
    ]);
    foreach($items as $item){
        $keys = [
            ftc_route_compact_key($item->post_name),
            ftc_route_compact_key($item->post_title),
        ];
        if(in_array($needle, $keys, true)) return $item;
    }
    return null;
}

function ftc_find_service_for_public_route($slug){
    $slug = ftc_route_clean_slug($slug);
    $aliases = [
        'website'=>'website-development-core-tech',
        'websites'=>'website-development-core-tech',
        'web-development'=>'website-development-core-tech',
        'website-development'=>'website-development-core-tech',
        'core-tech'=>'website-development-core-tech',
        'ecommerce'=>'ecommerce-conversion-rate-optimization-cro',
        'commerce'=>'ecommerce-conversion-rate-optimization-cro',
        'cro'=>'ecommerce-conversion-rate-optimization-cro',
        'conversion'=>'ecommerce-conversion-rate-optimization-cro',
        'data'=>'data-analysis-visualization',
        'analytics'=>'data-analysis-visualization',
        'dashboards'=>'data-analysis-visualization',
        'seo'=>'search-discovery-optimization-seo-aeo',
        'aeo'=>'search-discovery-optimization-seo-aeo',
        'search'=>'search-discovery-optimization-seo-aeo',
        'search-discovery'=>'search-discovery-optimization-seo-aeo',
        'marketing'=>'digital-marketing-growth-strategy',
        'growth'=>'digital-marketing-growth-strategy',
        'digital-marketing'=>'digital-marketing-growth-strategy',
        'ai'=>'creative-technology-innovation',
        'automation'=>'creative-technology-innovation',
        'innovation'=>'creative-technology-innovation',
        'technology'=>'creative-technology-innovation',
    ];
    if(isset($aliases[$slug])) $slug = $aliases[$slug];
    return ftc_find_post_for_public_route('ftc_service', $slug);
}

function ftc_route_child_prompt($service_id, $child_slug){
    $needle = ftc_route_compact_key($child_slug);
    if(!$service_id || $needle === '' || !function_exists('ftc_service_task_groups')) return '';
    foreach(ftc_service_task_groups($service_id) as $group=>$tasks){
        if(ftc_route_compact_key($group) === $needle) return $group;
        foreach($tasks as $task){
            if(ftc_route_compact_key($task) === $needle) return $task;
        }
    }
    return ucwords(str_replace('-', ' ', ftc_route_clean_slug($child_slug)));
}

function ftc_get_route_data(){
    $route_vars = ftc_current_route_vars();
    $route = $route_vars['route'];
    if($route === '') return null;
    $item = $route_vars['item'];
    $child = $route_vars['child'];

    switch($route){
        case 'get-started':
            return ['type'=>'prompt','prompt'=>'Get Started','title'=>'Get Started'];
        case 'portfolio':
            if($item){
                $project = ftc_find_post_for_public_route('ftc_portfolio', $item);
                if($project) return ['type'=>'project','id'=>$project->ID,'prompt'=>get_the_title($project),'title'=>get_the_title($project)];
            }
            return ['type'=>'prompt','prompt'=>'Show me your work!','title'=>'Portfolio'];
        case 'services':
            if($item){
                $service = ftc_find_service_for_public_route($item);
                if($service && $child){
                    $child_prompt = ftc_route_child_prompt($service->ID, $child);
                    return ['type'=>'prompt','prompt'=>$child_prompt,'title'=>$child_prompt ?: get_the_title($service)];
                }
                if($service) return ['type'=>'service','id'=>$service->ID,'prompt'=>get_the_title($service),'title'=>get_the_title($service)];
            }
            return ['type'=>'prompt','prompt'=>'Our Services','title'=>'Services'];
        case 'about':
            return ['type'=>'prompt','prompt'=>'Tell me about your company','title'=>'About Field Theory Lab'];
        case 'contact':
            return ['type'=>'prompt','prompt'=>'Request a Proposal','title'=>'Contact Field Theory Lab'];
        case 'faq':
            return ['type'=>'prompt','prompt'=>'FAQ','title'=>'Helpful Prompts'];
        case 'testimonials':
            return ['type'=>'prompt','prompt'=>'Testimonials','title'=>'Testimonials'];
        case 'privacy':
            return ['type'=>'prompt','prompt'=>'Privacy Policy','title'=>'Privacy Policy'];
    }
    return ['type'=>'prompt','prompt'=>'Get Started','title'=>'Field Theory Lab'];
}

function ftc_route_document_title(){
    $route = ftc_get_route_data();
    $title = $route['title'] ?? 'Field Theory Lab';
    return $title . ' - Field Theory Lab';
}

function ftc_disable_conflicting_route_canonical($canonical){
    $route_vars = ftc_current_route_vars();
    return !empty($route_vars['route']) ? false : $canonical;
}
add_filter('wpseo_canonical', 'ftc_disable_conflicting_route_canonical', 99);
add_filter('rank_math/frontend/canonical', 'ftc_disable_conflicting_route_canonical', 99);
add_filter('aioseo_canonical_url', 'ftc_disable_conflicting_route_canonical', 99);

function ftc_noindex_internal_concierge_cpts($robots){
    if(is_singular(['ftc_response','ftc_portfolio','ftc_service','ftc_faq','ftc_testimonial'])){
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'ftc_noindex_internal_concierge_cpts');

function ftc_render_public_route(){
    $route_vars = ftc_current_route_vars();
    if(!$route_vars['route']) return;
    global $wp_query;
    status_header(200);
    if($wp_query) $wp_query->is_404 = false;
    remove_action('wp_head', '_wp_render_title_tag', 1);
    remove_action('wp_head', 'rel_canonical');
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo esc_html(ftc_route_document_title()); ?></title>
  <meta name="description" content="<?php echo esc_attr(ftc_route_meta_description()); ?>">
  <link rel="canonical" href="<?php echo esc_url(ftc_route_canonical_url()); ?>">
  <?php
  ob_start();
  wp_head();
  $ftc_head = ob_get_clean();
  $ftc_head = preg_replace('/<title\b[^>]*>.*?<\/title>\s*/is', '', $ftc_head);
  $ftc_head = preg_replace('/<link\b[^>]*rel=["\']canonical["\'][^>]*>\s*/i', '', $ftc_head);
  $ftc_head = preg_replace('/<meta\b[^>]*name=["\']description["\'][^>]*>\s*/i', '', $ftc_head);
  echo $ftc_head;
  ?>
</head>
<body <?php body_class('ftc-public-route'); ?>>
<?php if(function_exists('wp_body_open')) wp_body_open(); ?>
<?php echo ftc_shortcode(); ?>
<?php wp_footer(); ?>
</body>
</html><?php
    exit;
}
add_action('template_redirect', 'ftc_render_public_route', 0);



function ftc_mobile_theme_color(){
    echo '<meta name="theme-color" content="#101010">' . "\n";
    echo '<meta name="msapplication-navbutton-color" content="#101010">' . "\n";
    echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
}
add_action('wp_head', 'ftc_mobile_theme_color', 1);

function ftc_enqueue_assets(){
    $settings = ftc_get_settings();
    $public_settings = $settings;
    unset($public_settings['recaptcha_secret_key']);
    wp_enqueue_style('ftc-app', FTC_URL . 'assets/css/app.css', [], FTC_VERSION);
    wp_enqueue_script('ftc-three', FTC_THREE_URL, [], 'r128', true);
    wp_enqueue_script('ftc-app', FTC_URL . 'assets/js/app.js', ['ftc-three'], FTC_VERSION, true);
    if(!empty($settings['recaptcha_site_key'])){
        wp_enqueue_script(
            'google-recaptcha',
            'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($settings['recaptcha_site_key']),
            [],
            null,
            true
        );
    }
    wp_localize_script('ftc-app', 'ftcData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ftc_nonce'),
        'settings' => $public_settings,
        'responses' => ftc_get_responses(),
        'portfolio' => ftc_get_demo_portfolio(),
        'route' => ftc_get_route_data(),
        'recaptchaSiteKey' => $settings['recaptcha_site_key'] ?? '',
        'recaptchaAction' => 'ftc_submit_inquiry',
    ]);
}
add_action('wp_enqueue_scripts', 'ftc_enqueue_assets');

function ftc_preload_scene_assets(){
    echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
    echo '<link rel="preload" href="' . esc_url(FTC_THREE_URL) . '" as="script" crossorigin>' . "\n";
}
add_action('wp_head', 'ftc_preload_scene_assets', 0);

function ftc_route_canonical_url(){
    $route_vars = ftc_current_route_vars();
    $route = $route_vars['route'];
    if($route === '') return home_url('/');
    $parts = [$route];
    $item = $route_vars['item'];
    $child = $route_vars['child'];
    if($item) $parts[] = $item;
    if($child) $parts[] = $child;
    return home_url('/' . implode('/', $parts) . '/');
}

function ftc_route_meta_description(){
    $route = ftc_get_route_data();
    if(!$route) return 'Field Theory Lab helps organizations improve websites, marketing, analytics, ecommerce, SEO, AI, automation, and digital systems.';
    if(!empty($route['id']) && ($route['type'] ?? '') === 'service'){
        return wp_strip_all_tags(get_the_excerpt($route['id']) ?: ftc_service_detail_headline($route['id']));
    }
    if(!empty($route['id']) && ($route['type'] ?? '') === 'project'){
        return wp_strip_all_tags(get_the_excerpt($route['id']) ?: get_the_title($route['id']));
    }
    return wp_strip_all_tags(($route['title'] ?? 'Field Theory Lab') . ' from Field Theory Lab.');
}

function ftc_render_route_prompt_response($prompt,$settings){
    $normalized_prompt = function_exists('ftc_normalize_prompt_text') ? ftc_normalize_prompt_text($prompt) : strtolower(trim((string)$prompt));
    if(in_array($normalized_prompt, ['get started','start','home'], true)){
        $response = ftc_pick_response('Get Started');
        ftc_render_get_started_sequence($response,$settings,false);
        return;
    }

    if(function_exists('ftc_core_response_for_prompt') && function_exists('ftc_render_cpt_response_shell')){
        $core_response = ftc_core_response_for_prompt($prompt);
        if($core_response){
            ftc_render_cpt_response_shell($core_response, $settings);
            return;
        }
    }

    if($prompt === 'FAQ'){
        ftc_render_helpful_prompt_response($settings);
        return;
    }

    $direct_faq = ftc_direct_faq_response($prompt);
    if($direct_faq){
        ftc_render_response_shell($direct_faq,$settings,$prompt);
        return;
    }

    $exact_service_id = ftc_find_exact_service_by_prompt($prompt);
    if($exact_service_id){
        ftc_render_service_detail_response_markup($exact_service_id);
        return;
    }

    $child_service = ftc_find_service_child_match($prompt);
    if($child_service){
        ftc_render_child_service_response($child_service,$settings);
        return;
    }

    $portfolio_id = ftc_find_portfolio_by_search($prompt);
    if($portfolio_id){
        ftc_render_project_detail_markup($portfolio_id);
        return;
    }

    $response = ftc_pick_response($prompt);
    if(($response['layout'] ?? '') === 'home'){
        ftc_render_get_started_sequence($response,$settings,false);
        return;
    }
    ftc_render_response_shell($response,$settings,$prompt);
}

function ftc_route_project_response($post_id){
    $template_id = get_post_meta($post_id,'_ftc_elementor_template_id',true);
    if($template_id){
        echo '<div class="ftc-response-shell ftc-response-layout-project"><section class="ftc-response-content"><div class="ftc-elementor-template ftc-project-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div></section>';
        echo '<div class="ftc-response-actions ftc-project-actions"><div class="ftc-response-actions-left"><button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Show me your work!" data-prompt="Show me your work!">Back to Portfolio</button><button type="button" class="ftc-blue-outline-btn" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div>';
        echo '</div>';
        return;
    }
    ftc_render_project_detail_markup($post_id);
}

function ftc_render_route_initial_response($route=null){
    $route = $route ?: ftc_get_route_data();
    if(!$route) return '';
    $settings = ftc_get_settings();
    ob_start();
    if(($route['type'] ?? '') === 'service' && !empty($route['id'])){
        ftc_render_service_detail_response_markup(absint($route['id']));
    } elseif(($route['type'] ?? '') === 'project' && !empty($route['id'])){
        ftc_route_project_response(absint($route['id']));
    } else {
        ftc_render_route_prompt_response($route['prompt'] ?? 'Get Started',$settings);
    }
    return trim(ob_get_clean());
}

function ftc_shortcode($atts = []){
    ob_start();
    include FTC_PATH . 'templates/concierge-app.php';
    return ob_get_clean();
}
add_shortcode('ft_concierge', 'ftc_shortcode');
add_shortcode('field_theory_concierge', 'ftc_shortcode');
