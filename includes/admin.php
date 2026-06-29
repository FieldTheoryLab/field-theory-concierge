<?php
if (!defined('ABSPATH')) exit;

function ftc_admin_menu(){
    add_menu_page('Field Theory Concierge','Field Theory Concierge','manage_options','field-theory-concierge','ftc_admin_page','dashicons-format-chat',58);
    add_submenu_page('field-theory-concierge','Dashboard','Dashboard','manage_options','field-theory-concierge','ftc_admin_page');
    add_submenu_page('field-theory-concierge','Contact & Form Settings','Contact & Form Settings','manage_options','ftc-contact','ftc_contact_page');
}
add_action('admin_menu','ftc_admin_menu');

function ftc_admin_style(){ echo '<style>.ftc-admin-wrap{max-width:1180px}.ftc-admin-hero{background:#101010;color:#fff;border-radius:14px;padding:24px;margin:18px 0}.ftc-admin-hero h1{color:#fff;margin:0 0 8px}.ftc-admin-hero p{font-size:15px;max-width:780px}.ftc-admin-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:22px;margin:18px 0}.ftc-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.ftc-admin-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:18px 0}.ftc-admin-action-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;display:flex;flex-direction:column;gap:12px;min-height:160px}.ftc-admin-action-card h2{margin:0;font-size:18px}.ftc-admin-action-card p{margin:0;color:#646970}.ftc-admin-action-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:auto}.ftc-core-page-table{width:100%;border-collapse:collapse}.ftc-core-page-table th,.ftc-core-page-table td{border-bottom:1px solid #e5e5e5;padding:12px;text-align:left;vertical-align:middle}.ftc-core-page-table code{font-size:12px}.ftc-admin-card input,.ftc-admin-card textarea{width:100%}.ftc-admin-card textarea{min-height:180px;font-family:ui-monospace,Menlo,monospace}.ftc-help{color:#646970}.ftc-response-block{border-top:1px solid #eee;padding-top:18px;margin-top:18px}@media(max-width:1100px){.ftc-admin-cards{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:900px){.ftc-admin-grid,.ftc-admin-cards{grid-template-columns:1fr}}</style>'; }
add_action('admin_head','ftc_admin_style');

function ftc_admin_card($title,$desc,$manage_url,$add_url=''){
    echo '<article class="ftc-admin-action-card"><h2>'.esc_html($title).'</h2><p>'.esc_html($desc).'</p><div class="ftc-admin-action-row">';
    echo '<a class="button button-primary" href="'.esc_url($manage_url).'">Manage</a>';
    if($add_url) echo '<a class="button" href="'.esc_url($add_url).'">Add New</a>';
    echo '</div></article>';
}

function ftc_admin_core_page_rows(){
    $rows = [
        ['Get Started','get_started','/get-started/'],
        ['Services','services','/services/'],
        ['Portfolio','portfolio','/portfolio/'],
        ['About','about','/about/'],
        ['Testimonials','testimonials','/testimonials/'],
        ['Request a Proposal','contact','/contact/'],
        ['FAQ','faq','/faq/'],
        ['Privacy Policy','privacy','/privacy/'],
    ];
    echo '<table class="ftc-core-page-table"><thead><tr><th>Public Page</th><th>Route</th><th>Editable Record</th><th>Actions</th></tr></thead><tbody>';
    foreach($rows as $row){
        [$label,$intent,$route] = $row;
        $response = function_exists('ftc_get_response_cpt_by_intent') ? ftc_get_response_cpt_by_intent($intent) : null;
        $edit = $response ? get_edit_post_link($response['id']) : admin_url('post-new.php?post_type=ftc_response');
        $elementor = $response && function_exists('ftc_admin_elementor_link') ? ftc_admin_elementor_link($response['id'], 'Elementor', 'button') : '';
        echo '<tr><td><strong>'.esc_html($label).'</strong></td><td><a href="'.esc_url(home_url($route)).'" target="_blank" rel="noopener"><code>'.esc_html($route).'</code></a></td><td>'.($response ? esc_html($response['title']) : '<em>Missing</em>').'</td><td><a class="button" href="'.esc_url($edit).'">'.($response ? 'Edit' : 'Create').'</a> '.$elementor.'</td></tr>';
    }
    echo '</tbody></table>';
}

