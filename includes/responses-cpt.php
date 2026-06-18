<?php
if (!defined('ABSPATH')) exit;

/**
 * Field Theory Concierge Response Engine 2.8.0
 *
 * This is intentionally non-invasive:
 * - Existing legacy response rendering remains intact.
 * - Existing Service / Portfolio / FAQ / Testimonial CPT rendering remains intact.
 * - This adds a new Response CPT and matching/rendering helpers that can be adopted gradually.
 */

function ftc_register_response_cpt(){
    register_post_type('ftc_response', [
        'labels' => [
            'name' => 'Concierge Responses',
            'singular_name' => 'Concierge Response',
            'add_new_item' => 'Add Concierge Response',
            'edit_item' => 'Edit Concierge Response',
            'new_item' => 'New Concierge Response',
            'view_item' => 'View Concierge Response',
            'search_items' => 'Search Concierge Responses',
            'not_found' => 'No concierge responses found',
            'menu_name' => 'Response Engine',
        ],
        'public' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => 'field-theory-concierge',
        'show_in_rest' => true,
        'supports' => ['title','editor','excerpt','thumbnail','page-attributes','custom-fields','elementor'],
        'menu_icon' => 'dashicons-format-chat',
        'capability_type' => 'post',
        'hierarchical' => false,
    ]);
}
add_action('init','ftc_register_response_cpt');

function ftc_response_meta_defaults(){
    return [
        '_ftc_response_keywords' => '',
        '_ftc_response_followups' => '',
        '_ftc_response_status' => 'active',
        '_ftc_response_type' => 'legacy',
        '_ftc_response_intent_type' => 'general',
        '_ftc_response_template_id' => 0,
        '_ftc_response_preview_template_id' => 0,
        '_ftc_response_full_template_id' => 0,
        '_ftc_response_legacy_layout' => 'none',
        '_ftc_response_blocks' => '',
        '_ftc_response_prompt_label' => '',
        '_ftc_response_intro_phrase' => '',
        '_ftc_response_content_preview' => '',
        '_ftc_response_full_content' => '',
    ];
}

function ftc_response_block_types(){
    return [
        'text' => 'Text / Native Content',
        'video' => 'Video',
        'services' => 'Services Preview',
        'portfolio' => 'Portfolio Preview',
        'featured_project' => 'Featured Project',
        'faq' => 'FAQ',
        'testimonials' => 'Testimonials',
        'contact' => 'Contact',
        'elementor_template' => 'Elementor Template',
        'elementor_preview_template' => 'Elementor Preview Template',
        'elementor_full_template' => 'Elementor Full Template',
        'custom_html' => 'Custom HTML',
        'legacy_layout' => 'Legacy Layout',
    ];
}


function ftc_response_intent_types(){
    return [
        'general' => 'General Response',
        'get_started' => 'Get Started',
        'about' => 'About / Company',
        'services' => 'Services Overview',
        'service_detail' => 'Service Detail',
        'portfolio' => 'Portfolio Overview',
        'project_detail' => 'Project Detail',
        'faq' => 'FAQ / Quick Answer',
        'contact' => 'Contact / Request a Proposal',
        'privacy' => 'Privacy',
        'custom' => 'Custom Campaign / Landing Response',
    ];
}

function ftc_enable_elementor_for_responses(){
    add_post_type_support('ftc_response','elementor');

    // Elementor stores enabled post types in this option.
    $supported = get_option('elementor_cpt_support', []);
    if(!is_array($supported)) $supported = [];
    if(!in_array('ftc_response', $supported, true)){
        $supported[] = 'ftc_response';
        update_option('elementor_cpt_support', array_values(array_unique($supported)));
    }
}
add_action('init','ftc_enable_elementor_for_responses', 30);
add_action('admin_init','ftc_enable_elementor_for_responses');

function ftc_response_elementor_edit_url($post_id){
    if(!did_action('elementor/loaded') && !class_exists('\\Elementor\\Plugin')) return '';
    return admin_url('post.php?post='.absint($post_id).'&action=elementor');
}

