<?php
if (!defined('ABSPATH')) exit;

function ftc_admin_menu(){
    add_menu_page('Field Theory Concierge','Field Theory Concierge','manage_options','field-theory-concierge','ftc_admin_page','dashicons-format-chat',58);
    add_submenu_page('field-theory-concierge','Settings','Settings','manage_options','field-theory-concierge','ftc_admin_page');
    add_submenu_page('field-theory-concierge','Responses','Responses','manage_options','ftc-responses','ftc_responses_page');
    add_submenu_page('field-theory-concierge','Contact','Contact','manage_options','ftc-contact','ftc_contact_page');
}
add_action('admin_menu','ftc_admin_menu');

function ftc_admin_style(){ echo '<style>.ftc-admin-wrap{max-width:1050px}.ftc-admin-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:22px;margin:18px 0}.ftc-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.ftc-admin-card input,.ftc-admin-card textarea{width:100%}.ftc-admin-card textarea{min-height:180px;font-family:ui-monospace,Menlo,monospace}.ftc-help{color:#646970}.ftc-response-block{border-top:1px solid #eee;padding-top:18px;margin-top:18px}@media(max-width:900px){.ftc-admin-grid{grid-template-columns:1fr}}</style>'; }
add_action('admin_head','ftc_admin_style');

function ftc_admin_page(){
    if (isset($_POST['ftc_save_settings']) && check_admin_referer('ftc_save_settings')){
        $settings = ftc_get_settings();
        foreach (['dark_logo','light_logo','icon_logo','background_image','tagline','descriptor','name_prompt','input_placeholder','demo_video_url'] as $key){ $settings[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        update_option('ftc_settings',$settings);
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }
    $s = ftc_get_settings();
    ?>
    <div class="wrap ftc-admin-wrap"><h1>Field Theory Concierge <small>v<?php echo esc_html(FTC_VERSION); ?></small></h1>
    <div class="ftc-admin-card"><p><strong>Shortcode:</strong> <code>[ft_concierge]</code> or <code>[field_theory_concierge]</code></p><p class="ftc-help">Built for Jamie Rushad Gros and Field Theory Lab. Use this dashboard to control the core concierge copy and visual assets.</p></div>
    <form method="post"><div class="ftc-admin-card"><h2>Brand & Intro</h2><?php wp_nonce_field('ftc_save_settings'); ?>
    <div class="ftc-admin-grid">
    <?php foreach ([
        'dark_logo'=>'Dark Mode Logo URL','light_logo'=>'Light Mode Logo URL','icon_logo'=>'Icon Logo URL','background_image'=>'Background Image URL','tagline'=>'Tagline','descriptor'=>'Descriptor','name_prompt'=>'Name Prompt','input_placeholder'=>'Chat Input Placeholder','demo_video_url'=>'Intro / Welcome Video URL'
    ] as $key=>$label): ?>
        <p><label><strong><?php echo esc_html($label); ?></strong></label><br><input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($s[$key] ?? ''); ?>"></p>
    <?php endforeach; ?>
    </div><p><button class="button button-primary" name="ftc_save_settings" value="1">Save Settings</button></p></div></form></div>
    <?php
}

function ftc_contact_page(){
    if (isset($_POST['ftc_save_contact']) && check_admin_referer('ftc_save_contact')){
        $settings = ftc_get_settings();
        foreach (['contact_email','contact_phone','contact_url','calendly_url'] as $key){ $settings[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        update_option('ftc_settings',$settings);
        echo '<div class="updated"><p>Contact saved.</p></div>';
    }
    $s = ftc_get_settings();
    ?>
    <div class="wrap ftc-admin-wrap"><h1>Concierge Contact</h1><form method="post"><div class="ftc-admin-card"><?php wp_nonce_field('ftc_save_contact'); ?>
    <div class="ftc-admin-grid">
    <?php foreach (['contact_email'=>'Email','contact_phone'=>'Phone','contact_url'=>'Contact Page URL','calendly_url'=>'Calendly / Booking URL'] as $key=>$label): ?>
        <p><label><strong><?php echo esc_html($label); ?></strong></label><br><input type="text" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($s[$key] ?? ''); ?>"></p>
    <?php endforeach; ?>
    </div><p><button class="button button-primary" name="ftc_save_contact" value="1">Save Contact</button></p></div></form></div>
    <?php
}

function ftc_responses_page(){
    if (isset($_POST['ftc_save_responses']) && check_admin_referer('ftc_save_responses')){
        $responses = ftc_get_responses();
        foreach ($responses as $key=>$value){
            $responses[$key]['title'] = sanitize_text_field(wp_unslash($_POST['title'][$key] ?? $value['title']));
            $responses[$key]['description'] = sanitize_textarea_field(wp_unslash($_POST['description'][$key] ?? ($value['description'] ?? '')));
            $responses[$key]['html'] = wp_kses_post(wp_unslash($_POST['html'][$key] ?? $value['html']));
            $responses[$key]['layout'] = $value['layout'] ?? 'none';
            $responses[$key]['template_id'] = 0;
            $responses[$key]['response_image'] = $value['response_image'] ?? '';
            $f = sanitize_textarea_field(wp_unslash($_POST['followups'][$key] ?? ''));
            $responses[$key]['followups'] = array_values(array_filter(array_map('trim', explode("\n", $f))));
        }
        update_option('ftc_responses',$responses);
        echo '<div class="updated"><p>Responses saved.</p></div>';
    }
    $responses = ftc_get_responses();
    ?>
    <div class="wrap ftc-admin-wrap"><h1>Concierge Responses</h1><form method="post"><div class="ftc-admin-card"><?php wp_nonce_field('ftc_save_responses'); ?>
    <p class="ftc-help">Manage the common concierge answers. Titles, descriptions, content, images, and follow-up prompts are editable here.</p>
    <?php foreach ($responses as $key=>$response): ?>
        <div class="ftc-response-block"><h2><?php echo esc_html(ucfirst($key)); ?></h2>
        <p><label><strong>Title</strong></label><br><input type="text" name="title[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($response['title']); ?>"></p>
        <p><label><strong>Description</strong></label><br><textarea name="description[<?php echo esc_attr($key); ?>]" style="min-height:70px"><?php echo esc_textarea($response['description'] ?? ''); ?></textarea></p>
        
        <p><label><strong>Editable Response Content</strong> <span class="ftc-help">This is the response content area. You can add HTML or Elementor shortcodes here.</span></label><br><textarea name="html[<?php echo esc_attr($key); ?>]"><?php echo esc_textarea($response['html']); ?></textarea></p>
        <p><label><strong>Follow-ups</strong> <span class="ftc-help">one per line</span></label><br><textarea name="followups[<?php echo esc_attr($key); ?>]" style="min-height:90px"><?php echo esc_textarea(implode("\n", $response['followups'] ?? [])); ?></textarea></p>
        </div>
    <?php endforeach; ?>
    <p><button class="button button-primary" name="ftc_save_responses" value="1">Save Responses</button></p></div></form></div>
    <?php
}