function ftc_admin_page(){
    if (isset($_POST['ftc_save_settings']) && check_admin_referer('ftc_save_settings')){
        $settings = ftc_get_settings();
        foreach (['dark_logo','icon_logo','tagline','descriptor','name_prompt','input_placeholder','demo_video_url'] as $key){ $settings[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        update_option('ftc_settings',$settings);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    $s = ftc_get_settings();
    ?>
    <div class="wrap ftc-admin-wrap">
    <div class="ftc-admin-hero"><h1>Field Theory Concierge <small>v<?php echo esc_html(FTC_VERSION); ?></small></h1><p>Edit the concierge like a small site: top-level pages live in Pages & Responses, projects live in Portfolio, services live in Services, quick answers live in FAQs, and reviews live in Testimonials.</p></div>
    <div class="ftc-admin-cards">
      <?php
        ftc_admin_card('Pages & Responses','Edit Get Started, About, Services, Portfolio, Request a Proposal, FAQ, Privacy, and custom chat responses.',admin_url('edit.php?post_type=ftc_response'),admin_url('post-new.php?post_type=ftc_response'));
        ftc_admin_card('Portfolio Projects','Manage project cards, featured images, galleries, project detail pages, and Elementor project templates.',admin_url('edit.php?post_type=ftc_portfolio'),admin_url('post-new.php?post_type=ftc_portfolio'));
        ftc_admin_card('Services','Edit the six service categories, child service/task lists, service artwork, and full service details.',admin_url('edit.php?post_type=ftc_service'),admin_url('post-new.php?post_type=ftc_service'));
        ftc_admin_card('FAQs','Create user-focused quick answers that can be opened from helpful prompts and search.',admin_url('edit.php?post_type=ftc_faq'),admin_url('post-new.php?post_type=ftc_faq'));
        ftc_admin_card('Testimonials','Edit quote cards shown on the Testimonials response and Get Started sequence.',admin_url('edit.php?post_type=ftc_testimonial'),admin_url('post-new.php?post_type=ftc_testimonial'));
        ftc_admin_card('Proposal Requests','Review submitted onboarding quiz leads and contact details.',admin_url('edit.php?post_type=ftc_lead'));
      ?>
    </div>
    <div class="ftc-admin-card"><h2>Core Public Pages</h2><p class="ftc-help">These are the SEO-facing routes. Edit the matching record, or use Elementor when you want a fully designed response body.</p><?php ftc_admin_core_page_rows(); ?></div>
    <div class="ftc-admin-card"><p><strong>Shortcode:</strong> <code>[ft_concierge]</code> or <code>[field_theory_concierge]</code></p><p class="ftc-help">Use this only if you need to place the concierge inside a WordPress page manually. The plugin also renders the public routes directly.</p></div>
    <form method="post"><div class="ftc-admin-card"><h2>Brand & Intro</h2><?php wp_nonce_field('ftc_save_settings'); ?>
    <div class="ftc-admin-grid">
    <?php foreach ([
        'dark_logo'=>'Logo URL','icon_logo'=>'Icon Logo URL','tagline'=>'Tagline','descriptor'=>'Descriptor','name_prompt'=>'Name Prompt','input_placeholder'=>'Chat Input Placeholder','demo_video_url'=>'Intro / Welcome Video URL'
    ] as $key=>$label): ?>
        <p><label><strong><?php echo esc_html($label); ?></strong></label><br><input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($s[$key] ?? ''); ?>"></p>
    <?php endforeach; ?>
    </div><p><button class="button button-primary" name="ftc_save_settings" value="1">Save Settings</button></p></div></form></div>
    <?php
}

function ftc_contact_page(){
    if (isset($_POST['ftc_save_contact']) && check_admin_referer('ftc_save_contact')){
        $settings = ftc_get_settings();
        foreach (['contact_email','contact_phone','recaptcha_site_key','recaptcha_secret_key'] as $key){ $settings[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        $settings['contact_url'] = '';
        $settings['calendly_url'] = '';
        $threshold = (float)($_POST['recaptcha_threshold'] ?? 0.5);
        $settings['recaptcha_threshold'] = (string)max(0, min(1, $threshold));
        update_option('ftc_settings',$settings);
        echo '<div class="updated"><p>Contact saved.</p></div>';
    }
    $s = ftc_get_settings();
    ?>
    <div class="wrap ftc-admin-wrap"><h1>Contact & Form Settings</h1><form method="post"><div class="ftc-admin-card"><?php wp_nonce_field('ftc_save_contact'); ?>
    <p class="ftc-help">These settings feed the Request a Proposal response. The phone number appears as mobile-friendly Call and Text buttons.</p>
    <div class="ftc-admin-grid">
    <?php foreach (['contact_email'=>'Email for proposal requests','contact_phone'=>'Call / Text phone number'] as $key=>$label): ?>
        <p><label><strong><?php echo esc_html($label); ?></strong></label><br><input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($s[$key] ?? ''); ?>"></p>
    <?php endforeach; ?>
        <p><label><strong>Google reCAPTCHA v3 Site Key</strong></label><br><input type="text" name="recaptcha_site_key" value="<?php echo esc_attr($s['recaptcha_site_key'] ?? ''); ?>" class="large-text"><span class="ftc-help"> Public site key from <a href="https://www.google.com/recaptcha/admin/create" target="_blank" rel="noopener">Google reCAPTCHA admin</a>. Choose reCAPTCHA v3 (not Enterprise).</span></p>
        <p><label><strong>Google reCAPTCHA v3 Secret Key</strong></label><br><input type="password" name="recaptcha_secret_key" value="<?php echo esc_attr($s['recaptcha_secret_key'] ?? ''); ?>" autocomplete="new-password" class="large-text"><span class="ftc-help"> Secret key paired with the site key above. Server verification uses Google’s standard siteverify endpoint.</span></p>
        <p><label><strong>reCAPTCHA Score Threshold</strong></label><br><input type="number" step="0.1" min="0" max="1" name="recaptcha_threshold" value="<?php echo esc_attr($s['recaptcha_threshold'] ?? '0.5'); ?>"><span class="ftc-help"> Minimum score (0–1) required to accept Submit Inquiry. 0.5 is a typical starting point; higher values are stricter.</span></p>
    </div><p><button class="button button-primary" name="ftc_save_contact" value="1">Save Contact</button></p></div></form></div>
    <?php
}