function ftc_add_response_meta_boxes(){
    add_meta_box('ftc_response_engine_meta','Response Engine','ftc_response_meta_box','ftc_response','normal','high');
}
add_action('add_meta_boxes','ftc_add_response_meta_boxes');

function ftc_response_meta_box($post){
    wp_nonce_field('ftc_save_response_meta','ftc_response_meta_nonce');
    $defaults = ftc_response_meta_defaults();
    $meta = [];
    foreach($defaults as $key=>$default){
        $meta[$key] = get_post_meta($post->ID,$key,true);
        if($meta[$key] === '') $meta[$key] = $default;
    }

    $blocks = json_decode($meta['_ftc_response_blocks'], true);
    if(!is_array($blocks)) $blocks = [];

    ?>
    <style>
      .ftc-response-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:14px 0}
      .ftc-response-admin-grid input,.ftc-response-admin-grid select,.ftc-response-admin-full textarea,.ftc-response-admin-full input{width:100%}
      .ftc-response-admin-full{margin:14px 0}
      .ftc-response-block-row{border:1px solid #dcdcde;border-radius:8px;padding:14px;margin:12px 0;background:#fff}
      .ftc-response-block-row summary{font-weight:700;cursor:pointer}
      .ftc-help{color:#646970;font-size:12px}
      @media(max-width:782px){.ftc-response-admin-grid{grid-template-columns:1fr}}
    </style>

    <p class="ftc-help">A Response is an AJAX/chat-style experience. It can match keywords, display a response title/description first, then extend below the fold with blocks such as video, services, portfolio, FAQ, contact, custom HTML, or an Elementor template.</p>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Status</strong></label><br>
        <select name="ftc_response_status">
          <option value="active" <?php selected($meta['_ftc_response_status'],'active'); ?>>Active</option>
          <option value="inactive" <?php selected($meta['_ftc_response_status'],'inactive'); ?>>Inactive</option>
        </select>
      </p>
      <p>
        <label><strong>Response Type / Purpose</strong></label><br>
        <select name="ftc_response_intent_type">
          <?php foreach(ftc_response_intent_types() as $type=>$label): ?>
            <option value="<?php echo esc_attr($type); ?>" <?php selected($meta['_ftc_response_intent_type'],$type); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
    </div>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Rendering Mode</strong></label><br>
        <select name="ftc_response_type">
          <option value="legacy" <?php selected($meta['_ftc_response_type'],'legacy'); ?>>Legacy fallback / current design</option>
          <option value="blocks" <?php selected($meta['_ftc_response_type'],'blocks'); ?>>Response blocks</option>
          <option value="elementor_template" <?php selected($meta['_ftc_response_type'],'elementor_template'); ?>>Elementor template by ID</option>
          <option value="elementor_canvas" <?php selected($meta['_ftc_response_type'],'elementor_canvas'); ?>>Edit this Response with Elementor</option>
          <option value="prompt_only" <?php selected($meta['_ftc_response_type'],'prompt_only'); ?>>Prompt only</option>
        </select>
      </p>
      <p>
        <label><strong>Elementor Editing</strong></label><br>
        <?php $edit_url = ftc_response_elementor_edit_url($post->ID); ?>
        <?php if($edit_url): ?>
          <a class="button button-primary" href="<?php echo esc_url($edit_url); ?>">Edit This Response with Elementor</a>
        <?php else: ?>
          <span class="ftc-help">Elementor is not active or not available.</span>
        <?php endif; ?>
      </p>
    </div>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Prompt Label</strong></label><br>
        <input type="text" name="ftc_response_prompt_label" value="<?php echo esc_attr($meta['_ftc_response_prompt_label']); ?>" placeholder="Defaults to title">
      </p>
      <p>
        <label><strong>Intro Phrase</strong></label><br>
        <input type="text" name="ftc_response_intro_phrase" value="<?php echo esc_attr($meta['_ftc_response_intro_phrase']); ?>" placeholder="Typed response phrase. Defaults to the excerpt.">
      </p>
    </div>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Elementor Preview Template ID</strong></label><br>
        <input type="number" name="ftc_response_preview_template_id" value="<?php echo esc_attr($meta['_ftc_response_preview_template_id']); ?>">
        <span class="ftc-help">Optional template for the first compact response preview.</span>
      </p>
      <p>
        <label><strong>Elementor Full Template ID</strong></label><br>
        <input type="number" name="ftc_response_full_template_id" value="<?php echo esc_attr($meta['_ftc_response_full_template_id']); ?>">
        <span class="ftc-help">Optional template for the full response after the preview.</span>
      </p>
    </div>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Legacy Elementor Template ID</strong></label><br>
        <input type="number" name="ftc_response_template_id" value="<?php echo esc_attr($meta['_ftc_response_template_id']); ?>">
        <span class="ftc-help">Use this for a reusable Elementor Template. Use “Edit this Response with Elementor” to design this response directly.</span>
      </p>
    </div>

    <div class="ftc-response-admin-full">
      <label><strong>Content Preview</strong> <span class="ftc-help">Short editable content shown in the first response.</span></label><br>
      <textarea name="ftc_response_content_preview" rows="5"><?php echo esc_textarea($meta['_ftc_response_content_preview']); ?></textarea>
    </div>

    <div class="ftc-response-admin-full">
      <label><strong>Full Content</strong> <span class="ftc-help">Longer editable content shown when the response expands below the preview.</span></label><br>
      <textarea name="ftc_response_full_content" rows="7"><?php echo esc_textarea($meta['_ftc_response_full_content']); ?></textarea>
    </div>

    <div class="ftc-response-admin-full">
      <label><strong>Trigger Prompts</strong> <span class="ftc-help">One per line. If multiple responses share a prompt, the engine can return multiple response cards.</span></label><br>
      <textarea name="ftc_response_keywords" rows="5"><?php echo esc_textarea($meta['_ftc_response_keywords']); ?></textarea>
    </div>

    <div class="ftc-response-admin-full">
      <label><strong>Related Prompts</strong> <span class="ftc-help">One per line.</span></label><br>
      <textarea name="ftc_response_followups" rows="4"><?php echo esc_textarea($meta['_ftc_response_followups']); ?></textarea>
    </div>

    <div class="ftc-response-admin-grid">
      <p>
        <label><strong>Legacy Layout</strong></label><br>
        <select name="ftc_response_legacy_layout">
          <?php foreach(['none','home','about','portfolio','services','faq','contact','testimonials'] as $layout): ?>
            <option value="<?php echo esc_attr($layout); ?>" <?php selected($meta['_ftc_response_legacy_layout'],$layout); ?>><?php echo esc_html($layout); ?></option>
          <?php endforeach; ?>
        </select>
      </p>
      <p class="ftc-help">Legacy layout keeps the current working design intact while the new block engine is phased in.</p>
    </div>

    <hr>
    <h3>Response Blocks</h3>
    <p class="ftc-help">Blocks render after the response title/description. This is the “big chat” model: first the response, then the page extends below if there is more content.</p>

    <?php
    $types = ftc_response_block_types();
    $block_count = max(count($blocks), 3);
    for($i=0; $i<$block_count; $i++):
        $block = $blocks[$i] ?? ['type'=>'','title'=>'','content'=>'','template_id'=>'','source'=>''];
    ?>
      <details class="ftc-response-block-row" <?php open_if($i===0); ?>>
        <summary>Block <?php echo esc_html($i+1); ?> <?php echo !empty($block['type']) ? '— '.esc_html($types[$block['type']] ?? $block['type']) : ''; ?></summary>
        <div class="ftc-response-admin-grid">
          <p>
            <label><strong>Block Type</strong></label><br>
            <select name="ftc_response_blocks[<?php echo esc_attr($i); ?>][type]">
              <option value="">None</option>
              <?php foreach($types as $type=>$label): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($block['type'] ?? '', $type); ?>><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </p>
          <p>
            <label><strong>Block Title</strong></label><br>
            <input type="text" name="ftc_response_blocks[<?php echo esc_attr($i); ?>][title]" value="<?php echo esc_attr($block['title'] ?? ''); ?>">
          </p>
        </div>
        <div class="ftc-response-admin-grid">
          <p>
            <label><strong>Template / Post ID</strong></label><br>
            <input type="number" name="ftc_response_blocks[<?php echo esc_attr($i); ?>][template_id]" value="<?php echo esc_attr($block['template_id'] ?? ''); ?>">
          </p>
          <p>
            <label><strong>Source / Slug / URL</strong></label><br>
            <input type="text" name="ftc_response_blocks[<?php echo esc_attr($i); ?>][source]" value="<?php echo esc_attr($block['source'] ?? ''); ?>" placeholder="video URL, service slug, portfolio slug, etc.">
          </p>
        </div>
        <p>
          <label><strong>Block Content / HTML</strong></label><br>
          <textarea name="ftc_response_blocks[<?php echo esc_attr($i); ?>][content]" rows="4" style="width:100%"><?php echo esc_textarea($block['content'] ?? ''); ?></textarea>
        </p>
      </details>
    <?php endfor; ?>
    <?php
}

function open_if($condition){
    if($condition) echo 'open';
}

function ftc_save_response_meta($post_id){
    if(!isset($_POST['ftc_response_meta_nonce']) || !wp_verify_nonce($_POST['ftc_response_meta_nonce'],'ftc_save_response_meta')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(get_post_type($post_id) !== 'ftc_response') return;
    if(!current_user_can('edit_post',$post_id)) return;

    update_post_meta($post_id,'_ftc_response_keywords', sanitize_textarea_field(wp_unslash($_POST['ftc_response_keywords'] ?? '')));
    update_post_meta($post_id,'_ftc_response_followups', sanitize_textarea_field(wp_unslash($_POST['ftc_response_followups'] ?? '')));
    update_post_meta($post_id,'_ftc_response_status', sanitize_key(wp_unslash($_POST['ftc_response_status'] ?? 'active')));
    update_post_meta($post_id,'_ftc_response_type', sanitize_key(wp_unslash($_POST['ftc_response_type'] ?? 'legacy')));
    update_post_meta($post_id,'_ftc_response_intent_type', sanitize_key(wp_unslash($_POST['ftc_response_intent_type'] ?? 'general')));
    update_post_meta($post_id,'_ftc_response_template_id', absint($_POST['ftc_response_template_id'] ?? 0));
    update_post_meta($post_id,'_ftc_response_preview_template_id', absint($_POST['ftc_response_preview_template_id'] ?? 0));
    update_post_meta($post_id,'_ftc_response_full_template_id', absint($_POST['ftc_response_full_template_id'] ?? 0));
    update_post_meta($post_id,'_ftc_response_legacy_layout', sanitize_key(wp_unslash($_POST['ftc_response_legacy_layout'] ?? 'none')));
    update_post_meta($post_id,'_ftc_response_prompt_label', sanitize_text_field(wp_unslash($_POST['ftc_response_prompt_label'] ?? '')));
    update_post_meta($post_id,'_ftc_response_intro_phrase', sanitize_text_field(wp_unslash($_POST['ftc_response_intro_phrase'] ?? '')));
    update_post_meta($post_id,'_ftc_response_content_preview', wp_kses_post(wp_unslash($_POST['ftc_response_content_preview'] ?? '')));
    update_post_meta($post_id,'_ftc_response_full_content', wp_kses_post(wp_unslash($_POST['ftc_response_full_content'] ?? '')));

    $blocks = [];
    foreach((array)($_POST['ftc_response_blocks'] ?? []) as $block){
        $type = sanitize_key(wp_unslash($block['type'] ?? ''));
        if(!$type) continue;
        $blocks[] = [
            'type' => $type,
            'title' => sanitize_text_field(wp_unslash($block['title'] ?? '')),
            'content' => wp_kses_post(wp_unslash($block['content'] ?? '')),
            'template_id' => absint($block['template_id'] ?? 0),
            'source' => sanitize_text_field(wp_unslash($block['source'] ?? '')),
        ];
    }
    update_post_meta($post_id,'_ftc_response_blocks', wp_json_encode($blocks));
}
add_action('save_post_ftc_response','ftc_save_response_meta');

function ftc_get_response_post_data($post_id){
    $post = get_post($post_id);
    if(!$post || $post->post_type !== 'ftc_response') return null;

    $keywords = get_post_meta($post_id,'_ftc_response_keywords',true);
    $followups = get_post_meta($post_id,'_ftc_response_followups',true);
    $blocks = json_decode(get_post_meta($post_id,'_ftc_response_blocks',true), true);
    if(!is_array($blocks)) $blocks = [];
    $content_preview = get_post_meta($post_id,'_ftc_response_content_preview',true);
    $full_content = get_post_meta($post_id,'_ftc_response_full_content',true);
    $legacy_template_id = absint(get_post_meta($post_id,'_ftc_response_template_id',true));
    $preview_template_id = absint(get_post_meta($post_id,'_ftc_response_preview_template_id',true));
    $full_template_id = absint(get_post_meta($post_id,'_ftc_response_full_template_id',true));

    return [
        'id' => $post_id,
        'source' => 'cpt',
        'title' => get_the_title($post_id),
        'description' => get_post_meta($post_id,'_ftc_response_intro_phrase',true) ?: (has_excerpt($post_id) ? get_the_excerpt($post_id) : ''),
        'content_preview' => $content_preview,
        'full_content' => $full_content,
        'html' => $full_content !== '' ? $full_content : apply_filters('the_content', $post->post_content),
        'keywords' => array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$keywords)))),
        'followups' => array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)$followups)))),
        'status' => get_post_meta($post_id,'_ftc_response_status',true) ?: 'active',
        'type' => get_post_meta($post_id,'_ftc_response_type',true) ?: 'legacy',
        'intent_type' => get_post_meta($post_id,'_ftc_response_intent_type',true) ?: 'general',
        'template_id' => $legacy_template_id,
        'preview_template_id' => $preview_template_id,
        'full_template_id' => $full_template_id,
        'legacy_layout' => get_post_meta($post_id,'_ftc_response_legacy_layout',true) ?: 'none',
        'prompt_label' => get_post_meta($post_id,'_ftc_response_prompt_label',true),
        'blocks' => $blocks,
        'sort_order' => intval($post->menu_order),
    ];
}

