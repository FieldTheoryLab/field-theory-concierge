<?php
if (!defined('ABSPATH')) exit;

function ftc_detect_intent($term){
    $q = strtolower($term);
    $map = [
        'portfolio' => ['portfolio','work','projects','case studies','examples','built','websites you','show me your work','see your work'],
        'contact' => ['contact','contact us','call','email','talk','meeting','hire','work with','get started','discovery','schedule','book'],
        'websites' => ['website','web design','web development','wordpress','drupal','ux','user experience'],
        'analytics' => ['analytics','data','dashboard','ga4','google analytics','reporting','looker','measurement'],
        'ai' => ['ai','automation','chatgpt','artificial intelligence','agents','workflow'],
        'marketing' => ['marketing','seo','search','visibility','campaign','content','ads'],
        'services' => ['services','help','do you do','what do you do','offer'],
        'about' => ['about','company','who are you','field theory','tell me about'],
    ];
    foreach ($map as $intent => $needles){
        foreach ($needles as $needle){ if (strpos($q, $needle) !== false) return $intent; }
    }
    return 'fallback';
}

function ftc_ajax_answer(){
    check_ajax_referer('ftc_nonce', 'nonce');
    $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
    $intent = ftc_detect_intent($term);
    $responses = ftc_get_responses();
    $response = $responses[$intent] ?? $responses['fallback'];
    $settings = ftc_get_settings();

    ob_start();
    ?>
    <div class="ftc-answer-heading"><?php echo esc_html($response['title']); ?></div>
    <div class="ftc-answer-body"><?php echo wp_kses_post($response['html']); ?></div>
    <?php
    if (($response['layout'] ?? '') === 'portfolio') ftc_render_portfolio_masonry();
    if (($response['layout'] ?? '') === 'services') ftc_render_services_panel();
    if (($response['layout'] ?? '') === 'contact') ftc_render_contact_panel($settings);
    ftc_render_followups($response['followups'] ?? []);
    $html = ob_get_clean();

    wp_send_json_success(['intent'=>$intent, 'html'=>$html]);
}
add_action('wp_ajax_ftc_answer', 'ftc_ajax_answer');
add_action('wp_ajax_nopriv_ftc_answer', 'ftc_ajax_answer');

function ftc_ajax_menu(){
    check_ajax_referer('ftc_nonce', 'nonce');
    $items = [
        'Show me your work!' => 'Show me your work',
        'What services do you offer?' => 'What services do you offer?',
        'Tell me about Field Theory' => 'Tell me about Field Theory',
        'Help me understand my data' => 'Help me understand my website and marketing data',
        'Can you help with AI automation?' => 'Can you help us with AI automation?',
        'How can I work with Field Theory?' => 'How can I work with Field Theory?',
    ];
    ob_start();
    echo '<div class="ftc-explore-list">';
    foreach ($items as $label=>$prompt){
        echo '<button type="button" class="ftc-menu-prompt" data-prompt="' . esc_attr($prompt) . '"><span>' . esc_html($label) . '</span><small>' . esc_html($prompt) . '</small></button>';
    }
    echo '</div>';
    $menu = wp_nav_menu(['theme_location'=>'primary','container'=>false,'echo'=>false,'fallback_cb'=>false]);
    if ($menu) echo '<div class="ftc-wp-menu"><div class="ftc-menu-subtitle">Site Menu</div>' . $menu . '</div>';
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_menu', 'ftc_ajax_menu');
add_action('wp_ajax_nopriv_ftc_menu', 'ftc_ajax_menu');

function ftc_render_portfolio_masonry(){
    $items = [];
    $query = new WP_Query(['post_type'=>'ftc_portfolio','posts_per_page'=>12,'meta_key'=>'_ftc_featured','meta_value'=>'1']);
    if ($query->have_posts()){
        while ($query->have_posts()){ $query->the_post();
            $items[] = [
                'title'=>get_the_title(),
                'description'=>get_the_excerpt() ?: wp_trim_words(wp_strip_all_tags(get_the_content()), 22),
                'image'=>get_the_post_thumbnail_url(get_the_ID(), 'large'),
                'industry'=>get_post_meta(get_the_ID(), '_ftc_industry', true),
                'url'=>get_post_meta(get_the_ID(), '_ftc_project_url', true) ?: get_permalink(),
            ];
        }
        wp_reset_postdata();
    }
    if (!$items) $items = ftc_get_demo_portfolio();
    echo '<div class="ftc-section-label">Featured Work</div><div class="ftc-masonry">';
    foreach ($items as $i=>$item){
        echo '<article class="ftc-work-card ftc-work-card-' . esc_attr(($i % 5) + 1) . '">';
        if (!empty($item['image'])) echo '<img src="' . esc_url($item['image']) . '" alt="' . esc_attr($item['title']) . '">';
        echo '<div class="ftc-work-info"><div><span>' . esc_html($item['industry'] ?? 'Project') . '</span><h3>' . esc_html($item['title']) . '</h3><p>' . esc_html($item['description']) . '</p></div>';
        if (!empty($item['url'])) echo '<a href="' . esc_url($item['url']) . '">View Project →</a>';
        echo '</div></article>';
    }
    echo '</div>';
}

function ftc_render_services_panel(){
    $services = [
        ['Web Technology','Websites, UX, WordPress, Drupal, performance, accessibility, integrations, and modern digital infrastructure.'],
        ['Digital Marketing','SEO, content strategy, campaigns, conversion planning, AI visibility, and better customer journeys.'],
        ['Analytics','GA4, dashboards, reporting, tracking plans, KPIs, and data storytelling for smarter decisions.'],
        ['AI Automation','AI assistants, workflow automation, internal knowledge tools, lead support, and practical team adoption.'],
        ['UX & Content','Information architecture, messaging, page strategy, and interfaces that help people understand what to do next.'],
        ['Growth Systems','Connecting website, data, marketing, and automation into an operating system for growth.'],
    ];
    echo '<div class="ftc-section-label">How We Help</div><div class="ftc-service-grid">';
    foreach ($services as $s) echo '<div class="ftc-service-card"><strong>' . esc_html($s[0]) . '</strong><p>' . esc_html($s[1]) . '</p></div>';
    echo '</div>';
}

function ftc_render_contact_panel($settings){
    echo '<div class="ftc-section-label">Get In Touch</div><div class="ftc-contact-grid">';
    if (!empty($settings['contact_email'])) echo '<a class="ftc-contact-card" href="mailto:' . esc_attr($settings['contact_email']) . '"><strong>Email</strong><span>' . esc_html($settings['contact_email']) . '</span></a>';
    if (!empty($settings['contact_phone'])) echo '<a class="ftc-contact-card" href="tel:' . esc_attr($settings['contact_phone']) . '"><strong>Phone</strong><span>' . esc_html($settings['contact_phone']) . '</span></a>';
    if (!empty($settings['calendly_url'])) echo '<a class="ftc-contact-card" href="' . esc_url($settings['calendly_url']) . '"><strong>Schedule</strong><span>Book a discovery call</span></a>';
    if (!empty($settings['contact_url'])) echo '<a class="ftc-contact-card" href="' . esc_url($settings['contact_url']) . '"><strong>Contact Form</strong><span>Send us a message</span></a>';
    echo '</div>';
}

function ftc_render_followups($followups){
    if (!$followups) return;
    echo '<div class="ftc-followups"><div class="ftc-section-label">You might also ask</div><div class="ftc-followup-row">';
    foreach ($followups as $f) echo '<button type="button" class="ftc-followup" data-prompt="' . esc_attr($f) . '">' . esc_html($f) . '</button>';
    echo '</div></div>';
}
