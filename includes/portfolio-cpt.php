<?php
if (!defined('ABSPATH')) exit;

function ftc_register_portfolio_cpt(){
    register_post_type('ftc_portfolio', [
        'labels' => [
            'name' => 'Concierge Portfolio',
            'singular_name' => 'Portfolio Item',
            'add_new_item' => 'Add Portfolio Item',
            'edit_item' => 'Edit Portfolio Item',
        ],
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => 'field-theory-concierge',
        'menu_icon' => 'dashicons-portfolio',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'has_archive' => false,
        'rewrite' => ['slug' => 'ft-work'],
    ]);

    register_taxonomy('ftc_portfolio_tag', 'ftc_portfolio', [
        'label' => 'Portfolio Tags',
        'hierarchical' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'rewrite' => ['slug' => 'ft-work-tag'],
    ]);
}
add_action('init', 'ftc_register_portfolio_cpt');

function ftc_portfolio_meta_boxes(){
    add_meta_box('ftc_portfolio_details', 'Concierge Portfolio Details', 'ftc_portfolio_meta_box', 'ftc_portfolio', 'normal', 'high');
}
add_action('add_meta_boxes', 'ftc_portfolio_meta_boxes');

function ftc_portfolio_meta_box($post){
    wp_nonce_field('ftc_save_portfolio', 'ftc_portfolio_nonce');
    $industry = get_post_meta($post->ID, '_ftc_industry', true);
    $video = get_post_meta($post->ID, '_ftc_video_url', true);
    $url = get_post_meta($post->ID, '_ftc_project_url', true);
    $results = get_post_meta($post->ID, '_ftc_results', true);
    $featured = get_post_meta($post->ID, '_ftc_featured', true);
    ?>
    <p><label><strong>Industry</strong></label><br><input type="text" name="ftc_industry" value="<?php echo esc_attr($industry); ?>" class="widefat"></p>
    <p><label><strong>Video URL</strong> <span style="color:#666">(YouTube, Vimeo, or media file)</span></label><br><input type="url" name="ftc_video_url" value="<?php echo esc_attr($video); ?>" class="widefat"></p>
    <p><label><strong>Project URL</strong></label><br><input type="url" name="ftc_project_url" value="<?php echo esc_attr($url); ?>" class="widefat"></p>
    <p><label><strong>Results / Impact</strong></label><br><textarea name="ftc_results" class="widefat" rows="4"><?php echo esc_textarea($results); ?></textarea></p>
    <p><label><input type="checkbox" name="ftc_featured" value="1" <?php checked($featured, '1'); ?>> Featured in Concierge</label></p>
    <?php
}

function ftc_save_portfolio_meta($post_id){
    if (!isset($_POST['ftc_portfolio_nonce']) || !wp_verify_nonce($_POST['ftc_portfolio_nonce'], 'ftc_save_portfolio')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    update_post_meta($post_id, '_ftc_industry', sanitize_text_field($_POST['ftc_industry'] ?? ''));
    update_post_meta($post_id, '_ftc_video_url', esc_url_raw($_POST['ftc_video_url'] ?? ''));
    update_post_meta($post_id, '_ftc_project_url', esc_url_raw($_POST['ftc_project_url'] ?? ''));
    update_post_meta($post_id, '_ftc_results', sanitize_textarea_field($_POST['ftc_results'] ?? ''));
    update_post_meta($post_id, '_ftc_featured', isset($_POST['ftc_featured']) ? '1' : '0');
}
add_action('save_post_ftc_portfolio', 'ftc_save_portfolio_meta');