function ftc_keyword_score_response_data($term, $response){
    $term = strtolower(trim((string)$term));
    if($term === '') return 0;
    $score = 0;
    $title = strtolower($response['title'] ?? '');
    $prompt = strtolower($response['prompt_label'] ?? '');
    foreach(array_filter([$title,$prompt]) as $label){
        if($term === $label) $score += 120;
        elseif(strpos($term,$label) !== false) $score += 90;
        elseif(strlen($term) >= 4 && strpos($label,$term) !== false) $score += 50;
    }
    foreach(($response['keywords'] ?? []) as $keyword){
        $keyword = strtolower(trim((string)$keyword));
        if(!$keyword) continue;
        if($term === $keyword) $score += 100;
        elseif(strpos($term,$keyword) !== false) $score += 75;
        elseif(strlen($term) >= 4 && strpos($keyword,$term) !== false) $score += 35;
    }
    return $score;
}

function ftc_find_response_cpt_matches($term){
    $q = new WP_Query([
        'post_type' => 'ftc_response',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => ['menu_order'=>'ASC','title'=>'ASC'],
    ]);
    $matches = [];
    if($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $data = ftc_get_response_post_data(get_the_ID());
            if(!$data || ($data['status'] ?? 'active') !== 'active') continue;
            $score = ftc_keyword_score_response_data($term, $data);
            if($score > 0){
                $data['_score'] = $score;
                $matches[] = $data;
            }
        }
        wp_reset_postdata();
    }
    usort($matches, function($a,$b){
        $as = intval($a['_score'] ?? 0);
        $bs = intval($b['_score'] ?? 0);
        if($as === $bs) return intval($a['sort_order'] ?? 50) <=> intval($b['sort_order'] ?? 50);
        return $bs <=> $as;
    });
    return $matches;
}

function ftc_render_response_engine_block($block){
    $type = $block['type'] ?? '';
    $title = $block['title'] ?? '';
    $content = $block['content'] ?? '';
    $template_id = absint($block['template_id'] ?? 0);
    $source = $block['source'] ?? '';

    echo '<section class="ftc-response-block ftc-response-block-'.esc_attr($type).'">';
    if($title) echo '<h3>'.esc_html($title).'</h3>';

    switch($type){
        case 'text':
        case 'custom_html':
            echo ftc_render_editable_html($content);
            break;
        case 'video':
            $url = esc_url($source ?: $content);
            if($url) echo '<video class="ftc-response-video" controls playsinline src="'.$url.'"></video>';
            break;
        case 'services':
            if(function_exists('ftc_render_services_panel')) ftc_render_services_panel();
            break;
        case 'portfolio':
            if(function_exists('ftc_render_portfolio_masonry')) ftc_render_portfolio_masonry(9);
            break;
        case 'featured_project':
            if(function_exists('ftc_render_portfolio_masonry')) ftc_render_portfolio_masonry(1);
            break;
        case 'faq':
            if(function_exists('ftc_render_faq_panel')) ftc_render_faq_panel();
            break;
        case 'testimonials':
            if(function_exists('ftc_render_testimonials_panel')) ftc_render_testimonials_panel();
            break;
        case 'contact':
            if(function_exists('ftc_render_contact_panel')) ftc_render_contact_panel(ftc_get_settings());
            break;
        case 'elementor_template':
        case 'elementor_preview_template':
        case 'elementor_full_template':
            if($template_id && function_exists('ftc_render_elementor_template_by_id')) echo ftc_render_elementor_template_by_id($template_id);
            break;
        case 'legacy_layout':
            // This block intentionally does not render here; the main legacy fallback handles it.
            break;
    }

    echo '</section>';
}

function ftc_seed_default_response_posts(){
    if(get_option('ftc_response_posts_seeded')) return;
    $existing = get_posts(['post_type'=>'ftc_response','post_status'=>'any','numberposts'=>1]);
    if($existing){ update_option('ftc_response_posts_seeded', 1); return; }

    $defaults = [
        [
            'post_title'=>'Get Started',
            'post_excerpt'=>'Field Theory Lab helps organizations grow through better technology, smarter marketing, and deeper insights.',
            'intro_phrase'=>'Field Theory Lab helps organizations grow through better technology, smarter marketing, and deeper insights.',
            'menu_order'=>10,
            'keywords'=>"get started\nstart\noverview\nhome",
            'layout'=>'home',
            'intent_type'=>'get_started',
            'blocks'=>[
                ['type'=>'legacy_layout','title'=>'Current Get Started Layout','content'=>'','template_id'=>0,'source'=>'home']
            ],
        ],
        [
            'post_title'=>'Show Me Your Work',
            'post_excerpt'=>'A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.',
            'intro_phrase'=>'A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.',
            'menu_order'=>20,
            'keywords'=>"show me your work\nportfolio\nprojects\ncase studies\nexamples",
            'layout'=>'portfolio',
            'intent_type'=>'portfolio',
            'blocks'=>[
                ['type'=>'legacy_layout','title'=>'Current Portfolio Layout','content'=>'','template_id'=>0,'source'=>'portfolio']
            ],
        ],
        [
            'post_title'=>'Our Services',
            'post_excerpt'=>'Website development, digital marketing, SEO/AEO, analytics, ecommerce, creative technology, and practical AI systems.',
            'intro_phrase'=>'We help companies with websites, marketing, data, automation, AI, ecommerce, SEO, and the systems that connect them.',
            'menu_order'=>30,
            'keywords'=>"services\nour services\nhelp my company\nux\nweb development\nseo\nai\nanalytics",
            'layout'=>'services',
            'intent_type'=>'services',
            'blocks'=>[
                ['type'=>'legacy_layout','title'=>'Current Services Layout','content'=>'','template_id'=>0,'source'=>'services']
            ],
        ],
    ];

    $defaults[] = [
        'post_title'=>'About Field Theory',
        'post_excerpt'=>'Field Theory Lab is a creative technology agency based in Albuquerque, New Mexico.',
        'intro_phrase'=>'Field Theory Lab is a creative technology agency based in Albuquerque, New Mexico.',
        'menu_order'=>40,
        'keywords'=>"about\nabout field theory\ntell me about your company\ncompany\nteam",
        'layout'=>'about',
        'intent_type'=>'about',
        'blocks'=>[
            ['type'=>'legacy_layout','title'=>'Current About Layout','content'=>'','template_id'=>0,'source'=>'about']
        ],
    ];
    $defaults[] = [
        'post_title'=>'Testimonials',
        'post_excerpt'=>'Client feedback, proof points, and stories from teams Field Theory has helped.',
        'intro_phrase'=>'Here is what clients value about working with Field Theory Lab.',
        'menu_order'=>50,
        'keywords'=>"testimonials\nreviews\nclients\nwhat do clients say\nreferences",
        'layout'=>'testimonials',
        'intent_type'=>'general',
        'blocks'=>[
            ['type'=>'legacy_layout','title'=>'Current Testimonials Layout','content'=>'','template_id'=>0,'source'=>'testimonials']
        ],
    ];
    $defaults[] = [
        'post_title'=>'FAQ',
        'post_excerpt'=>'Quick answers to common questions about websites, marketing, analytics, AI, SEO, budgets, timelines, and support.',
        'intro_phrase'=>'Here are quick answers to common questions.',
        'menu_order'=>60,
        'keywords'=>"faq\nquestions\nfrequently asked questions\nwhat budget should i plan for\nhow long does a website take\nhow much\nhow long\nbudget\ntimeline",
        'layout'=>'faq',
        'intent_type'=>'faq',
        'blocks'=>[
            ['type'=>'legacy_layout','title'=>'Current FAQ Layout','content'=>'','template_id'=>0,'source'=>'faq']
        ],
    ];

    foreach($defaults as $item){
        $post_id = wp_insert_post([
            'post_type'=>'ftc_response',
            'post_status'=>'publish',
            'post_title'=>$item['post_title'],
            'post_excerpt'=>$item['post_excerpt'],
            'post_content'=>'',
            'menu_order'=>$item['menu_order'],
        ]);
        if($post_id && !is_wp_error($post_id)){
            update_post_meta($post_id,'_ftc_response_keywords',$item['keywords']);
            update_post_meta($post_id,'_ftc_response_intro_phrase',$item['intro_phrase'] ?? $item['post_excerpt']);
            update_post_meta($post_id,'_ftc_response_followups',"Get Started\nShow me your work!\nOur Services\nRequest a Proposal");
            update_post_meta($post_id,'_ftc_response_status','active');
            update_post_meta($post_id,'_ftc_response_type','legacy');
            update_post_meta($post_id,'_ftc_response_intent_type',$item['intent_type'] ?? 'general');
            update_post_meta($post_id,'_ftc_response_legacy_layout',$item['layout']);
            update_post_meta($post_id,'_ftc_response_content_preview',$item['content_preview'] ?? '');
            update_post_meta($post_id,'_ftc_response_full_content',$item['full_content'] ?? '');
            update_post_meta($post_id,'_ftc_response_preview_template_id',0);
            update_post_meta($post_id,'_ftc_response_full_template_id',0);
            update_post_meta($post_id,'_ftc_response_blocks',wp_json_encode($item['blocks']));
        }
    }

    update_option('ftc_response_posts_seeded', 1);
}
add_action('init','ftc_seed_default_response_posts', 20);

function ftc_ensure_prd_300_response_posts(){
    $faq_keywords = "faq\nquestions\nfrequently asked questions\nwhat budget should i plan for\nhow long does a website take\nhow much\nhow long\nbudget\ntimeline";
    $responses = get_posts([
        'post_type'=>'ftc_response',
        'post_status'=>'any',
        'numberposts'=>-1,
    ]);
    $has_faq = false;
    foreach($responses as $response){
        if(strtolower(trim($response->post_title)) === 'faq'){
            $has_faq = true;
            $current_keywords = (string)get_post_meta($response->ID,'_ftc_response_keywords',true);
            if($current_keywords === '' || preg_match('/(^|\n)(seo|analytics|ai)(\n|$)/i', $current_keywords)){
                update_post_meta($response->ID,'_ftc_response_keywords',$faq_keywords);
            }
        }
        foreach(['_ftc_response_content_preview','_ftc_response_full_content','_ftc_response_preview_template_id','_ftc_response_full_template_id'] as $key){
            if(!metadata_exists('post', $response->ID, $key)){
                update_post_meta($response->ID, $key, strpos($key, 'template') !== false ? 0 : '');
            }
        }
        $followups = (string)get_post_meta($response->ID,'_ftc_response_followups',true);
        if(strpos($followups, 'Hire Our Team') !== false){
            update_post_meta($response->ID,'_ftc_response_followups',str_replace('Hire Our Team','Request a Proposal',$followups));
        }
    }

    if(!$has_faq){
        $post_id = wp_insert_post([
            'post_type'=>'ftc_response',
            'post_status'=>'publish',
            'post_title'=>'FAQ',
            'post_excerpt'=>'Quick answers to common questions about websites, marketing, analytics, AI, SEO, budgets, timelines, and support.',
            'post_content'=>'',
            'menu_order'=>60,
        ]);
        if($post_id && !is_wp_error($post_id)){
            update_post_meta($post_id,'_ftc_response_keywords',$faq_keywords);
            update_post_meta($post_id,'_ftc_response_intro_phrase','Here are quick answers to common questions.');
            update_post_meta($post_id,'_ftc_response_followups',"Get Started\nOur Services\nShow me your work!\nRequest a Proposal");
            update_post_meta($post_id,'_ftc_response_status','active');
            update_post_meta($post_id,'_ftc_response_type','legacy');
            update_post_meta($post_id,'_ftc_response_intent_type','faq');
            update_post_meta($post_id,'_ftc_response_legacy_layout','faq');
            update_post_meta($post_id,'_ftc_response_content_preview','');
            update_post_meta($post_id,'_ftc_response_full_content','');
            update_post_meta($post_id,'_ftc_response_preview_template_id',0);
            update_post_meta($post_id,'_ftc_response_full_template_id',0);
            update_post_meta($post_id,'_ftc_response_blocks',wp_json_encode([
                ['type'=>'legacy_layout','title'=>'Current FAQ Layout','content'=>'','template_id'=>0,'source'=>'faq']
            ]));
        }
    }
}
add_action('init','ftc_ensure_prd_300_response_posts', 25);


function ftc_response_admin_columns($columns){
    $new = [];
    foreach($columns as $key=>$label){
        $new[$key] = $label;
        if($key === 'title'){
            $new['ftc_response_type'] = 'Response Type';
            $new['ftc_rendering_mode'] = 'Rendering';
            $new['ftc_keywords'] = 'Trigger Prompts';
            $new['ftc_elementor'] = 'Elementor';
        }
    }
    return $new;
}
add_filter('manage_ftc_response_posts_columns','ftc_response_admin_columns');

function ftc_response_admin_column_content($column, $post_id){
    if($column === 'ftc_response_type'){
        $types = ftc_response_intent_types();
        $type = get_post_meta($post_id,'_ftc_response_intent_type',true) ?: 'general';
        echo esc_html($types[$type] ?? $type);
    }
    if($column === 'ftc_rendering_mode'){
        echo esc_html(get_post_meta($post_id,'_ftc_response_type',true) ?: 'legacy');
    }
    if($column === 'ftc_keywords'){
        $keywords = get_post_meta($post_id,'_ftc_response_keywords',true);
        echo esc_html(wp_trim_words(str_replace(["\r","\n"], ', ', $keywords), 12));
    }
    if($column === 'ftc_elementor'){
        $url = ftc_response_elementor_edit_url($post_id);
        if($url) echo '<a href="'.esc_url($url).'">Edit with Elementor</a>';
    }
}
add_action('manage_ftc_response_posts_custom_column','ftc_response_admin_column_content',10,2);

function ftc_response_row_actions($actions, $post){
    if($post->post_type !== 'ftc_response') return $actions;
    $url = ftc_response_elementor_edit_url($post->ID);
    if($url) $actions['ftc_elementor'] = '<a href="'.esc_url($url).'">Edit with Elementor</a>';
    return $actions;
}
add_filter('post_row_actions','ftc_response_row_actions',10,2);
