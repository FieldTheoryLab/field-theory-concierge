<?php
if (!defined('ABSPATH')) exit;

function ftc_register_portfolio_cpt(){
    register_post_type('ftc_portfolio', [
        'labels' => ['name'=>'Portfolio Projects','singular_name'=>'Portfolio Project','add_new_item'=>'Add Portfolio Project','edit_item'=>'Edit Portfolio Project','menu_name'=>'Portfolio Projects'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-portfolio',
        'supports'=>['title','editor','thumbnail','excerpt','custom-fields','elementor'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-work'],
    ]);
    register_taxonomy('ftc_portfolio_tag','ftc_portfolio',['label'=>'Portfolio Tags','hierarchical'=>false,'show_ui'=>true,'show_in_menu'=>true,'rewrite'=>['slug'=>'ft-work-tag']]);

    register_post_type('ftc_service', [
        'labels' => ['name'=>'Services','singular_name'=>'Service','add_new_item'=>'Add Service','edit_item'=>'Edit Service','menu_name'=>'Services'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-admin-tools',
        'supports'=>['title','editor','thumbnail','excerpt','page-attributes','custom-fields','elementor'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-service'],
    ]);
    register_taxonomy('ftc_service_group','ftc_service',['label'=>'Service Groups','hierarchical'=>true,'show_ui'=>true,'show_in_menu'=>true]);

    register_post_type('ftc_lead', [
        'labels'=>['name'=>'Proposal Requests','singular_name'=>'Proposal Request','edit_item'=>'View Proposal Request','menu_name'=>'Proposal Requests'],
        'public'=>false,
        'publicly_queryable'=>false,
        'show_ui'=>true,
        'show_in_menu'=>'field-theory-concierge',
        'menu_icon'=>'dashicons-clipboard',
        'supports'=>['title','custom-fields'],
        'capability_type'=>'post',
        'map_meta_cap'=>true,
    ]);

    ftc_register_faq_cpt();
    ftc_register_testimonial_cpt();
}
add_action('init','ftc_register_portfolio_cpt');

function ftc_register_faq_cpt(){
    register_post_type('ftc_faq', [
        'labels'=>['name'=>'FAQs','singular_name'=>'FAQ','add_new_item'=>'Add FAQ','edit_item'=>'Edit FAQ','menu_name'=>'FAQs'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-editor-help',
        'supports'=>['title','editor','excerpt','thumbnail','page-attributes','custom-fields','elementor'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-faq'],
    ]);
    register_taxonomy('ftc_faq_topic','ftc_faq',['label'=>'FAQ Topics','hierarchical'=>true,'show_ui'=>true,'show_in_menu'=>true,'rewrite'=>['slug'=>'ft-faq-topic']]);
}

function ftc_register_testimonial_cpt(){
    register_post_type('ftc_testimonial', [
        'labels'=>[
            'name'=>'Testimonials',
            'singular_name'=>'Testimonial',
            'add_new_item'=>'Add Testimonial',
            'edit_item'=>'Edit Testimonial',
            'menu_name'=>'Testimonials',
        ],
        'public'=>true,
        'publicly_queryable'=>true,
        'exclude_from_search'=>true,
        'show_ui'=>true,
        'show_in_menu'=>'field-theory-concierge',
        'menu_icon'=>'dashicons-format-quote',
        'supports'=>['title','editor','excerpt','thumbnail','page-attributes','custom-fields','elementor'],
        'show_in_rest'=>true,
        'has_archive'=>false,
        'rewrite'=>['slug'=>'ft-testimonial'],
        'show_in_nav_menus'=>false,
    ]);
}

function ftc_portfolio_meta_boxes(){
    add_meta_box('ftc_portfolio_details','Concierge Portfolio Details','ftc_portfolio_meta_box','ftc_portfolio','normal','high');
    add_meta_box('ftc_service_details','Concierge Service Details','ftc_service_meta_box','ftc_service','normal','high');
    add_meta_box('ftc_testimonial_details','Testimonial Details','ftc_testimonial_meta_box','ftc_testimonial','normal','high');
    add_meta_box('ftc_lead_details','Proposal Request Details','ftc_lead_meta_box','ftc_lead','normal','high');
}
add_action('add_meta_boxes','ftc_portfolio_meta_boxes');

function ftc_portfolio_admin_assets($hook){
    if(!in_array($hook, ['post.php','post-new.php'], true)) return;
    $screen = get_current_screen();
    if(!$screen || $screen->post_type !== 'ftc_portfolio') return;
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts','ftc_portfolio_admin_assets');

function ftc_portfolio_meta_box($post){
    wp_nonce_field('ftc_save_portfolio','ftc_portfolio_nonce');
    $industry=get_post_meta($post->ID,'_ftc_industry',true); $video=get_post_meta($post->ID,'_ftc_video_url',true); $url=get_post_meta($post->ID,'_ftc_project_url',true); $results=get_post_meta($post->ID,'_ftc_results',true); $featured=get_post_meta($post->ID,'_ftc_featured',true); $gallery=get_post_meta($post->ID,'_ftc_gallery_urls',true); $gallery_ids=get_post_meta($post->ID,'_ftc_gallery_ids',true); $template_id=get_post_meta($post->ID,'_ftc_elementor_template_id',true);
    echo '<p><label><strong>Industry</strong></label><br><input type="text" name="ftc_industry" value="'.esc_attr($industry).'" class="widefat"></p>';
    echo '<p><label><strong>Project URL</strong></label><br><input type="url" name="ftc_project_url" value="'.esc_attr($url).'" class="widefat"></p>';
    echo '<p><label><strong>Video URL</strong></label><br><input type="url" name="ftc_video_url" value="'.esc_attr($video).'" class="widefat"></p>';
    echo '<p><label><strong>Elementor Template ID</strong> <span style="color:#666">Optional. If set, this full-width Elementor template replaces the default concierge project detail.</span></label><br><input type="number" name="ftc_elementor_template_id" value="'.esc_attr($template_id).'" class="widefat"></p>';
    echo '<div class="ftc-portfolio-gallery-field"><label><strong>Project Gallery</strong> <span style="color:#666">Choose images from the Media Library. The first selected image is used first in the gallery.</span></label>';
    echo '<input type="hidden" name="ftc_gallery_ids" class="ftc-gallery-ids" value="'.esc_attr($gallery_ids).'">';
    echo '<p><button type="button" class="button ftc-gallery-select">Choose Gallery Images</button> <button type="button" class="button ftc-gallery-clear">Clear Gallery</button></p>';
    echo '<div class="ftc-gallery-preview" style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0 16px;">';
    foreach(array_filter(array_map('absint',explode(',',(string)$gallery_ids))) as $attachment_id){
        $thumb = wp_get_attachment_image_url($attachment_id,'thumbnail');
        if($thumb) echo '<span data-id="'.esc_attr($attachment_id).'" style="display:block;width:86px;height:64px;border:1px solid #ccd0d4;background:#f6f7f7;border-radius:4px;overflow:hidden;"><img src="'.esc_url($thumb).'" alt="" style="width:100%;height:100%;object-fit:cover;"></span>';
    }
    echo '</div></div>';
    echo '<p><label><strong>Fallback Gallery URLs</strong> <span style="color:#666">Advanced fallback only. One image URL per line.</span></label><br><textarea name="ftc_gallery_urls" class="widefat" rows="4">'.esc_textarea($gallery).'</textarea></p>';
    echo '<p><label><strong>Results / Impact</strong></label><br><textarea name="ftc_results" class="widefat" rows="4">'.esc_textarea($results).'</textarea></p>';
    echo '<p><label><input type="checkbox" name="ftc_featured" value="1" '.checked($featured,'1',false).'> Featured in Concierge</label></p>';
    ?>
    <script>
    jQuery(function($){
      $('.ftc-gallery-select').on('click', function(e){
        e.preventDefault();
        const wrap = $(this).closest('.ftc-portfolio-gallery-field');
        const input = wrap.find('.ftc-gallery-ids');
        const preview = wrap.find('.ftc-gallery-preview');
        const frame = wp.media({
          title: 'Choose Project Gallery Images',
          button: { text: 'Use selected images' },
          multiple: true,
          library: { type: 'image' }
        });
        frame.on('select', function(){
          const selection = frame.state().get('selection').toArray();
          const ids = selection.map(function(attachment){ return attachment.id; });
          input.val(ids.join(','));
          preview.empty();
          selection.forEach(function(attachment){
            const data = attachment.toJSON();
            const url = (data.sizes && data.sizes.thumbnail) ? data.sizes.thumbnail.url : data.url;
            preview.append('<span data-id="'+attachment.id+'" style="display:block;width:86px;height:64px;border:1px solid #ccd0d4;background:#f6f7f7;border-radius:4px;overflow:hidden;"><img src="'+url+'" alt="" style="width:100%;height:100%;object-fit:cover;"></span>');
          });
        });
        frame.open();
      });
      $('.ftc-gallery-clear').on('click', function(e){
        e.preventDefault();
        const wrap = $(this).closest('.ftc-portfolio-gallery-field');
        wrap.find('.ftc-gallery-ids').val('');
        wrap.find('.ftc-gallery-preview').empty();
      });
    });
    </script>
    <?php
}
function ftc_service_meta_box($post){
    wp_nonce_field('ftc_save_service','ftc_service_nonce');
    $eyebrow=get_post_meta($post->ID,'_ftc_service_eyebrow',true); $image=get_post_meta($post->ID,'_ftc_service_image',true); $tasks=get_post_meta($post->ID,'_ftc_service_tasks',true); $featured=get_post_meta($post->ID,'_ftc_featured',true); $template_id=get_post_meta($post->ID,'_ftc_elementor_template_id',true);
    echo '<p><label><strong>Small Label / Icon Text</strong></label><br><input type="text" name="ftc_service_eyebrow" value="'.esc_attr($eyebrow).'" class="widefat" placeholder="API, SEO, DATA, AI"></p>';
    echo '<p><label><strong>Service Image URL</strong> <span style="color:#666">Fallback only. Prefer the normal Featured Image box for service artwork.</span></label><br><input type="url" name="ftc_service_image" value="'.esc_attr($image).'" class="widefat"></p>';
    echo '<p><label><strong>Elementor Template ID</strong> <span style="color:#666">Optional. If set, this full-width Elementor template replaces the default concierge service detail.</span></label><br><input type="number" name="ftc_elementor_template_id" value="'.esc_attr($template_id).'" class="widefat"></p>';
    echo '<p><label><strong>Child Categories / Tasks</strong> <span style="color:#666">One per line</span></label><br><textarea name="ftc_service_tasks" class="widefat" rows="8">'.esc_textarea($tasks).'</textarea></p>';
    echo '<p><label><input type="checkbox" name="ftc_featured" value="1" '.checked($featured,'1',false).'> Featured in Concierge</label></p>';
}

function ftc_testimonial_meta_box($post){
    wp_nonce_field('ftc_save_testimonial','ftc_testimonial_nonce');
    $role = get_post_meta($post->ID,'_ftc_testimonial_role',true);
    $company = get_post_meta($post->ID,'_ftc_testimonial_company',true);
    $featured = get_post_meta($post->ID,'_ftc_featured',true);
    echo '<p class="description">Use the title for the person or client label, and the main editor for the quote. Keep the quote short enough to scan in the chat response.</p>';
    echo '<p><label><strong>Role / Label</strong></label><br><input type="text" name="ftc_testimonial_role" value="'.esc_attr($role).'" class="widefat" placeholder="Healthcare client, Marketing director, Nonprofit partner"></p>';
    echo '<p><label><strong>Company / Organization</strong></label><br><input type="text" name="ftc_testimonial_company" value="'.esc_attr($company).'" class="widefat"></p>';
    echo '<p><label><input type="checkbox" name="ftc_featured" value="1" '.checked($featured,'1',false).'> Show in Concierge testimonials</label></p>';
}

function ftc_lead_meta_value($post_id, $key){
    $value = get_post_meta($post_id, $key, true);
    return is_array($value) ? implode(', ', $value) : (string)$value;
}

function ftc_lead_phone_href($phone, $scheme='tel'){
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if($digits === '') return '';
    if(strlen($digits) === 10) $digits = '1'.$digits;
    return $scheme . ':+' . $digits;
}

function ftc_lead_detail_row($label, $value){
    if(is_array($value)) $value = implode(', ', array_filter($value));
    $value = trim((string)$value);
    if($value === '') $value = 'Not provided';
    echo '<tr><th scope="row">'.esc_html($label).'</th><td>'.nl2br(esc_html($value)).'</td></tr>';
}

function ftc_lead_meta_box($post){
    $services = get_post_meta($post->ID,'_ftc_lead_services',true);
    if(!is_array($services)) $services = array_filter(array_map('trim', explode(',', (string)$services)));
    $name = ftc_lead_meta_value($post->ID,'_ftc_lead_name');
    $email = ftc_lead_meta_value($post->ID,'_ftc_lead_email');
    $phone = ftc_lead_meta_value($post->ID,'_ftc_lead_phone');
    $company = ftc_lead_meta_value($post->ID,'_ftc_lead_company');
    $website = ftc_lead_meta_value($post->ID,'_ftc_lead_website');
    $contact_method = ftc_lead_meta_value($post->ID,'_ftc_lead_contact_method');
    $priority = ftc_lead_meta_value($post->ID,'_ftc_lead_priority');
    $score = ftc_lead_meta_value($post->ID,'_ftc_lead_score');
    $timestamp = ftc_lead_meta_value($post->ID,'_ftc_lead_timestamp');
    echo '<style>.ftc-lead-actions{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 18px}.ftc-lead-summary{border-collapse:collapse;width:100%;max-width:960px}.ftc-lead-summary th{width:190px;text-align:left;color:#50575e}.ftc-lead-summary th,.ftc-lead-summary td{padding:10px 12px;border-bottom:1px solid #dcdcde;vertical-align:top}.ftc-lead-priority{display:inline-block;border-radius:999px;padding:4px 10px;background:#f0f6fc;color:#0969da;font-weight:700}</style>';
    echo '<div class="ftc-lead-actions">';
    if(is_email($email)) echo '<a class="button button-primary" href="mailto:'.esc_attr($email).'?subject='.rawurlencode('Re: Field Theory proposal request').'">Email '.esc_html($name ?: $email).'</a>';
    $tel = ftc_lead_phone_href($phone, 'tel');
    $sms = ftc_lead_phone_href($phone, 'sms');
    if($tel) echo '<a class="button" href="'.esc_attr($tel).'">Call</a>';
    if($sms) echo '<a class="button" href="'.esc_attr($sms).'">Text</a>';
    if($website) echo '<a class="button" href="'.esc_url($website).'" target="_blank" rel="noopener">Open Website</a>';
    echo '</div>';
    echo '<p><span class="ftc-lead-priority">'.esc_html($priority ?: 'Unscored').($score !== '' ? ' / '.esc_html($score) : '').'</span></p>';
    echo '<table class="ftc-lead-summary"><tbody>';
    ftc_lead_detail_row('Submitted', $timestamp);
    ftc_lead_detail_row('Name', $name);
    ftc_lead_detail_row('Email', $email);
    ftc_lead_detail_row('Phone', $phone);
    ftc_lead_detail_row('Preferred Contact', $contact_method);
    ftc_lead_detail_row('Company', $company);
    ftc_lead_detail_row('Website', $website);
    ftc_lead_detail_row('Services', $services);
    ftc_lead_detail_row('Timeline', ftc_lead_meta_value($post->ID,'_ftc_lead_timeline'));
    ftc_lead_detail_row('Budget', ftc_lead_meta_value($post->ID,'_ftc_lead_budget'));
    ftc_lead_detail_row('Organization Type', ftc_lead_meta_value($post->ID,'_ftc_lead_org_type'));
    ftc_lead_detail_row('Challenge', ftc_lead_meta_value($post->ID,'_ftc_lead_challenge'));
    ftc_lead_detail_row('Notes', ftc_lead_meta_value($post->ID,'_ftc_lead_notes'));
    echo '</tbody></table>';
}

function ftc_save_portfolio_meta($post_id){
    if (!isset($_POST['ftc_portfolio_nonce']) || !wp_verify_nonce($_POST['ftc_portfolio_nonce'],'ftc_save_portfolio')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_ftc_industry',sanitize_text_field(wp_unslash($_POST['ftc_industry'] ?? '')));
    update_post_meta($post_id,'_ftc_video_url',esc_url_raw(wp_unslash($_POST['ftc_video_url'] ?? '')));
    update_post_meta($post_id,'_ftc_project_url',esc_url_raw(wp_unslash($_POST['ftc_project_url'] ?? '')));
    update_post_meta($post_id,'_ftc_results',sanitize_textarea_field(wp_unslash($_POST['ftc_results'] ?? '')));
    update_post_meta($post_id,'_ftc_gallery_urls',sanitize_textarea_field(wp_unslash($_POST['ftc_gallery_urls'] ?? '')));
    $gallery_ids = array_filter(array_map('absint',explode(',',sanitize_text_field(wp_unslash($_POST['ftc_gallery_ids'] ?? '')))));
    update_post_meta($post_id,'_ftc_gallery_ids',implode(',',array_unique($gallery_ids)));
    update_post_meta($post_id,'_ftc_gallery_manual','1');
    update_post_meta($post_id,'_ftc_featured',isset($_POST['ftc_featured'])?'1':'0');
    update_post_meta($post_id,'_ftc_elementor_template_id',absint($_POST['ftc_elementor_template_id']??0));
}
add_action('save_post_ftc_portfolio','ftc_save_portfolio_meta');
function ftc_save_service_meta($post_id){
    if (!isset($_POST['ftc_service_nonce']) || !wp_verify_nonce($_POST['ftc_service_nonce'],'ftc_save_service')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_ftc_service_eyebrow',sanitize_text_field(wp_unslash($_POST['ftc_service_eyebrow'] ?? '')));
    update_post_meta($post_id,'_ftc_service_image',esc_url_raw(wp_unslash($_POST['ftc_service_image'] ?? '')));
    update_post_meta($post_id,'_ftc_service_tasks',sanitize_textarea_field(wp_unslash($_POST['ftc_service_tasks'] ?? '')));
    update_post_meta($post_id,'_ftc_featured',isset($_POST['ftc_featured'])?'1':'0');
    update_post_meta($post_id,'_ftc_elementor_template_id',absint($_POST['ftc_elementor_template_id']??0));
}
add_action('save_post_ftc_service','ftc_save_service_meta');

function ftc_save_testimonial_meta($post_id){
    if (!isset($_POST['ftc_testimonial_nonce']) || !wp_verify_nonce($_POST['ftc_testimonial_nonce'],'ftc_save_testimonial')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_ftc_testimonial_role',sanitize_text_field(wp_unslash($_POST['ftc_testimonial_role'] ?? '')));
    update_post_meta($post_id,'_ftc_testimonial_company',sanitize_text_field(wp_unslash($_POST['ftc_testimonial_company'] ?? '')));
    update_post_meta($post_id,'_ftc_featured',isset($_POST['ftc_featured'])?'1':'0');
}
add_action('save_post_ftc_testimonial','ftc_save_testimonial_meta');

function ftc_lead_admin_columns($columns){
    return [
        'cb'=>$columns['cb'] ?? '',
        'title'=>'Proposal Request',
        'ftc_lead_contact'=>'Contact',
        'ftc_lead_company'=>'Company',
        'ftc_lead_services'=>'Services',
        'ftc_lead_timeline'=>'Timeline',
        'ftc_lead_priority'=>'Priority',
        'date'=>'Date',
    ];
}
add_filter('manage_ftc_lead_posts_columns','ftc_lead_admin_columns');

function ftc_lead_admin_column_content($column, $post_id){
    if($column === 'ftc_lead_contact'){
        $name = ftc_lead_meta_value($post_id,'_ftc_lead_name');
        $email = ftc_lead_meta_value($post_id,'_ftc_lead_email');
        $phone = ftc_lead_meta_value($post_id,'_ftc_lead_phone');
        if($name) echo '<strong>'.esc_html($name).'</strong><br>';
        if(is_email($email)) echo '<a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a><br>';
        if($phone) echo esc_html($phone);
    }
    if($column === 'ftc_lead_company'){
        $company = ftc_lead_meta_value($post_id,'_ftc_lead_company');
        $website = ftc_lead_meta_value($post_id,'_ftc_lead_website');
        echo $company ? esc_html($company) : '<span style="color:#777">Not provided</span>';
        if($website) echo '<br><a href="'.esc_url($website).'" target="_blank" rel="noopener">Website</a>';
    }
    if($column === 'ftc_lead_services'){
        $services = get_post_meta($post_id,'_ftc_lead_services',true);
        if(is_array($services)) echo esc_html(implode(', ', $services));
        else echo esc_html((string)$services);
    }
    if($column === 'ftc_lead_timeline'){
        echo esc_html(ftc_lead_meta_value($post_id,'_ftc_lead_timeline'));
    }
    if($column === 'ftc_lead_priority'){
        $priority = ftc_lead_meta_value($post_id,'_ftc_lead_priority');
        $score = ftc_lead_meta_value($post_id,'_ftc_lead_score');
        echo esc_html($priority ?: 'Unscored');
        if($score !== '') echo ' / '.esc_html($score);
    }
}
add_action('manage_ftc_lead_posts_custom_column','ftc_lead_admin_column_content',10,2);

function ftc_lead_row_actions($actions, $post){
    if($post->post_type !== 'ftc_lead') return $actions;
    $email = ftc_lead_meta_value($post->ID,'_ftc_lead_email');
    $phone = ftc_lead_meta_value($post->ID,'_ftc_lead_phone');
    if(is_email($email)) $actions['ftc_email'] = '<a href="mailto:'.esc_attr($email).'">Email</a>';
    $tel = ftc_lead_phone_href($phone, 'tel');
    $sms = ftc_lead_phone_href($phone, 'sms');
    if($tel) $actions['ftc_call'] = '<a href="'.esc_attr($tel).'">Call</a>';
    if($sms) $actions['ftc_text'] = '<a href="'.esc_attr($sms).'">Text</a>';
    return $actions;
}
add_filter('post_row_actions','ftc_lead_row_actions',10,2);

function ftc_seed_default_testimonials_2828(){
    if(get_option('ftc_testimonials_seeded_2828')) return;
    $items = [
        [
            'title'=>'Healthcare client',
            'quote'=>'A clearer website, cleaner message, and a team that understood the business problem before touching the design.',
            'role'=>'Healthcare client',
            'order'=>10,
        ],
        [
            'title'=>'Nonprofit partner',
            'quote'=>'Field Theory brought strategy, design, development, and analytics together without making the process feel heavy.',
            'role'=>'Nonprofit partner',
            'order'=>20,
        ],
        [
            'title'=>'Marketing director',
            'quote'=>'The reporting finally made sense. We could see what was working and what to do next.',
            'role'=>'Marketing director',
            'order'=>30,
        ],
    ];
    foreach($items as $item){
        $existing = get_page_by_title($item['title'], OBJECT, 'ftc_testimonial');
        if($existing){
            $post_id = $existing->ID;
        } else {
            $post_id = wp_insert_post([
                'post_type'=>'ftc_testimonial',
                'post_status'=>'publish',
                'post_title'=>$item['title'],
                'post_content'=>'<p>'.esc_html($item['quote']).'</p>',
                'menu_order'=>$item['order'],
            ]);
        }
        if(!$post_id || is_wp_error($post_id)) continue;
        if(!get_post_meta($post_id,'_ftc_testimonial_role',true)) update_post_meta($post_id,'_ftc_testimonial_role',$item['role']);
        if(!metadata_exists('post',$post_id,'_ftc_featured')) update_post_meta($post_id,'_ftc_featured','1');
    }
    update_option('ftc_testimonials_seeded_2828',1);
}
add_action('init','ftc_seed_default_testimonials_2828',63);
add_action('admin_init','ftc_seed_default_testimonials_2828',63);

function ftc_seed_default_services(){
    if (get_option('ftc_services_seeded_270')) return;
    $services = [
        ['Website Development & Core Tech','API','Websites, UX, WordPress, Drupal, integrations, performance, accessibility, and modern digital infrastructure.','https://placehold.co/960x540/242424/ffd94d?text=Website+Development',['Website User Experience Design','WordPress Development','Drupal Development','API Integrations','Accessibility & Performance','Technical Planning','Content Management Systems','Landing Pages']],
        ['Digital Marketing & Growth Strategy','GROWTH','SEO, content strategy, campaigns, conversion planning, AI visibility, and better customer journeys.','https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',['Campaign Strategy','Content Strategy','Conversion Planning','Local Search','Paid Media Planning','Marketing Operations','Email Journeys','Reporting']],
        ['Search & Discovery Optimization (SEO / AEO)','SEO','Search visibility, answer engine optimization, content architecture, technical SEO, and useful search-ready websites.',FTC_URL.'assets/images/service-seo.svg',['Technical SEO','AI Search Visibility','Structured Data','Content Architecture','Local SEO','Editorial Planning','Search Audits','Optimization Roadmaps']],
        ['Ecommerce & Conversion Rate Optimization (CRO)','CRO','Ecommerce, product journeys, checkout experience, testing, conversion funnels, and measurable growth improvements.',FTC_URL.'assets/images/service-cro.svg',['Shop UX','Product Pages','Checkout Optimization','Conversion Funnels','A/B Test Planning','Memberships','Subscriptions','Analytics Events']],
        ['Technology, Innovation and A.I.','AI','Practical AI workflows, internal assistants, automation, and experimental digital tools.','https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',['AI Assistants','Workflow Automation','Internal Knowledge Tools','Lead Support','Prototype Development','Creative Experiments','Prompt Systems','Team Adoption']],
    ];
    $i=0; foreach($services as $svc){
        if (get_page_by_title($svc[0], OBJECT, 'ftc_service')) continue;
        $id=wp_insert_post(['post_type'=>'ftc_service','post_status'=>'publish','post_title'=>$svc[0],'post_excerpt'=>$svc[2],'post_content'=>'<p>'.esc_html($svc[2]).'</p>','menu_order'=>$i]);
        if(!is_wp_error($id)&&$id){ update_post_meta($id,'_ftc_service_eyebrow',$svc[1]); update_post_meta($id,'_ftc_service_image',$svc[3]); update_post_meta($id,'_ftc_service_tasks',implode("\n",$svc[4])); update_post_meta($id,'_ftc_featured','1'); }
        $i++;
    }
    update_option('ftc_services_seeded_270',1);
}
add_action('admin_init','ftc_seed_default_services');

function ftc_refresh_service_art_2620(){
    if (get_option('ftc_service_art_refreshed_2620')) return;
    $map = [
        'website-development-core-tech' => 'https://placehold.co/960x540/242424/ffd94d?text=Website+Development',
        'digital-marketing-growth-strategy' => 'https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',
        'search-discovery-optimization-seo-aeo' => FTC_URL.'assets/images/service-seo.svg',
        'ecommerce-conversion-rate-optimization-cro' => FTC_URL.'assets/images/service-cro.svg',
        'data-analysis-visualization' => 'https://placehold.co/960x540/242424/ffd94d?text=Data+%26+Analytics',
        'creative-technology-innovation' => 'https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',
    ];
    foreach($map as $slug=>$url){
        $posts = get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){
            $current = get_post_meta($posts[0]->ID,'_ftc_service_image',true);
            if(!$current || strpos($current,'service-red.svg') !== false) update_post_meta($posts[0]->ID,'_ftc_service_image',$url);
        }
    }
    update_option('ftc_service_art_refreshed_2620',1);
}
add_action('admin_init','ftc_refresh_service_art_2620');


function ftc_default_faqs(){ return [
    ['Websites','What does Field Theory provide for website development?','Field Theory plans, designs, builds, hosts, maintains, and improves websites across WordPress, Drupal, custom applications, integrations, accessibility, performance, security, and content workflows.'],
    ['Marketing','What does Field Theory provide for digital marketing?','Field Theory connects strategy, content, campaigns, paid media, conversion planning, customer journeys, reporting, and ongoing optimization so marketing activity is tied to measurable business outcomes.'],
    ['SEO / AEO','How does Field Theory improve search and AI visibility?','Field Theory works on technical SEO, content architecture, schema, local visibility, answer engine optimization, AI citation readiness, and structured content that helps people and AI systems understand your organization.'],
    ['Analytics','How does Field Theory help with analytics and reporting?','Field Theory sets up meaningful tracking, GA4, dashboards, campaign reporting, attribution, KPI planning, executive summaries, and decision-ready views of website and marketing performance.'],
    ['AI','What does Field Theory build with AI and automation?','Field Theory builds practical AI assistants, workflow automations, internal knowledge tools, lead support flows, reporting automation, prototypes, and interactive digital tools that solve specific operational or customer experience problems.'],
    ['Ecommerce','How does Field Theory help with ecommerce and conversion?','Field Theory improves ecommerce strategy, product journeys, checkout flows, subscriptions, conversion funnels, testing plans, analytics events, retention, and revenue-focused customer experience.'],
    ['Working Together','How does a proposal request work?','Start with the Request a Proposal quiz. Field Theory reviews the services, goals, timing, budget, and context you submit, then follows up with a practical recommendation for the next step.'],
    ['Websites','Can Field Theory improve our current website?','Yes. Field Theory can audit the current experience, clarify messaging, improve UX, fix technical issues, strengthen search visibility, add analytics, modernize content workflows, and plan phased improvements around your goals.'],
]; }
function ftc_more_default_faqs(){ return []; }
function ftc_seed_default_faqs(){
    if (get_option('ftc_default_faqs_seeded_270')) return;
    foreach(ftc_default_faqs() as $i=>$faq){ [$topic,$q,$a]=$faq; if(get_page_by_title($q,OBJECT,'ftc_faq')) continue; $id=wp_insert_post(['post_type'=>'ftc_faq','post_status'=>'publish','post_title'=>$q,'post_content'=>'<p>'.esc_html($a).'</p>','menu_order'=>$i]); if($id&&!is_wp_error($id)) wp_set_object_terms($id,$topic,'ftc_faq_topic'); }
    update_option('ftc_default_faqs_seeded_270',1);
}
add_action('admin_init','ftc_seed_default_faqs');

function ftc_sync_faq_catalog_2811(){
    if (get_option('ftc_faq_catalog_synced_2811')) return;
    $legacy_titles = [
        'What does Field Theory provide for website development?' => ['What makes a website effective?'],
        'What does Field Theory provide for digital marketing?' => ['How do I know if my marketing is working?'],
        'How does Field Theory improve search and AI visibility?' => ['What is AI visibility?'],
        'How does Field Theory help with analytics and reporting?' => ['What should we track?'],
        'What does Field Theory build with AI and automation?' => ['How can AI help my business?'],
        'How does a proposal request work?' => ['How can we work with Field Theory?'],
    ];
    foreach(ftc_default_faqs() as $i=>$faq){
        [$topic,$question,$answer] = $faq;
        $post = get_page_by_title($question, OBJECT, 'ftc_faq');
        if(!$post && isset($legacy_titles[$question])){
            foreach($legacy_titles[$question] as $legacy_title){
                $post = get_page_by_title($legacy_title, OBJECT, 'ftc_faq');
                if($post) break;
            }
        }
        $postarr = [
            'post_type'=>'ftc_faq',
            'post_status'=>'publish',
            'post_title'=>$question,
            'post_content'=>'<p>'.esc_html($answer).'</p>',
            'menu_order'=>$i,
        ];
        if($post) $postarr['ID'] = $post->ID;
        $id = $post ? wp_update_post($postarr) : wp_insert_post($postarr);
        if($id && !is_wp_error($id)) wp_set_object_terms($id, $topic, 'ftc_faq_topic');
    }
    update_option('ftc_faq_catalog_synced_2811',1);
}
add_action('init','ftc_sync_faq_catalog_2811',24);
add_action('admin_init','ftc_sync_faq_catalog_2811',24);

function ftc_seed_default_portfolio(){
    if (get_option('ftc_portfolio_seeded_2617')) return;
    $items = [
        ['PNM','Utility','Customer-focused energy information and service journeys.',FTC_URL.'assets/images/PNM_Website3.jpg','Clean, affordable, local energy information with better service pathways and content organization.'],
        ['NMEDD','Government / Economic Development','A statewide economic development platform.',FTC_URL.'assets/images/NMEDD_Website.jpg','A data-rich economic development website designed to help businesses understand why New Mexico is a strategic place to start, grow, or relocate.'],
        ['Rodgers & Co.','Water / Agriculture','A mobile-first brand and website experience.',FTC_URL.'assets/images/Rodgers_MobileSite.jpg','A strong visual story for a New Mexico water company, translating field expertise into a memorable web presence.'],
        ['OMNI CRE','Commercial Real Estate','Strategic commercial real estate advisors website and content system.',FTC_URL.'assets/images/OMNICRE_Desktop_Mockup.jpg','A purple, high-contrast commercial real estate experience with project spotlight content and adviser positioning.'],
        ['MySchoolsABQ','Education','School discovery, UX, and public information design.',FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg','A school discovery experience focused on clarity, trust, and easier public navigation.'],
        ['Amy Biehl High School','Education','Mobile-first school storytelling and resource access.',FTC_URL.'assets/images/AmyBiehlHighMockups.jpg','A bold, high-contrast school website system with strong mobile navigation and content pathways.'],
    ];
    $i = 0;
    foreach($items as $item){
        if (get_page_by_title($item[0], OBJECT, 'ftc_portfolio')) continue;
        $id = wp_insert_post([
            'post_type'=>'ftc_portfolio',
            'post_status'=>'publish',
            'post_title'=>$item[0],
            'post_excerpt'=>$item[2],
            'post_content'=>'<p>'.esc_html($item[4]).'</p><ul><li>Strategy, UX, and content planning</li><li>Visual design and interface direction</li><li>Website development and launch support</li></ul>',
            'menu_order'=>$i,
        ]);
        if($id && !is_wp_error($id)){
            update_post_meta($id,'_ftc_industry',$item[1]);
            update_post_meta($id,'_ftc_gallery_urls',implode("\n", array_unique([$item[3], FTC_URL.'assets/images/OMNICRE_Desktop_Mockup2.jpg', FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg'])));
            update_post_meta($id,'_ftc_results','Improved storytelling, navigation, usability, and digital presentation.');
            update_post_meta($id,'_ftc_featured','1');
            $att_id = attachment_url_to_postid($item[3]);
            if($att_id) set_post_thumbnail($id,$att_id);
        }
        $i++;
    }
    update_option('ftc_portfolio_seeded_2617',1);
}
add_action('admin_init','ftc_seed_default_portfolio');


function ftc_migrate_2620_design_assets(){
    if (get_option('ftc_assets_migrated_2620')) return;
    $service_map = [
        'website-development-core-tech' => 'https://placehold.co/960x540/242424/ffd94d?text=Website+Development',
        'digital-marketing-growth-strategy' => 'https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',
        'search-discovery-optimization-seo-aeo' => FTC_URL.'assets/images/service-seo.svg',
        'ecommerce-conversion-rate-optimization-cro' => FTC_URL.'assets/images/service-cro.svg',
        'data-analysis-visualization' => 'https://placehold.co/960x540/242424/ffd94d?text=Data+%26+Analytics',
        'creative-technology-innovation' => 'https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',
    ];
    foreach($service_map as $slug=>$img){
        $posts = get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){ update_post_meta($posts[0]->ID,'_ftc_service_image',$img); update_post_meta($posts[0]->ID,'_ftc_featured','1'); }
    }
    $portfolio_galleries = [
        'pnm' => [FTC_URL.'assets/images/PNM_Website3.jpg', FTC_URL.'assets/images/HeadingHome.jpg'],
        'nmedd' => [FTC_URL.'assets/images/NMEDD_Website.jpg', FTC_URL.'assets/images/PNM_Website3.jpg'],
        'rodgers-co' => [FTC_URL.'assets/images/Rodgers_MobileSite.jpg', FTC_URL.'assets/images/BeWellNM_Mobile.jpg'],
        'omni-cre' => [FTC_URL.'assets/images/OMNICRE_Desktop_Mockup.jpg', FTC_URL.'assets/images/OMNICRE_Desktop_Mockup2.jpg'],
        'myschoolsabq' => [FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg', FTC_URL.'assets/images/TheEducaationPlan_mobile.jpg'],
        'amy-biehl-high-school' => [FTC_URL.'assets/images/AmyBiehlHighMockups.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks2.jpg'],
    ];
    foreach($portfolio_galleries as $slug=>$imgs){
        $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){ update_post_meta($posts[0]->ID,'_ftc_gallery_urls',implode("\n",$imgs)); update_post_meta($posts[0]->ID,'_ftc_featured','1'); }
    }
    update_option('ftc_assets_migrated_2620',1);
}
add_action('admin_init','ftc_migrate_2620_design_assets');
add_action('init','ftc_migrate_2620_design_assets',20);


function ftc_service_catalog_2621(){
    return [
        [
            'title'=>'Website Development & Core Tech','slug'=>'website-development-core-tech','eyebrow'=>'API','image'=>'https://placehold.co/960x540/242424/ffd94d?text=Website+Development',
            'excerpt'=>'Websites, UX, CMS platforms, integrations, performance, accessibility, hosting, and core technical infrastructure.',
            'content'=>'<p>Your website is often the most important digital asset your business owns. We design, build, host, secure, maintain, and continuously improve websites and web applications that help organizations grow.</p><p>Whether you need a marketing website, enterprise platform, custom application, or digital ecosystem, we combine strategy, user experience, development, analytics, and ongoing support to create solutions that perform.</p><p><strong>Ongoing partnership:</strong> We frequently serve as an extension of internal marketing and IT teams, providing hosting, security updates, content support, technical maintenance, development enhancements, and long-term strategic guidance.</p>',
            'tasks'=>[
                'Strategy & Planning: Website Strategy & Planning, Information Architecture, Website Governance & Training, User Experience (UX) Design, User Interface (UI) Design',
                'Development: Website Development, Enterprise CMS Development, Custom Web Applications, Website Migrations & Replatforming, Headless CMS Architectures',
                'Performance & Support: ADA Accessibility Compliance, Website Security & Hardening, Hosting & Infrastructure, Website Maintenance & Support, Performance Optimization',
                'Integrations: API Integrations, CRM Integrations, ERP Integrations, Marketing Technology Integrations',
                'Platforms & Technologies: Drupal, WordPress, React, Next.js, Node.js, PHP, Custom CMS Development, Acquia, Pantheon, Cloud Hosting Platforms'
            ]
        ],
        [
            'title'=>'Ecommerce & Conversion Rate Optimization (CRO)','slug'=>'ecommerce-conversion-rate-optimization-cro','eyebrow'=>'CRO','image'=>'https://placehold.co/960x540/242424/ffd94d?text=Ecommerce+%26+CRO',
            'excerpt'=>'Strategic ecommerce experiences, checkout optimization, funnel analysis, testing, and measurable revenue growth.',
            'content'=>'<p>Driving traffic is only part of the equation. We help businesses turn visitors into customers through strategic ecommerce experiences, conversion optimization, testing, and performance analysis.</p><p>Our team combines UX, analytics, behavioral insights, and experimentation to improve revenue, average order value, customer retention, and overall ecommerce performance.</p><p><strong>Growth focus:</strong> We measure what matters, identify friction points, test solutions, and continuously optimize toward increased revenue and customer lifetime value.</p>',
            'tasks'=>[
                'Ecommerce Strategy: Ecommerce Strategy, Shopify Development, WooCommerce Development, Custom Ecommerce Platforms, Product Experience Design',
                'Optimization: Shopping Cart Optimization, Checkout Optimization, Subscription Models, Customer Journey Analysis, Conversion Rate Optimization (CRO), A/B Testing, Landing Page Optimization',
                'Analytics & Revenue: User Behavior Analysis, Funnel Analysis, Revenue Attribution, Customer Retention Strategy, Loyalty Programs',
                'Platforms: Shopify, Shopify Plus, WooCommerce, Custom Ecommerce Solutions',
                'Marketplace & Advertising: Amazon Advertising, Amazon Store Optimization, Product Listing Optimization, Google Shopping, Meta Commerce'
            ]
        ],
        [
            'title'=>'Data, Analysis & Visualization','slug'=>'data-analysis-visualization','eyebrow'=>'DATA','image'=>'https://placehold.co/960x540/242424/ffd94d?text=Data+%26+Analytics',
            'excerpt'=>'Analytics strategy, dashboards, reporting, business intelligence, attribution, visualization, and decision-ready insights.',
            'content'=>'<p>Data should drive decisions, not create confusion. We help organizations collect, organize, visualize, and activate their data to uncover opportunities and improve performance.</p><p>Our proprietary analytics platform, ANNA, combines business intelligence, marketing analytics, CRO insights, and executive reporting into custom dashboards focused on what matters most.</p><p><strong>Outcome:</strong> We help organizations move from reporting activity to measuring outcomes.</p>',
            'tasks'=>[
                'Analytics Strategy: Analytics Strategy, Data Collection & Governance, KPI Development, North Star Metric Identification, Attribution Modeling, Google Tag Manager',
                'Dashboards & Reporting: Dashboard Design, Business Intelligence, Executive Reporting, Custom Reporting Solutions, Automated Reporting, Business Intelligence Visualizations, Looker Studio, Tableau, BigQuery',
                'Marketing & Behavior: Marketing Analytics, Conversion Analytics, Performance Marketing Reporting, Behavioral Analytics, User Journey Analysis, Funnel Analysis, Predictive Insights',
                'Heatmapping & Session Analytics: Heatmaps, Session Recordings, Microsoft Clarity, Kissmetrics, Scroll Analysis, Click Tracking',
                'ANNA Analytics Platform: Executive Dashboards, Marketing Performance Tracking, Lead Generation Reporting, CRO Monitoring, Customer Journey Analysis, Cross-Channel Attribution',
                'Data Sources: Google Analytics, Google Search Console, CRM Platforms, Ecommerce Platforms, Advertising Platforms, Custom APIs, Internal Databases, BigQuery'
            ]
        ],
        [
            'title'=>'Search & Discovery Optimization (SEO / AEO)','slug'=>'search-discovery-optimization-seo-aeo','eyebrow'=>'SEO','image'=>'https://placehold.co/960x540/242424/ffd94d?text=SEO+%26+AEO',
            'excerpt'=>'Technical SEO, content strategy, local SEO, AI visibility, answer engine optimization, schema, and discovery platforms.',
            'content'=>'<p>Search has changed. Today’s customers discover brands through Google, AI assistants, ChatGPT, Gemini, Perplexity, voice search, maps, and emerging discovery platforms.</p><p>We help organizations improve visibility across both traditional search engines and AI-powered answer engines.</p><p><strong>Outcome:</strong> Help your organization become more discoverable wherever customers search, ask questions, or seek recommendations.</p>',
            'tasks'=>[
                'SEO Foundation: Technical SEO, Content Strategy, Keyword Research, Search Intent Analysis, On-Page SEO, Local SEO, Enterprise SEO',
                'Architecture & Content: Site Architecture Optimization, Internal Linking Strategies, Structured Data & Schema, Search Console Optimization, Content Audits, Competitive Analysis, Link Acquisition Strategies',
                'Answer Engine Optimization: Answer Engine Optimization (AEO), AI Search Visibility, AI Citation Optimization, Structured Content Development, Knowledge Graph Optimization, FAQ Optimization, Entity-Based Search Strategies',
                'Search Experiences: Voice Search Optimization, Featured Snippet Optimization, Maps Visibility, Local Discovery',
                'Platforms: Google, Bing, ChatGPT, Gemini, Perplexity, Claude, Voice Search Platforms'
            ]
        ],
        [
            'title'=>'Digital Marketing & Growth Strategy','slug'=>'digital-marketing-growth-strategy','eyebrow'=>'GROWTH','image'=>'https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',
            'excerpt'=>'Marketing strategy, campaigns, content, paid media, automation, customer journeys, attribution, and growth planning.',
            'content'=>'<p>Effective marketing connects business goals with customer needs. We develop integrated strategies that align channels, content, technology, and measurement to drive growth.</p><p>Our approach begins by understanding the customer journey and identifying opportunities to increase awareness, engagement, conversion, and retention.</p><p><strong>Outcome:</strong> Create measurable growth through strategic planning, integrated campaigns, and continuous optimization.</p>',
            'tasks'=>[
                'Strategy: Marketing Strategy, Customer Journey Mapping, Audience Research, Persona Development, Growth Planning, Campaign Development, Content Strategy',
                'Demand & Conversion: Marketing Automation, Lead Generation, Demand Generation, Performance Marketing, Marketing Attribution, Funnel Optimization, Retention Marketing, Customer Experience Strategy',
                'Paid Media: Google Ads, Google Display Network, YouTube Advertising, Meta Advertising, Facebook Advertising, Instagram Advertising, LinkedIn Advertising, Retargeting Campaigns',
                'Certifications & Expertise: Google Ads Certified, Google Analytics, Meta Advertising, Performance Marketing',
                'Managed Growth: Campaign Planning, Reporting, Optimization, Content Management, Ongoing Advisory Support'
            ]
        ],
        [
            'title'=>'Technology, Innovation and A.I.','slug'=>'creative-technology-innovation','eyebrow'=>'AI','image'=>'https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',
            'excerpt'=>'AI agents, workflow automation, conversational interfaces, interactive digital experiences, prototypes, and innovation consulting.',
            'content'=>'<p>Technology should create better experiences, improve efficiency, and unlock new opportunities. We help organizations explore and implement emerging technologies that drive business results.</p><p>From AI agents and automation to interactive digital experiences, we build innovative solutions that solve real-world problems.</p><p><strong>Outcome:</strong> Transform ideas into practical technology solutions that improve customer experiences, increase efficiency, and create competitive advantages.</p>',
            'tasks'=>[
                'AI & Automation: AI Strategy, AI Agent Development, AI Workflow Automation, Business Process Automation, Custom AI Tools, Internal AI Assistants, Customer Service Agents, Lead Qualification Agents',
                'Interactive Experiences: Interactive Web Experiences, Data Collection Experiences, Quizzes & Assessments, Interactive Calculators, Animated Experiences, Interactive Storytelling, Data Visualizations',
                'Digital Products: Digital Product Prototypes, Custom Applications, Conversational Interfaces, Customer Self-Service Tools, Workflow Optimization, Innovation Consulting',
                'Tools & Experiences: Product Finders, ROI Calculators, Configurators, Interactive Maps, Immersive Web Experiences',
                'Operations: Marketing Automation, Reporting Automation, CRM Automation, Business Process Automation, Internal Knowledge Tools'
            ]
        ],
    ];
}
function ftc_sync_service_catalog_2621(){
    if (get_option('ftc_services_synced_2624')) return;
    foreach(ftc_service_catalog_2621() as $i=>$svc){
        $existing = get_page_by_path($svc['slug'], OBJECT, 'ftc_service');
        if(!$existing) $existing = get_page_by_title($svc['title'], OBJECT, 'ftc_service');
        $postarr = [
            'post_type'=>'ftc_service','post_status'=>'publish','post_title'=>$svc['title'],'post_name'=>$svc['slug'],
            'post_excerpt'=>$svc['excerpt'],'post_content'=>$svc['content'],'menu_order'=>$i
        ];
        if($existing){ $postarr['ID']=$existing->ID; $id=wp_update_post($postarr); } else { $id=wp_insert_post($postarr); }
        if($id && !is_wp_error($id)){
            update_post_meta($id,'_ftc_service_eyebrow',$svc['eyebrow']);
            update_post_meta($id,'_ftc_service_image',$svc['image']);
            update_post_meta($id,'_ftc_service_tasks',implode("\n",$svc['tasks']));
            update_post_meta($id,'_ftc_featured','1');
        }
    }
    update_option('ftc_services_synced_2624',1);
}
add_action('admin_init','ftc_sync_service_catalog_2621', 8);


function ftc_refresh_portfolio_galleries_2622(){
    if (get_option('ftc_portfolio_galleries_2622')) return;
    $sets = [
        'pnm' => [FTC_URL.'assets/images/PNM_Website3.jpg', FTC_URL.'assets/images/HeadingHome.jpg', FTC_URL.'assets/images/AztecMechanical_Website.png'],
        'nmedd' => [FTC_URL.'assets/images/NMEDD_Website.jpg', FTC_URL.'assets/images/PNM_Website3.jpg', FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg'],
        'rodgers-co' => [FTC_URL.'assets/images/Rodgers_MobileSite.jpg', FTC_URL.'assets/images/BeWellNM_Mobile.jpg', FTC_URL.'assets/images/LetsPlantMobile.jpg'],
        'omni-cre' => [FTC_URL.'assets/images/OMNICRE_Desktop_Mockup.jpg', FTC_URL.'assets/images/OMNICRE_Desktop_Mockup2.jpg', FTC_URL.'assets/images/AztecMechanical_Website.png'],
        'myschoolsabq' => [FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg', FTC_URL.'assets/images/TheEducaationPlan_mobile.jpg', FTC_URL.'assets/images/TheEducaationPlan_mobile2.jpg'],
        'amy-biehl-high-school' => [FTC_URL.'assets/images/AmyBiehlHighMockups.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks2.jpg'],
    ];
    foreach($sets as $slug=>$imgs){
        $posts=get_posts(['post_type'=>'ftc_portfolio','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){ update_post_meta($posts[0]->ID,'_ftc_gallery_urls',implode("\n",$imgs)); update_post_meta($posts[0]->ID,'_ftc_featured','1'); }
    }
    update_option('ftc_portfolio_galleries_2622',1);
}
add_action('admin_init','ftc_refresh_portfolio_galleries_2622',9);
add_action('init','ftc_refresh_portfolio_galleries_2622',21);


function ftc_force_service_placeholders_2623(){
    if (get_option('ftc_service_placeholders_2623')) return;
    $map = [
        'website-development-core-tech' => 'https://placehold.co/960x540/242424/ffd94d?text=Website+Development',
        'digital-marketing-growth-strategy' => 'https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',
        'search-discovery-optimization-seo-aeo' => 'https://placehold.co/960x540/242424/ffd94d?text=SEO+%26+AEO',
        'ecommerce-conversion-rate-optimization-cro' => 'https://placehold.co/960x540/242424/ffd94d?text=Ecommerce+%26+CRO',
        'data-analysis-visualization' => 'https://placehold.co/960x540/242424/ffd94d?text=Data+%26+Analytics',
        'creative-technology-innovation' => 'https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',
    ];
    foreach($map as $slug=>$url){
        $posts = get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){ update_post_meta($posts[0]->ID,'_ftc_service_image',$url); update_post_meta($posts[0]->ID,'_ftc_featured','1'); }
    }
    update_option('ftc_service_placeholders_2623',1);
}
add_action('admin_init','ftc_force_service_placeholders_2623',10);
add_action('init','ftc_force_service_placeholders_2623',22);


function ftc_migrate_2626_visual_content(){
    if (get_option('ftc_visual_content_migrated_2626')) return;

    $service_map = [
        'website-development-core-tech' => FTC_URL.'assets/images/placeholder-service-web.svg',
        'digital-marketing-growth-strategy' => FTC_URL.'assets/images/placeholder-service-marketing.svg',
        'search-discovery-optimization-seo-aeo' => FTC_URL.'assets/images/placeholder-service-seo.svg',
        'ecommerce-conversion-rate-optimization-cro' => FTC_URL.'assets/images/placeholder-service-cro.svg',
        'data-analysis-visualization' => FTC_URL.'assets/images/placeholder-service-data.svg',
        'creative-technology-innovation' => FTC_URL.'assets/images/placeholder-service-ai.svg',
    ];
    foreach($service_map as $slug=>$img){
        $posts = get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){
            update_post_meta($posts[0]->ID,'_ftc_service_image',$img);
            update_post_meta($posts[0]->ID,'_ftc_featured','1');
        }
    }

    $portfolio_galleries = [
        'pnm' => [FTC_URL.'assets/images/PNM_Website3.jpg', FTC_URL.'assets/images/placeholder-portfolio-2.svg', FTC_URL.'assets/images/placeholder-portfolio-3.svg'],
        'nmedd' => [FTC_URL.'assets/images/NMEDD_Website.jpg', FTC_URL.'assets/images/placeholder-portfolio-1.svg', FTC_URL.'assets/images/placeholder-portfolio-4.svg'],
        'rodgers-co' => [FTC_URL.'assets/images/Rodgers_MobileSite.jpg', FTC_URL.'assets/images/placeholder-portfolio-2.svg', FTC_URL.'assets/images/placeholder-portfolio-3.svg'],
        'omni-cre' => [FTC_URL.'assets/images/OMNICRE_Desktop_Mockup.jpg', FTC_URL.'assets/images/OMNICRE_Desktop_Mockup2.jpg', FTC_URL.'assets/images/placeholder-portfolio-4.svg'],
        'myschoolsabq' => [FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg', FTC_URL.'assets/images/placeholder-portfolio-1.svg', FTC_URL.'assets/images/placeholder-portfolio-2.svg'],
        'amy-biehl-high-school' => [FTC_URL.'assets/images/AmyBiehlHighMockups.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks.jpg', FTC_URL.'assets/images/AmyBiehlHighMobileMocks2.jpg'],
    ];

    foreach($portfolio_galleries as $slug=>$imgs){
        $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if($posts){
            update_post_meta($posts[0]->ID,'_ftc_gallery_urls',implode("\n",$imgs));
            update_post_meta($posts[0]->ID,'_ftc_featured','1');
        }
    }

    update_option('ftc_visual_content_migrated_2626', 1);
}
add_action('init','ftc_migrate_2626_visual_content',30);
add_action('admin_init','ftc_migrate_2626_visual_content');


function ftc_migrate_2627_responsive_cleanup(){
    if (get_option('ftc_responsive_cleanup_2627')) return;

    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'any','posts_per_page'=>-1]);
    foreach($services as $svc){
        update_post_meta($svc->ID,'_ftc_service_image',FTC_URL.'assets/images/placeholder-gray-16x9.svg');
        update_post_meta($svc->ID,'_ftc_featured','1');
    }

    $projects = get_posts(['post_type'=>'ftc_portfolio','post_status'=>'any','posts_per_page'=>-1]);
    foreach($projects as $project){
        $gallery = trim((string)get_post_meta($project->ID,'_ftc_gallery_urls',true));
        if(!$gallery){
            update_post_meta($project->ID,'_ftc_gallery_urls',implode("\n",[
                FTC_URL.'assets/images/placeholder-gray-16x9.svg',
                FTC_URL.'assets/images/placeholder-gray-16x9.svg',
                FTC_URL.'assets/images/placeholder-gray-16x9.svg'
            ]));
        }
        update_post_meta($project->ID,'_ftc_featured','1');
    }

    update_option('ftc_responsive_cleanup_2627', 1);
}
add_action('init','ftc_migrate_2627_responsive_cleanup',35);
add_action('admin_init','ftc_migrate_2627_responsive_cleanup');


function ftc_migrate_2632_visual_cleanup(){
    if (get_option('ftc_visual_cleanup_2632')) return;

    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'any','posts_per_page'=>-1]);
    foreach($services as $svc){
        update_post_meta($svc->ID,'_ftc_service_image',FTC_URL.'assets/images/placeholder-gray-16x9.svg');
        update_post_meta($svc->ID,'_ftc_featured','1');
    }

    $projects = get_posts(['post_type'=>'ftc_portfolio','post_status'=>'any','posts_per_page'=>-1]);
    foreach($projects as $project){
        $gallery = trim((string)get_post_meta($project->ID,'_ftc_gallery_urls',true));
        if(!$gallery){
            update_post_meta($project->ID,'_ftc_gallery_urls',implode("\n",[
                FTC_URL.'assets/images/placeholder-gray-16x9.svg',
                FTC_URL.'assets/images/placeholder-gray-16x9.svg',
                FTC_URL.'assets/images/placeholder-gray-16x9.svg'
            ]));
        }
        update_post_meta($project->ID,'_ftc_featured','1');
    }

    update_option('ftc_visual_cleanup_2632', 1);
}
add_action('init','ftc_migrate_2632_visual_cleanup',35);
add_action('admin_init','ftc_migrate_2632_visual_cleanup');

function ftc_placeholder_image_url(){
    return FTC_URL.'assets/images/placeholder-gray-16x9.svg';
}

function ftc_placeholder_gallery_urls($count=5){
    $count = max(3, min(5, absint($count)));
    return array_fill(0, $count, ftc_placeholder_image_url());
}

function ftc_get_placeholder_attachment_id(){
    $existing = get_posts([
        'post_type'=>'attachment',
        'post_status'=>'inherit',
        'posts_per_page'=>1,
        'fields'=>'ids',
        'meta_key'=>'_ftc_placeholder_attachment',
        'meta_value'=>'gray-16x9'
    ]);
    if($existing) return absint($existing[0]);

    $source = FTC_PATH.'assets/images/placeholder-gray-16x9.svg';
    if(!file_exists($source)) return 0;

    $upload = wp_upload_dir();
    if(!empty($upload['error']) || empty($upload['basedir']) || empty($upload['baseurl'])) return 0;

    $destination_dir = trailingslashit($upload['basedir']).'field-theory-concierge';
    if(!wp_mkdir_p($destination_dir)) return 0;

    $filename = 'placeholder-gray-16x9.svg';
    $destination = trailingslashit($destination_dir).$filename;
    if(!file_exists($destination) || md5_file($destination) !== md5_file($source)){
        if(!copy($source, $destination)) return 0;
    }

    $relative_file = 'field-theory-concierge/'.$filename;
    $attachment_id = wp_insert_attachment([
        'guid'=>trailingslashit($upload['baseurl']).$relative_file,
        'post_mime_type'=>'image/svg+xml',
        'post_title'=>'Field Theory temporary image placeholder',
        'post_content'=>'',
        'post_status'=>'inherit'
    ], $destination);

    if(!$attachment_id || is_wp_error($attachment_id)) return 0;

    update_post_meta($attachment_id, '_ftc_placeholder_attachment', 'gray-16x9');
    update_post_meta($attachment_id, '_wp_attached_file', $relative_file);
    wp_update_attachment_metadata($attachment_id, [
        'width'=>1280,
        'height'=>720,
        'file'=>$relative_file,
    ]);

    return absint($attachment_id);
}

function ftc_import_plugin_image_attachment($relative_path, $title, $meta_key, $meta_value){
    $existing = get_posts([
        'post_type'=>'attachment',
        'post_status'=>'inherit',
        'posts_per_page'=>1,
        'fields'=>'ids',
        'meta_key'=>$meta_key,
        'meta_value'=>$meta_value,
    ]);
    if($existing) return absint($existing[0]);

    $source = FTC_PATH.ltrim($relative_path,'/');
    if(!file_exists($source)) return 0;

    $upload = wp_upload_dir();
    if(!empty($upload['error']) || empty($upload['basedir']) || empty($upload['baseurl'])) return 0;

    $destination_dir = trailingslashit($upload['basedir']).'field-theory-concierge';
    if(!wp_mkdir_p($destination_dir)) return 0;

    $filename = wp_unique_filename($destination_dir, basename($source));
    $destination = trailingslashit($destination_dir).$filename;
    if(!copy($source, $destination)) return 0;

    $relative_file = 'field-theory-concierge/'.$filename;
    $filetype = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment([
        'guid'=>trailingslashit($upload['baseurl']).$relative_file,
        'post_mime_type'=>$filetype['type'] ?: 'image/jpeg',
        'post_title'=>$title,
        'post_content'=>'',
        'post_status'=>'inherit',
    ], $destination);

    if(!$attachment_id || is_wp_error($attachment_id)) return 0;

    require_once ABSPATH.'wp-admin/includes/image.php';
    update_post_meta($attachment_id, $meta_key, $meta_value);
    update_post_meta($attachment_id, '_wp_attached_file', $relative_file);
    wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $destination));

    return absint($attachment_id);
}

function ftc_migrate_280_placeholder_media(){
    if(get_option('ftc_placeholder_media_280')) return;

    $defaults = ftc_default_settings();
    $settings = wp_parse_args((array)get_option('ftc_settings', []), $defaults);
    foreach(['dark_logo','light_logo','icon_logo','background_image'] as $key){
        $settings[$key] = $defaults[$key];
    }
    update_option('ftc_settings', $settings);

    $placeholder_url = ftc_placeholder_image_url();
    $placeholder_gallery = implode("\n", ftc_placeholder_gallery_urls(5));
    $placeholder_attachment_id = ftc_get_placeholder_attachment_id();

    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'any','posts_per_page'=>-1]);
    foreach($services as $service){
        update_post_meta($service->ID, '_ftc_service_image', $placeholder_url);
        update_post_meta($service->ID, '_ftc_featured', '1');
        if($placeholder_attachment_id) set_post_thumbnail($service->ID, $placeholder_attachment_id);
    }

    $projects = get_posts(['post_type'=>'ftc_portfolio','post_status'=>'any','posts_per_page'=>-1]);
    foreach($projects as $project){
        update_post_meta($project->ID, '_ftc_gallery_urls', $placeholder_gallery);
        update_post_meta($project->ID, '_ftc_featured', '1');
        if($placeholder_attachment_id) set_post_thumbnail($project->ID, $placeholder_attachment_id);
    }

    update_option('ftc_placeholder_media_280', 1);
}
add_action('init','ftc_migrate_280_placeholder_media',45);
add_action('admin_init','ftc_migrate_280_placeholder_media',45);

function ftc_migrate_281_portfolio_images(){
    if(get_option('ftc_portfolio_images_281')) return;

    $base = FTC_URL.'assets/images/portfolio/';
    $sets = [
        'pnm' => [
            $base.'PNM_Website.jpg',
            $base.'PNM_Website2.jpg',
            $base.'PNM_Website3.jpg',
            $base.'HeadingHome.jpg',
        ],
        'nmedd' => [
            $base.'NMEDD_Website.jpg',
            $base.'USAMapMockup.jpg',
            $base.'SOS_LandingPage.jpg',
            $base.'SteamResources.jpg',
        ],
        'rodgers-co' => [
            $base.'Rodgers_MobileSite.jpg',
            $base.'LetsPlantMobile.jpg',
            $base.'LetsPlantMobile2.jpg',
            $base.'AztecMechanical_Website.png',
        ],
        'omni-cre' => [
            $base.'OMNICRE_Desktop_Mockup.jpg',
            $base.'OMNICRE_Desktop_Mockup2.jpg',
            $base.'OMNI_CRE.jpg',
        ],
        'myschoolsabq' => [
            $base.'MySchoolsAQBDesktop.jpg',
            $base.'MySchoolsAbQ_Mobile.jpg',
            $base.'EducationCenterMockup.jpg',
            $base.'TheEducaationPlan_mobile.jpg',
        ],
        'amy-biehl-high-school' => [
            $base.'AmyBiehlHighMockups.jpg',
            $base.'AmyBiehlHighMobileMocks.jpg',
            $base.'AmyBiehlHighMobileMocks2.jpg',
            $base.'AmyBiehlMockupsClose.jpg',
            $base.'AmyBiehlMockupsJPG.jpg',
        ],
    ];

    foreach($sets as $slug=>$imgs){
        $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>$slug,'posts_per_page'=>1,'post_status'=>'any']);
        if(!$posts) continue;
        $project_id = $posts[0]->ID;
        update_post_meta($project_id, '_ftc_gallery_urls', implode("\n", $imgs));
        update_post_meta($project_id, '_ftc_featured', '1');
        delete_post_thumbnail($project_id);
    }

    update_option('ftc_portfolio_images_281', 1);
}
add_action('init','ftc_migrate_281_portfolio_images',55);
add_action('admin_init','ftc_migrate_281_portfolio_images',55);

function ftc_migrate_2812_amy_biehl_hero(){
    if(get_option('ftc_amy_biehl_hero_2812')) return;

    $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>'amy-biehl-high-school','posts_per_page'=>1,'post_status'=>'any']);
    if(!$posts) $posts = get_posts(['post_type'=>'ftc_portfolio','s'=>'Amy Biehl','posts_per_page'=>1,'post_status'=>'any']);
    if($posts){
        $project_id = $posts[0]->ID;
        $hero_id = ftc_import_plugin_image_attachment('assets/images/portfolio/AmyBiehlHero.jpg','Amy Biehl High School hero mockup','_ftc_asset_attachment','amy-biehl-hero-2812');
        if($hero_id){
            set_post_thumbnail($project_id, $hero_id);
            $existing_ids = array_filter(array_map('absint',explode(',',(string)get_post_meta($project_id,'_ftc_gallery_ids',true))));
            array_unshift($existing_ids, $hero_id);
            update_post_meta($project_id,'_ftc_gallery_ids',implode(',',array_values(array_unique($existing_ids))));
        }
        update_post_meta($project_id,'_ftc_gallery_urls',implode("\n",[
            FTC_URL.'assets/images/portfolio/AmyBiehlHero.jpg',
            FTC_URL.'assets/images/portfolio/AmyBiehlHighMockups.jpg',
            FTC_URL.'assets/images/portfolio/AmyBiehlHighMobileMocks.jpg',
            FTC_URL.'assets/images/portfolio/AmyBiehlHighMobileMocks2.jpg',
            FTC_URL.'assets/images/portfolio/AmyBiehlMockupsClose.jpg',
        ]));
        update_post_meta($project_id,'_ftc_featured','1');
    }

    update_option('ftc_amy_biehl_hero_2812', 1);
}
add_action('init','ftc_migrate_2812_amy_biehl_hero',58);
add_action('admin_init','ftc_migrate_2812_amy_biehl_hero',58);

function ftc_portfolio_is_placeholder_url_2813($url){
    $url = strtolower((string)$url);
    if($url === '') return false;
    return strpos($url,'placeholder-gray') !== false || strpos($url,'placeholder-portfolio') !== false || strpos($url,'placehold.co') !== false;
}

function ftc_migrate_2813_remove_portfolio_fallback_images(){
    if(get_option('ftc_portfolio_no_fallback_images_2813')) return;

    $projects = get_posts(['post_type'=>'ftc_portfolio','post_status'=>'any','posts_per_page'=>-1]);
    foreach($projects as $project){
        $gallery = get_post_meta($project->ID,'_ftc_gallery_urls',true);
        $urls = array_values(array_filter(array_map('trim',explode("\n",(string)$gallery)), function($url){
            return $url !== '' && !ftc_portfolio_is_placeholder_url_2813($url);
        }));
        if($urls) update_post_meta($project->ID,'_ftc_gallery_urls',implode("\n",array_unique($urls)));
        else delete_post_meta($project->ID,'_ftc_gallery_urls');

        $ids = array_values(array_filter(array_map('absint',explode(',',(string)get_post_meta($project->ID,'_ftc_gallery_ids',true))), function($id){
            $url = $id ? wp_get_attachment_image_url($id,'large') : '';
            return $url && !ftc_portfolio_is_placeholder_url_2813($url);
        }));
        if($ids) update_post_meta($project->ID,'_ftc_gallery_ids',implode(',',array_unique($ids)));
        else delete_post_meta($project->ID,'_ftc_gallery_ids');

        $thumb = has_post_thumbnail($project->ID) ? get_the_post_thumbnail_url($project->ID,'large') : '';
        if($thumb && ftc_portfolio_is_placeholder_url_2813($thumb)) delete_post_thumbnail($project->ID);
    }

    update_option('ftc_portfolio_no_fallback_images_2813', 1);
}
add_action('init','ftc_migrate_2813_remove_portfolio_fallback_images',59);
add_action('admin_init','ftc_migrate_2813_remove_portfolio_fallback_images',59);

function ftc_portfolio_project_catalog_2818(){
    return [
        [
            'title'=>"Let's Plant Albuquerque",
            'slug'=>'lets-plant-albuquerque',
            'aliases'=>["Let's Plant Albuquerque"],
            'industry'=>'Civic / Environment',
            'excerpt'=>'A mobile-forward public campaign experience for community tree planting and local participation.',
            'content'=>'<p>Field Theory helped shape a clear digital experience for a public-facing environmental campaign, making it easier for people to understand the program, take action, and connect with the work.</p>',
            'results'=>'Clearer campaign storytelling, mobile-first access, and stronger community action pathways.',
            'images'=>['assets/images/portfolio/LetsPlantMobile.jpg','assets/images/portfolio/LetsPlantMobile2.jpg'],
        ],
        [
            'title'=>'The Education Plan',
            'slug'=>'the-education-plan',
            'aliases'=>['The Education Plan'],
            'industry'=>'Education / Finance',
            'excerpt'=>'Digital storytelling and mobile content pathways for education savings programs.',
            'content'=>'<p>Field Theory supported education-focused digital communication with cleaner mobile presentation, clearer program messaging, and easier pathways for families to understand next steps.</p>',
            'results'=>'Improved mobile storytelling, clearer program communication, and easier access to key information.',
            'images'=>['assets/images/portfolio/TheEducaationPlan_mobile.jpg','assets/images/portfolio/TheEducaationPlan_mobile2.jpg','assets/images/LogoTEP.jpg'],
        ],
        [
            'title'=>'BeWell New Mexico',
            'slug'=>'bewell-new-mexico',
            'aliases'=>['BeWell New Mexico','BeWellNM'],
            'industry'=>'Healthcare',
            'excerpt'=>'A clearer mobile experience for healthcare information and enrollment support.',
            'content'=>'<p>Field Theory helped translate complex healthcare information into a more approachable digital experience, with attention to mobile access, content clarity, and user confidence.</p>',
            'results'=>'Better mobile access, clearer user pathways, and more understandable healthcare content.',
            'images'=>['assets/images/BeWellNM_Mobile.jpg'],
        ],
        [
            'title'=>'Amy Biehl High School',
            'slug'=>'amy-biehl-high-school',
            'aliases'=>['Amy Biehl Highschool','Amy Biehl High School'],
            'industry'=>'Education',
            'excerpt'=>'Mobile-first school storytelling and resource access.',
            'content'=>'<p>Field Theory developed a bold school website experience focused on student-centered storytelling, fast mobile navigation, and clearer access to academic information, resources, and enrollment pathways.</p>',
            'results'=>'Stronger school storytelling, improved mobile navigation, and easier resource discovery.',
            'images'=>['assets/images/portfolio/AmyBiehlHero.jpg','assets/images/portfolio/AmyBiehlHighMockups.jpg','assets/images/portfolio/AmyBiehlHighMobileMocks.jpg','assets/images/portfolio/AmyBiehlHighMobileMocks2.jpg','assets/images/portfolio/AmyBiehlMockupsClose.jpg'],
        ],
        [
            'title'=>'BioAffinity Technologies',
            'slug'=>'bioaffinity-technologies',
            'aliases'=>['BioAffinity Technologies','bioAffinity Technologies'],
            'industry'=>'Healthcare / Biotechnology',
            'excerpt'=>'A placeholder project entry ready for BioAffinity Technologies images and detail content.',
            'content'=>'<p>This project entry is ready for BioAffinity Technologies imagery and details. Replace the placeholder image in the portfolio admin when final assets are available.</p>',
            'results'=>'Temporary placeholder entry prepared for image replacement in WordPress.',
            'images'=>[],
        ],
        [
            'title'=>'Heading Home ABQ',
            'slug'=>'heading-home-abq',
            'aliases'=>['Heading Home ABQ','Heading Home'],
            'industry'=>'Nonprofit / Housing',
            'excerpt'=>'Mission-driven digital storytelling for housing, support, and community services.',
            'content'=>'<p>Field Theory supported mission-driven communication with clearer presentation, approachable content, and digital pathways that help people understand programs, impact, and ways to engage.</p>',
            'results'=>'Clearer nonprofit storytelling and more approachable service communication.',
            'images'=>['assets/images/portfolio/HeadingHome.jpg'],
        ],
        [
            'title'=>'MySchoolsABQ',
            'slug'=>'myschoolsabq',
            'aliases'=>['MySchoolsABQ','MySchoolsABQ','MySchools ABQ'],
            'industry'=>'Education',
            'excerpt'=>'School discovery, UX, and public information design.',
            'content'=>'<p>Field Theory helped create a school discovery experience focused on clarity, trust, and easier public navigation, making education options easier for families to compare and understand.</p>',
            'results'=>'Improved school discovery, clearer public information, and better mobile access.',
            'images'=>['assets/images/portfolio/MySchoolsAQBDesktop.jpg','assets/images/portfolio/MySchoolsAbQ_Mobile.jpg','assets/images/portfolio/EducationCenterMockup.jpg'],
        ],
        [
            'title'=>'PNM',
            'slug'=>'pnm',
            'aliases'=>['PNM'],
            'industry'=>'Utility',
            'excerpt'=>'Customer-focused energy information and service journeys.',
            'content'=>'<p>Field Theory worked on digital presentation and customer pathways for energy information, helping make service content easier to understand and navigate.</p>',
            'results'=>'Improved storytelling, navigation, usability, and digital presentation.',
            'images'=>['assets/images/portfolio/PNM_Website.jpg','assets/images/portfolio/PNM_Website2.jpg','assets/images/portfolio/PNM_Website3.jpg'],
        ],
        [
            'title'=>'New Mexico Economic Development',
            'slug'=>'nmedd',
            'aliases'=>['NMEDD','New Mexico Economic Development','New Mexico Economic Development Department'],
            'industry'=>'Government / Economic Development',
            'excerpt'=>'A statewide economic development platform.',
            'content'=>'<p>Field Theory helped organize a data-rich economic development website designed to help businesses understand New Mexico as a strategic place to start, grow, or relocate.</p>',
            'results'=>'Clearer business attraction content, stronger navigation, and more useful statewide economic development storytelling.',
            'images'=>['assets/images/portfolio/NMEDD_Website.jpg','assets/images/portfolio/USAMapMockup.jpg','assets/images/portfolio/SOS_LandingPage.jpg','assets/images/portfolio/SteamResources.jpg'],
        ],
        [
            'title'=>'Omni CRE',
            'slug'=>'omni-cre',
            'aliases'=>['OMNI CRE','Omni CRE'],
            'industry'=>'Commercial Real Estate',
            'excerpt'=>'Strategic commercial real estate advisors website and content system.',
            'content'=>'<p>Field Theory supported a high-contrast commercial real estate experience with project spotlight content, advisor positioning, and clearer presentation of expertise.</p>',
            'results'=>'Stronger firm positioning, clearer project storytelling, and improved content presentation.',
            'images'=>['assets/images/portfolio/OMNICRE_Desktop_Mockup.jpg','assets/images/portfolio/OMNICRE_Desktop_Mockup2.jpg','assets/images/portfolio/OMNI_CRE.jpg'],
        ],
        [
            'title'=>'New Mexico Partnership',
            'slug'=>'new-mexico-partnership',
            'aliases'=>['New Mexico Partnership'],
            'industry'=>'Economic Development',
            'excerpt'=>'A placeholder project entry ready for New Mexico Partnership images and detail content.',
            'content'=>'<p>This project entry is ready for New Mexico Partnership imagery and details. Replace the placeholder image in the portfolio admin when final assets are available.</p>',
            'results'=>'Temporary placeholder entry prepared for image replacement in WordPress.',
            'images'=>[],
        ],
        [
            'title'=>'Enhanced Wellness',
            'slug'=>'enhanced-wellness',
            'aliases'=>['Enhanced Wellness'],
            'industry'=>'Healthcare / Wellness',
            'excerpt'=>'A placeholder project entry ready for Enhanced Wellness images and detail content.',
            'content'=>'<p>This project entry is ready for Enhanced Wellness imagery and details. Replace the placeholder image in the portfolio admin when final assets are available.</p>',
            'results'=>'Temporary placeholder entry prepared for image replacement in WordPress.',
            'images'=>[],
        ],
        [
            'title'=>'Rodgers & Co.',
            'slug'=>'rodgers-co',
            'aliases'=>['Rodgers & Co.','Rodgers and Co.','Rodgers & Co'],
            'industry'=>'Water / Agriculture',
            'excerpt'=>'A mobile-first brand and website experience.',
            'content'=>'<p>Field Theory helped translate field expertise into a memorable digital presence, using strong visual storytelling and a mobile-friendly presentation for a New Mexico water company.</p>',
            'results'=>'A stronger visual story, clearer service positioning, and improved mobile presentation.',
            'images'=>['assets/images/portfolio/Rodgers_MobileSite.jpg'],
        ],
        [
            'title'=>'Aztec Mechanical',
            'slug'=>'aztec-mechanical',
            'aliases'=>['Aztec Mechanical'],
            'industry'=>'Construction / Mechanical',
            'excerpt'=>'A practical digital presence for mechanical services and project credibility.',
            'content'=>'<p>Field Theory supported a straightforward website presentation focused on service clarity, credibility, and making the company easier to understand online.</p>',
            'results'=>'Clearer service presentation and a more polished digital presence.',
            'images'=>['assets/images/portfolio/AztecMechanical_Website.png'],
        ],
    ];
}

function ftc_find_portfolio_project_for_sync_2818($project){
    $existing = get_page_by_path($project['slug'], OBJECT, 'ftc_portfolio');
    if($existing) return $existing;
    foreach((array)($project['aliases'] ?? []) as $title){
        $existing = get_page_by_title($title, OBJECT, 'ftc_portfolio');
        if($existing) return $existing;
    }
    $existing = get_page_by_title($project['title'], OBJECT, 'ftc_portfolio');
    return $existing ?: null;
}

function ftc_migrate_2818_portfolio_project_catalog(){
    if(get_option('ftc_portfolio_project_catalog_2818')) return;

    $placeholder_id = ftc_get_placeholder_attachment_id();
    foreach(ftc_portfolio_project_catalog_2818() as $order=>$project){
        $existing = ftc_find_portfolio_project_for_sync_2818($project);
        $postarr = [
            'post_type'=>'ftc_portfolio',
            'post_status'=>'publish',
            'post_title'=>$project['title'],
            'post_name'=>$project['slug'],
            'post_excerpt'=>$project['excerpt'],
            'post_content'=>$project['content'],
            'menu_order'=>$order,
        ];
        if($existing) $postarr['ID'] = $existing->ID;
        $id = $existing ? wp_update_post($postarr) : wp_insert_post($postarr);
        if(!$id || is_wp_error($id)) continue;

        $attachment_ids = [];
        foreach((array)$project['images'] as $relative_path){
            $relative_path = ltrim((string)$relative_path, '/');
            if(!file_exists(FTC_PATH.$relative_path)) continue;
            $asset_key = $project['slug'].'-'.sanitize_title(basename($relative_path));
            $attachment_id = ftc_import_plugin_image_attachment($relative_path, $project['title'].' project image', '_ftc_portfolio_asset_2818', $asset_key);
            if($attachment_id) $attachment_ids[] = $attachment_id;
        }

        $uses_placeholder = false;
        if(!$attachment_ids && $placeholder_id){
            $attachment_ids[] = $placeholder_id;
            $uses_placeholder = true;
        }

        update_post_meta($id,'_ftc_industry',$project['industry']);
        update_post_meta($id,'_ftc_results',$project['results']);
        update_post_meta($id,'_ftc_featured','1');
        delete_post_meta($id,'_ftc_gallery_urls');

        if($attachment_ids){
            $attachment_ids = array_values(array_unique(array_map('absint',$attachment_ids)));
            update_post_meta($id,'_ftc_gallery_ids',implode(',',$attachment_ids));
            set_post_thumbnail($id,$attachment_ids[0]);
        } else {
            delete_post_meta($id,'_ftc_gallery_ids');
            delete_post_thumbnail($id);
        }

        if($uses_placeholder) update_post_meta($id,'_ftc_allow_placeholder_image','1');
        else delete_post_meta($id,'_ftc_allow_placeholder_image');
    }

    update_option('ftc_portfolio_project_catalog_2818', 1);
}
add_action('init','ftc_migrate_2818_portfolio_project_catalog',61);
add_action('admin_init','ftc_migrate_2818_portfolio_project_catalog',61);

function ftc_migrate_2819_service_naming_cleanup(){
    if(get_option('ftc_service_naming_cleanup_2819')) return;

    $post = get_page_by_path('creative-technology-innovation', OBJECT, 'ftc_service');
    if($post){
        wp_update_post([
            'ID'=>$post->ID,
            'post_title'=>'Technology, Innovation and A.I.',
            'post_excerpt'=>'AI agents, workflow automation, conversational interfaces, interactive digital experiences, prototypes, and innovation consulting.',
        ]);
        update_post_meta($post->ID,'_ftc_service_eyebrow','AI');
    }

    update_option('ftc_service_naming_cleanup_2819', 1);
}
add_action('init','ftc_migrate_2819_service_naming_cleanup',62);
add_action('admin_init','ftc_migrate_2819_service_naming_cleanup',62);

function ftc_migrate_2842_education_plan_video(){
    if(get_option('ftc_education_plan_video_2842')) return;

    $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>'the-education-plan','posts_per_page'=>1,'post_status'=>'any']);
    if(!$posts){
        $posts = get_posts(['post_type'=>'ftc_portfolio','s'=>'Education Plan','posts_per_page'=>1,'post_status'=>'any']);
    }
    if(!$posts){ update_option('ftc_education_plan_video_2842', 1); return; }

    $id = $posts[0]->ID;

    // Set the YouTube embed URL
    update_post_meta($id, '_ftc_video_url', 'https://www.youtube.com/embed/pIED_Upg75s?si=QzQdxKOG6n-T7gp4');

    // Import and set the desktop homepage screenshot as the featured/first gallery image
    $desktop_attachment_id = ftc_import_plugin_image_attachment(
        'assets/images/portfolio/TheEducaationPlan_desktop.png',
        'The Education Plan homepage desktop',
        '_ftc_portfolio_asset_2842',
        'the-education-plan-desktop-2842'
    );

    if($desktop_attachment_id){
        // Set as the post thumbnail (featured image)
        set_post_thumbnail($id, $desktop_attachment_id);

        // Prepend to gallery_ids so it appears first
        $existing_ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($id, '_ftc_gallery_ids', true))));
        array_unshift($existing_ids, $desktop_attachment_id);
        update_post_meta($id, '_ftc_gallery_ids', implode(',', array_values(array_unique($existing_ids))));
    }

    // Update gallery_urls to include desktop screenshot first
    $desktop_url = FTC_URL . 'assets/images/portfolio/TheEducaationPlan_desktop.png';
    $mobile_url1 = FTC_URL . 'assets/images/portfolio/TheEducaationPlan_mobile.jpg';
    $mobile_url2 = FTC_URL . 'assets/images/portfolio/TheEducaationPlan_mobile2.jpg';
    update_post_meta($id, '_ftc_gallery_urls', implode("\n", [$desktop_url, $mobile_url1, $mobile_url2]));

    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');

    update_option('ftc_education_plan_video_2842', 1);
}
add_action('init', 'ftc_migrate_2842_education_plan_video', 65);
add_action('admin_init', 'ftc_migrate_2842_education_plan_video', 65);

function ftc_migrate_2843_education_plan_gallery(){
    if(get_option('ftc_education_plan_gallery_2843')) return;

    $posts = get_posts(['post_type'=>'ftc_portfolio','name'=>'the-education-plan','posts_per_page'=>1,'post_status'=>'any']);
    if(!$posts){
        $posts = get_posts(['post_type'=>'ftc_portfolio','s'=>'Education Plan','posts_per_page'=>1,'post_status'=>'any']);
    }
    if(!$posts){ update_option('ftc_education_plan_gallery_2843', 1); return; }

    $id = $posts[0]->ID;

    // Clear existing gallery meta
    delete_post_meta($id, '_ftc_gallery_urls');
    delete_post_meta($id, '_ftc_gallery_ids');

    // Set gallery URLs (extra screenshots shown below the hero; desktop thumbnail handled by migration 2842)
    $gallery_urls = [
        FTC_URL . 'assets/images/portfolio/TEP_mobile_home.png',
        FTC_URL . 'assets/images/portfolio/TEP_mobile_splash.png',
        FTC_URL . 'assets/images/portfolio/TEP_mobile_splash2.png',
        FTC_URL . 'assets/images/portfolio/TEP_mobile_menu.png',
        FTC_URL . 'assets/images/portfolio/TEP_mobile_faqs.png',
        FTC_URL . 'assets/images/portfolio/TEP_mobile_content.png',
        FTC_URL . 'assets/images/portfolio/TEP_glidepath.png',
        FTC_URL . 'assets/images/portfolio/TEP_learning_center_desktop.png',
        FTC_URL . 'assets/images/portfolio/TEP_learning_center_mobile.png',
    ];
    update_post_meta($id, '_ftc_gallery_urls', implode("\n", $gallery_urls));

    // Re-confirm video, featured flag, remove placeholder
    update_post_meta($id, '_ftc_video_url', 'https://www.youtube.com/embed/pIED_Upg75s?si=QzQdxKOG6n-T7gp4');
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');

    update_option('ftc_education_plan_gallery_2843', 1);
}
add_action('init', 'ftc_migrate_2843_education_plan_gallery', 66);
add_action('admin_init', 'ftc_migrate_2843_education_plan_gallery', 66);

function ftc_migrate_2844_bewell_gallery(){
    if(get_option('ftc_bewell_gallery_2844')) return;

    $post = get_page_by_path('bewell-new-mexico', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('BeWell New Mexico', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts(['post_type'=>'ftc_portfolio','s'=>'BeWell','posts_per_page'=>1,'post_status'=>'any']);
        if($found) $post = $found[0];
    }
    if(!$post){ update_option('ftc_bewell_gallery_2844', 1); return; }

    $id = $post->ID;

    wp_update_post([
        'ID' => $id,
        'post_content' => '<p>Field Theory helped translate complex healthcare information into a more approachable digital experience, with attention to mobile access, content clarity, and user confidence.</p>
<ul>
<li>119K Enrollments in 2023 and 2024</li>
<li>47K Enrollments in 2023</li>
<li>72K Enrollments in 2024</li>
<li>110% Increase in Total Keywords (+10K New Keywords)</li>
<li>30% Increase in Direct Traffic</li>
<li>142% Increase in Branded Keywords</li>
<li>5 Point Increase in Domain Authority Score (DAS)</li>
</ul>',
    ]);

    $images = [
        ['assets/images/portfolio/BeWell_laptop_system.png',      'BeWell NM laptop system view',        'bewell-laptop-system-2844'],
        ['assets/images/portfolio/BeWell_laptop_desk.png',        'BeWell NM laptop desk view',          'bewell-desktop-2844'],
        ['assets/images/portfolio/BeWell_mobile_home.png',        'BeWell NM mobile home screen',        'bewell-mobile-home-2844'],
        ['assets/images/portfolio/BeWell_mobile_where_to_start.png','BeWell NM mobile where to start',  'bewell-mobile-where-2844'],
        ['assets/images/portfolio/BeWell_mobile_schedule.png',    'BeWell NM mobile schedule screen',    'bewell-mobile-schedule-2844'],
        ['assets/images/portfolio/BeWell_mobile_menu.png',        'BeWell NM mobile menu screen',        'bewell-mobile-menu-2844'],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2844', $asset_key);
        if($att_id) $attachment_ids[] = $att_id;
    }

    if($attachment_ids){
        set_post_thumbnail($id, $attachment_ids[0]);
        update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    }

    update_post_meta($id, '_ftc_results', "119K Enrollments in 2023 and 2024 (47K in 2023, 72K in 2024)\n110% Increase in Total Keywords (+10K New Keywords)\n30% Increase in Direct Traffic\n142% Increase in Branded Keywords\n5 Point Increase in Domain Authority Score (DAS)");
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_bewell_gallery_2844', 1);
}
add_action('init', 'ftc_migrate_2844_bewell_gallery', 67);
add_action('admin_init', 'ftc_migrate_2844_bewell_gallery', 67);

function ftc_migrate_2844b_bewell_hero(){
    if(get_option('ftc_bewell_hero_2844b')) return;

    $post = get_page_by_path('bewell-new-mexico', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('BeWell New Mexico', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts(['post_type'=>'ftc_portfolio','s'=>'BeWell','posts_per_page'=>1,'post_status'=>'any']);
        if($found) $post = $found[0];
    }
    if(!$post){ update_option('ftc_bewell_hero_2844b', 1); return; }

    $id = $post->ID;

    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_ftc_portfolio_asset_2844',
        'meta_value'     => 'bewell-laptop-system-2844',
    ]);

    if($existing){
        $att_id = absint($existing[0]);
    } else {
        $att_id = ftc_import_plugin_image_attachment(
            'assets/images/portfolio/BeWell_laptop_system.png',
            'BeWell NM laptop system view',
            '_ftc_portfolio_asset_2844',
            'bewell-laptop-system-2844'
        );
    }

    if($att_id){
        set_post_thumbnail($id, $att_id);
        $gallery_ids = array_values(array_filter(array_map('absint', explode(',', (string)get_post_meta($id, '_ftc_gallery_ids', true)))));
        $gallery_ids = array_diff($gallery_ids, [$att_id]);
        array_unshift($gallery_ids, $att_id);
        update_post_meta($id, '_ftc_gallery_ids', implode(',', array_values(array_unique($gallery_ids))));
    }

    update_option('ftc_bewell_hero_2844b', 1);
}
add_action('init', 'ftc_migrate_2844b_bewell_hero', 67);
add_action('admin_init', 'ftc_migrate_2844b_bewell_hero', 67);

function ftc_migrate_2845_bioaffinity_gallery(){
    if(get_option('ftc_bioaffinity_gallery_2845')) return;

    $post = get_page_by_path('bioaffinity-technologies', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('BioAffinity Technologies', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts(['post_type'=>'ftc_portfolio','s'=>'BioAffinity','posts_per_page'=>1,'post_status'=>'any']);
        if($found) $post = $found[0];
    }

    if(!$post){
        $id = wp_insert_post([
            'post_type'    => 'ftc_portfolio',
            'post_status'  => 'publish',
            'post_title'   => 'BioAffinity Technologies',
            'post_name'    => 'bioaffinity-technologies',
            'post_excerpt' => 'Science-driven digital presence for lung cancer detection technology.',
            'post_content' => '<p>Field Theory supported BioAffinity Technologies with digital presence, content strategy, and web development focused on communicating complex science to multiple audiences — from clinicians and researchers to investors and the public.</p>',
            'menu_order'   => 15,
        ]);
    } else {
        $id = $post->ID;
    }

    if(!$id){ update_option('ftc_bioaffinity_gallery_2845', 1); return; }

    $images = [
        ['assets/images/portfolio/BioAffinity_home.png',        'BioAffinity Technologies homepage',          'bioaffinity-home-2845'],
        ['assets/images/portfolio/BioAffinity_about.png',       'BioAffinity Technologies about page',        'bioaffinity-about-2845'],
        ['assets/images/portfolio/BioAffinity_news.png',        'BioAffinity Technologies news and results',  'bioaffinity-news-2845'],
        ['assets/images/portfolio/BioAffinity_mobile_menu.png', 'BioAffinity Technologies mobile menu',       'bioaffinity-mobile-2845'],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2845', $asset_key);
        if($att_id) $attachment_ids[] = $att_id;
    }

    if($attachment_ids){
        set_post_thumbnail($id, $attachment_ids[0]);
        update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    }

    update_post_meta($id, '_ftc_industry', 'Healthcare / Biotechnology');
    update_post_meta($id, '_ftc_results', 'Science-driven web presence, multi-audience content strategy, and digital communications for a lung cancer detection biotech.');
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_bioaffinity_gallery_2845', 1);
}
add_action('init', 'ftc_migrate_2845_bioaffinity_gallery', 68);
add_action('admin_init', 'ftc_migrate_2845_bioaffinity_gallery', 68);

function ftc_migrate_2846_pnm_gallery(){
    if(get_option('ftc_pnm_gallery_2846')) return;

    $post = get_page_by_path('pnm', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('PNM', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts(['post_type'=>'ftc_portfolio','s'=>'PNM','posts_per_page'=>1,'post_status'=>'any']);
        if($found) $post = $found[0];
    }

    if(!$post){ update_option('ftc_pnm_gallery_2846', 1); return; }

    $id = $post->ID;

    $images = [
        ['assets/images/portfolio/PNM_thumbnail.png',     'PNM iMac mockup thumbnail',          'pnm-thumbnail-2846'],
        ['assets/images/portfolio/PNM_desktop_dark.png',  'PNM website desktop dark view',      'pnm-desktop-dark-2846'],
        ['assets/images/portfolio/PNM_desktop_laptop.png','PNM website desktop laptop view',    'pnm-desktop-laptop-2846'],
        ['assets/images/portfolio/PNM_desktop_imac.png',  'PNM website desktop iMac view',      'pnm-desktop-imac-2846'],
        ['assets/images/portfolio/PNM_homepage_full.png', 'PNM homepage full-page screenshot',  'pnm-homepage-full-2846'],
        ['assets/images/portfolio/PNM_about_full.png',    'PNM about page full-page screenshot', 'pnm-about-full-2846'],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2846', $asset_key);
        if($att_id) $attachment_ids[] = $att_id;
    }

    if($attachment_ids){
        set_post_thumbnail($id, $attachment_ids[0]);
        update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    }

    update_post_meta($id, '_ftc_results', 'Improved customer-facing digital experience, clearer service journeys, and stronger energy information presentation for New Mexico\'s largest utility.');
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_pnm_gallery_2846', 1);
}
add_action('init', 'ftc_migrate_2846_pnm_gallery', 69);
add_action('admin_init', 'ftc_migrate_2846_pnm_gallery', 69);

function ftc_migrate_2847_doctorwise(){
    if(get_option('ftc_doctorwise_2847')) return;

    $post = get_page_by_path('doctor-wise-by-hylands', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title("Doctor Wise by Hyland's", OBJECT, 'ftc_portfolio');

    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => "Doctor Wise by Hyland's",
        'post_name'    => 'doctor-wise-by-hylands',
        'post_excerpt' => 'Custom ecommerce, Shopify, CRO, digital marketing, and analytics for a natural menopause wellness brand.',
        'post_content' => '<p>Field Theory partnered with Hyland\'s to launch Doctor Wise — a natural homeopathic menopause wellness brand — with a fully custom Shopify ecommerce experience built for conversion, clarity, and growth.</p><p>Our work spanned the full digital stack: custom Shopify implementation, ecommerce UX and conversion rate optimization, digital marketing strategy and execution, fulfillment integration, and a complete analytics foundation to measure performance and guide decisions.</p><ul><li>Custom Shopify Ecommerce Development</li><li>Conversion Rate Optimization (CRO)</li><li>Digital Marketing Strategy &amp; Execution</li><li>Fulfillment Integration</li><li>Analytics &amp; Performance Measurement</li></ul>',
        'menu_order'   => 5,
    ];

    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }

    if(!$id || is_wp_error($id)){ update_option('ftc_doctorwise_2847', 1); return; }

    $images = [
        ['assets/images/portfolio/DrWise_featured.png',    'Doctor Wise by Hyland\'s featured hero image',   'drwise-featured-2847',    true],
        ['assets/images/portfolio/DrWise_testimonial.png', 'Doctor Wise by Hyland\'s testimonial screenshot','drwise-testimonial-2847', false],
        ['assets/images/portfolio/DrWise_articles.png',    'Doctor Wise by Hyland\'s articles screenshot',   'drwise-articles-2847',    false],
        ['assets/images/portfolio/DrWise_products.png',    'Doctor Wise by Hyland\'s products screenshot',   'drwise-products-2847',    false],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2847', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) set_post_thumbnail($id, $att_id);
        }
    }

    update_post_meta($id, '_ftc_industry', 'Ecommerce / Health & Wellness');
    update_post_meta($id, '_ftc_results', 'Full-stack Shopify launch, conversion-optimized ecommerce, digital marketing, fulfillment integration, and analytics for a national wellness brand available at CVS, Amazon, and Pharmaca.');
    if($attachment_ids) update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_doctorwise_2847', 1);
}
add_action('init', 'ftc_migrate_2847_doctorwise', 70);
add_action('admin_init', 'ftc_migrate_2847_doctorwise', 70);

// ── Enhanced Wellness ─────────────────────────────────────────────────────────
function ftc_migrate_2848_enhanced_wellness(){
    if(get_option('ftc_enhanced_wellness_2848')) return;

    $post = get_page_by_path('enhanced-wellness', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Enhanced Wellness', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts(['post_type'=>'ftc_portfolio','s'=>'Enhanced Wellness','posts_per_page'=>1,'post_status'=>'any']);
        if($found) $post = $found[0];
    }

    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => 'Enhanced Wellness',
        'post_name'    => 'enhanced-wellness',
        'post_excerpt' => 'Health, vitality, longevity and performance — a full-service wellness website tailored to patient needs.',
        'post_content' => '<p>Field Theory built a comprehensive digital presence for Enhanced Wellness, a personalized integrative medicine and wellness clinic in Albuquerque, NM. The site combines bold visual storytelling with clear service navigation across regenerative therapies, functional medicine, bio-optimization, IV nutrition, and aesthetic services.</p><ul><li>Website Design &amp; Development</li><li>Mobile-First UX</li><li>Service Content Architecture</li><li>Patient Journey Optimization</li><li>Digital Marketing</li></ul>',
        'menu_order'   => 6,
    ];

    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }

    if(!$id || is_wp_error($id)){ update_option('ftc_enhanced_wellness_2848', 1); return; }

    $images = [
        ['assets/images/portfolio/EnhancedWellness_featured.png',         'Enhanced Wellness featured hero image',         'ew-featured-2848',          true],
        ['assets/images/portfolio/EnhancedWellness_desktop.png',          'Enhanced Wellness desktop homepage screenshot',  'ew-desktop-2848',           false],
        ['assets/images/portfolio/EnhancedWellness_mobile_home.png',      'Enhanced Wellness mobile homepage screenshot',   'ew-mobile-home-2848',       false],
        ['assets/images/portfolio/EnhancedWellness_mobile_services.png',  'Enhanced Wellness mobile services screenshot',   'ew-mobile-services-2848',   false],
        ['assets/images/portfolio/EnhancedWellness_acupuncture.png',      'Enhanced Wellness acupuncture service page',     'ew-acupuncture-2848',       false],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2848', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) set_post_thumbnail($id, $att_id);
        }
    }

    update_post_meta($id, '_ftc_industry', 'Healthcare / Wellness');
    update_post_meta($id, '_ftc_results', 'A comprehensive integrative wellness website with mobile-first design, clear service architecture, and patient-centered navigation.');
    if($attachment_ids) update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_enhanced_wellness_2848', 1);
}
add_action('init', 'ftc_migrate_2848_enhanced_wellness', 71);
add_action('admin_init', 'ftc_migrate_2848_enhanced_wellness', 71);

// ── New Mexico Partnership ─────────────────────────────────────────────────────
function ftc_migrate_2849_nm_partnership(){
    if(get_option('ftc_nm_partnership_2849')) return;

    $post = get_page_by_path('new-mexico-partnership', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('New Mexico Partnership', OBJECT, 'ftc_portfolio');

    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => 'New Mexico Partnership',
        'post_name'    => 'new-mexico-partnership',
        'post_excerpt' => 'A bold multi-device economic development platform positioning New Mexico as a premier destination for business and innovation.',
        'post_content' => '<p>Field Theory helped build the digital platform for the New Mexico Partnership — a statewide economic development organization attracting businesses, entrepreneurs, and investment to New Mexico.</p><p>The site combines compelling visual storytelling with data-rich content to help businesses discover why New Mexico is the right place to grow.</p><ul><li>Website Design &amp; Development</li><li>Economic Development Content Strategy</li><li>Multi-Device UX</li><li>News &amp; Media Integration</li></ul>',
        'menu_order'   => 7,
    ];

    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }

    if(!$id || is_wp_error($id)){ update_option('ftc_nm_partnership_2849', 1); return; }

    $images = [
        ['assets/images/portfolio/NMPartnership_featured.png', 'New Mexico Partnership multi-device mockup hero', 'nmp-featured-2849', true],
        ['assets/images/portfolio/NMPartnership_thumb.png',    'New Mexico Partnership thumbnail',                'nmp-thumb-2849',    false],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2849', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) set_post_thumbnail($id, $att_id);
        }
    }

    update_post_meta($id, '_ftc_industry', 'Economic Development');
    update_post_meta($id, '_ftc_results', 'A compelling multi-device platform showcasing New Mexico as a destination for business innovation and economic growth.');
    if($attachment_ids) update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_nm_partnership_2849', 1);
}
add_action('init', 'ftc_migrate_2849_nm_partnership', 72);
add_action('admin_init', 'ftc_migrate_2849_nm_partnership', 72);

function ftc_migrate_2850_pnm_mobile(){
    if(get_option('ftc_pnm_mobile_2850')) return;

    $post = get_page_by_path('pnm', OBJECT, 'ftc_portfolio');
    if(!$post){
        $results = get_posts(['post_type'=>'ftc_portfolio','post_status'=>'publish','posts_per_page'=>1,'s'=>'PNM']);
        $post = $results[0] ?? null;
    }

    if(!$post || is_wp_error($post)){ update_option('ftc_pnm_mobile_2850', 1); return; }

    $id = $post->ID;

    $att_id = ftc_import_plugin_image_attachment(
        'assets/images/portfolio/PNM_mobile_mockup.png',
        'PNM mobile mockup',
        '_ftc_portfolio_asset_2850',
        'pnm-mobile-mockup-2850'
    );

    $existing = array_filter(array_map('absint', explode(',', (string)get_post_meta($id, '_ftc_gallery_ids', true))));
    if($att_id && !in_array($att_id, $existing)){
        $existing[] = $att_id;
        update_post_meta($id, '_ftc_gallery_ids', implode(',', array_values($existing)));
    }

    update_option('ftc_pnm_mobile_2850', 1);
}
add_action('init', 'ftc_migrate_2850_pnm_mobile', 73);
add_action('admin_init', 'ftc_migrate_2850_pnm_mobile', 73);

function ftc_migrate_2851_lets_plant(){
    if(get_option('ftc_lets_plant_2851')) return;

    $post = get_page_by_path('lets-plant-albuquerque', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title("Let's Plant Albuquerque", OBJECT, 'ftc_portfolio');
    if(!$post){
        $posts = get_posts(['post_type'=>'ftc_portfolio','s'=>"Let's Plant",'posts_per_page'=>1,'post_status'=>'any']);
        $post = $posts[0] ?? null;
    }
    if(!$post){ update_option('ftc_lets_plant_2851', 1); return; }

    $id = $post->ID;
    $attachment_ids = [];

    $featured_id = ftc_import_plugin_image_attachment(
        'assets/images/portfolio/LetsPlant_featured.png',
        "Let's Plant Albuquerque featured homepage",
        '_ftc_portfolio_asset_2851',
        'letsplant-featured-2851'
    );
    if($featured_id){
        $attachment_ids[] = $featured_id;
        set_post_thumbnail($id, $featured_id);
    }

    $dashboard_id = ftc_import_plugin_image_attachment(
        'assets/images/portfolio/LetsPlant_data_dashboard.png',
        "Let's Plant Albuquerque data dashboard",
        '_ftc_portfolio_asset_2851',
        'letsplant-dashboard-2851'
    );
    if($dashboard_id) $attachment_ids[] = $dashboard_id;

    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    if($attachment_ids){
        update_post_meta($id, '_ftc_gallery_ids', implode(',', array_values(array_unique(array_map('absint', $attachment_ids)))));
    }

    update_post_meta($id, '_ftc_featured', '1');
    update_option('ftc_lets_plant_2851', 1);
}
add_action('init', 'ftc_migrate_2851_lets_plant', 74);
add_action('admin_init', 'ftc_migrate_2851_lets_plant', 74);

function ftc_migrate_2852_rodgers_co(){
    if(get_option('ftc_rodgers_co_2852')) return;

    $post = get_page_by_path('rodgers-co', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Rodgers & Co.', OBJECT, 'ftc_portfolio');
    if(!$post){ update_option('ftc_rodgers_co_2852', 1); return; }

    $id = $post->ID;

    wp_update_post([
        'ID'           => $id,
        'post_content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for Rodgers &amp; Co. — from visual design and content architecture through custom functionality, animations, and launch support.</p><p>Our work covered the complete build: responsive website design, interactive timeline storytelling, service navigation, and polished motion and UI details that bring the brand to life online.</p><ul><li>Website Design &amp; Development</li><li>Custom Functionality &amp; Animations</li><li>Interactive Timeline &amp; Storytelling</li><li>Mobile-First UX</li><li>Agency Partnership Delivery</li></ul>',
    ]);

    $images = [
        ['assets/images/portfolio/RodgersCo_featured.png', 'Rodgers & Co. featured hero', 'rodgers-featured-2852', true],
        ['assets/images/portfolio/RodgersCo_our_story.png', 'Rodgers & Co. our story', 'rodgers-story-2852', false],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2852', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) set_post_thumbnail($id, $att_id);
        }
    }

    if($attachment_ids) update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_rodgers_co_2852', 1);
}
add_action('init', 'ftc_migrate_2852_rodgers_co', 75);
add_action('admin_init', 'ftc_migrate_2852_rodgers_co', 75);

function ftc_migrate_2853_agency_partnership_copy(){
    if(get_option('ftc_agency_partnership_copy_2853')) return;

    $projects = [
        'amy-biehl-high-school' => [
            'title'   => 'Amy Biehl High School',
            'content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for Amy Biehl High School — including custom functionality, animations, and polished interactive details.</p><p>Our work produced a bold, mobile-first school website with strong visual storytelling, fast navigation, and clearer access to academic information, resources, and enrollment pathways.</p>',
        ],
        'the-education-plan' => [
            'title'   => 'The Education Plan',
            'content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for The Education Plan — including custom functionality, animations, and polished interactive details.</p><p>Our work supported education-focused digital communication with cleaner mobile presentation, clearer program messaging, and easier pathways for families to understand next steps.</p>',
        ],
        'pnm' => [
            'title'   => 'PNM',
            'content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for PNM — including custom functionality, animations, and polished interactive details.</p><p>Our work improved customer-facing digital presentation, clearer service journeys, and stronger energy information pathways for New Mexico\'s largest utility.</p>',
        ],
        'aztec-mechanical' => [
            'title'   => 'Aztec Mechanical',
            'content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for Aztec Mechanical — including custom functionality, animations, and polished interactive details.</p><p>Our work focused on service clarity, credibility, and a more polished digital presence that makes the company easier to understand online.</p>',
        ],
        'myschoolsabq' => [
            'title'   => 'MySchoolsABQ',
            'content' => '<p>Field Theory partnered with a local agency to design, develop, implement, and deliver the full digital experience for MySchoolsABQ — including custom functionality, animations, and polished interactive details.</p><p>Our work created a school discovery experience focused on clarity, trust, and easier public navigation, making education options easier for families to compare and understand.</p>',
        ],
    ];

    foreach($projects as $slug => $project){
        $post = get_page_by_path($slug, OBJECT, 'ftc_portfolio');
        if(!$post) $post = get_page_by_title($project['title'], OBJECT, 'ftc_portfolio');
        if(!$post) continue;

        wp_update_post(['ID' => $post->ID, 'post_content' => $project['content']]);
    }

    update_option('ftc_agency_partnership_copy_2853', 1);
}
add_action('init', 'ftc_migrate_2853_agency_partnership_copy', 75);
add_action('admin_init', 'ftc_migrate_2853_agency_partnership_copy', 75);

function ftc_migrate_2854_data_service_image(){
    /* Retired: catalog SVGs belong on service cards only; detail pages use WebGL visuals. */
    if(!get_option('ftc_data_service_image_2854')){
        update_option('ftc_data_service_image_2854', 1);
    }
}
add_action('init', 'ftc_migrate_2854_data_service_image', 76);
add_action('admin_init', 'ftc_migrate_2854_data_service_image', 76);

function ftc_migrate_2856_five_service_categories(){
    if(get_option('ftc_five_service_categories_2856')) return;

    $data_posts = get_posts([
        'post_type' => 'ftc_service',
        'name' => 'data-analysis-visualization',
        'posts_per_page' => 1,
        'post_status' => 'any',
    ]);
    if($data_posts){
        wp_update_post(['ID' => $data_posts[0]->ID, 'post_status' => 'draft']);
    }

    $order = [
        'website-development-core-tech' => 0,
        'ecommerce-conversion-rate-optimization-cro' => 1,
        'search-discovery-optimization-seo-aeo' => 2,
        'digital-marketing-growth-strategy' => 3,
        'creative-technology-innovation' => 4,
    ];
    foreach($order as $slug => $menu_order){
        $posts = get_posts([
            'post_type' => 'ftc_service',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        if($posts){
            wp_update_post([
                'ID' => $posts[0]->ID,
                'menu_order' => $menu_order,
                'post_status' => 'publish',
            ]);
        }
    }

    update_option('ftc_five_service_categories_2856', 1);
}
add_action('init', 'ftc_migrate_2856_five_service_categories', 77);
add_action('admin_init', 'ftc_migrate_2856_five_service_categories', 77);

function ftc_migrate_2857_restore_six_service_categories(){
    if(get_option('ftc_six_service_categories_2857')) return;

    $order = [
        'website-development-core-tech' => 0,
        'ecommerce-conversion-rate-optimization-cro' => 1,
        'search-discovery-optimization-seo-aeo' => 2,
        'digital-marketing-growth-strategy' => 3,
        'creative-technology-innovation' => 4,
        'data-analysis-visualization' => 5,
    ];
    foreach($order as $slug => $menu_order){
        $posts = get_posts([
            'post_type' => 'ftc_service',
            'name' => $slug,
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);
        if($posts){
            wp_update_post([
                'ID' => $posts[0]->ID,
                'menu_order' => $menu_order,
                'post_status' => 'publish',
            ]);
        }
    }

    $data_live = get_posts([
        'post_type' => 'ftc_service',
        'name' => 'data-analysis-visualization',
        'posts_per_page' => 1,
        'post_status' => 'publish',
    ]);
    if($data_live){
        update_option('ftc_six_service_categories_2857', 1);
    }
}
add_action('init', 'ftc_migrate_2857_restore_six_service_categories', 78);
add_action('admin_init', 'ftc_migrate_2857_restore_six_service_categories', 78);

function ftc_migrate_2858_ensure_data_service_catalog(){
    if(get_option('ftc_data_service_catalog_2858')) return;

    $canonical = get_page_by_path('data-analysis-visualization', OBJECT, 'ftc_service');
    if(!$canonical){
        $matches = get_posts([
            'post_type' => 'ftc_service',
            'post_status' => 'any',
            'posts_per_page' => -1,
            's' => 'Data, Analysis & Visualization',
        ]);
        foreach($matches as $candidate){
            if(strpos($candidate->post_name, 'data-analysis-visualization') === 0){
                $canonical = $candidate;
                break;
            }
        }
    }

    if(!$canonical){
        foreach(ftc_service_catalog_2621() as $svc){
            if($svc['slug'] !== 'data-analysis-visualization') continue;
            $id = wp_insert_post([
                'post_type' => 'ftc_service',
                'post_status' => 'publish',
                'post_title' => $svc['title'],
                'post_name' => $svc['slug'],
                'post_excerpt' => $svc['excerpt'],
                'post_content' => $svc['content'],
                'menu_order' => 5,
            ]);
            if($id && !is_wp_error($id)){
                update_post_meta($id, '_ftc_service_eyebrow', $svc['eyebrow']);
                update_post_meta($id, '_ftc_service_image', $svc['image']);
                update_post_meta($id, '_ftc_service_tasks', implode("\n", $svc['tasks']));
                update_post_meta($id, '_ftc_featured', '1');
                $canonical = get_post($id);
            }
            break;
        }
    } elseif(get_post_status($canonical->ID) !== 'publish'){
        wp_update_post([
            'ID' => $canonical->ID,
            'post_status' => 'publish',
            'post_name' => 'data-analysis-visualization',
            'menu_order' => 5,
        ]);
    }

    if($canonical){
        $dupes = get_posts([
            'post_type' => 'ftc_service',
            'post_status' => 'any',
            'posts_per_page' => -1,
        ]);
        foreach($dupes as $post){
            if((int)$post->ID === (int)$canonical->ID) continue;
            if(strpos($post->post_name, 'data-analysis-visualization') === 0
                || stripos($post->post_title, 'Data, Analysis & Visualization') !== false){
                wp_trash_post($post->ID);
            }
        }
    }

    $order = [
        'website-development-core-tech' => 0,
        'ecommerce-conversion-rate-optimization-cro' => 1,
        'search-discovery-optimization-seo-aeo' => 2,
        'digital-marketing-growth-strategy' => 3,
        'creative-technology-innovation' => 4,
        'data-analysis-visualization' => 5,
    ];
    foreach($order as $slug => $menu_order){
        $post = get_page_by_path($slug, OBJECT, 'ftc_service');
        if(!$post) continue;
        $update = [
            'ID' => $post->ID,
            'menu_order' => $menu_order,
        ];
        if($slug === 'data-analysis-visualization' && get_post_status($post->ID) !== 'publish'){
            $update['post_status'] = 'publish';
        }
        wp_update_post($update);
    }

    $data_live = get_page_by_path('data-analysis-visualization', OBJECT, 'ftc_service');
    if($data_live && $data_live->post_status === 'publish'){
        update_option('ftc_data_service_catalog_2858', 1);
    }
}
add_action('init', 'ftc_migrate_2858_ensure_data_service_catalog', 79);
add_action('admin_init', 'ftc_migrate_2858_ensure_data_service_catalog', 79);

function ftc_migrate_2855_lynn_scholarship(){
    if(get_option('ftc_lynn_scholarship_2855')) return;

    $post = get_page_by_path('lt-col-cecil-lynn-jr-memorial-scholarship', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_path('lynn-scholarship', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Lt. Col. Cecil Lynn Jr. Memorial Scholarship', OBJECT, 'ftc_portfolio');

    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => 'Lt. Col. Cecil Lynn Jr. Memorial Scholarship',
        'post_name'    => 'lt-col-cecil-lynn-jr-memorial-scholarship',
        'post_excerpt' => 'A memorial scholarship website honoring a New Mexico legacy — empowering student-athletes through academics, athletics, and community support.',
        'post_content' => '<p>Field Theory designed and built the digital home for the Lt. Col. Cecil Lynn Jr. Memorial Scholarship — a tribute site that celebrates grit, leadership, and the next generation of New Mexico student-athletes.</p><p>The experience combines bold hero storytelling, family legacy photography, and clear pathways to learn about Cecil Lynn Jr.\'s impact, explore scholarship criteria, and donate to support students pursuing excellence in academics and athletics.</p><ul><li>Memorial Scholarship Brand &amp; Storytelling</li><li>Responsive Website Design &amp; Development</li><li>Donation &amp; Support Pathways</li><li>Mobile-First Hero Experience</li><li>Legacy Photography Integration</li></ul><p><a href="https://lynnscholarship.com/" target="_blank" rel="noopener noreferrer">Visit lynnscholarship.com</a></p>',
        'menu_order'   => 12,
    ];

    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }

    if(!$id || is_wp_error($id)){ update_option('ftc_lynn_scholarship_2855', 1); return; }

    $images = [
        ['assets/images/portfolio/LynnScholarship_featured.png', 'Lt. Col. Cecil Lynn Jr. Memorial Scholarship featured hero', 'lynn-featured-2855', true],
        ['assets/images/portfolio/LynnScholarship_mobile.png',   'Lynn Scholarship mobile hero — Empower Dreams. Celebrate Grit.', 'lynn-mobile-2855', false],
        ['assets/images/portfolio/LynnScholarship_desktop.png',  'Lynn Scholarship desktop with Polaroid legacy photo', 'lynn-desktop-2855', false],
    ];

    $attachment_ids = [];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2855', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) set_post_thumbnail($id, $att_id);
        }
    }

    update_post_meta($id, '_ftc_industry', 'Education / Memorial Scholarship');
    update_post_meta($id, '_ftc_project_url', 'https://lynnscholarship.com/');
    update_post_meta($id, '_ftc_results', 'A heartfelt memorial scholarship platform that honors legacy, inspires student-athletes, and makes donating easy.');
    if($attachment_ids) update_post_meta($id, '_ftc_gallery_ids', implode(',', $attachment_ids));
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_lynn_scholarship_2855', 1);
}
add_action('init', 'ftc_migrate_2855_lynn_scholarship', 77);
add_action('admin_init', 'ftc_migrate_2855_lynn_scholarship', 77);

function ftc_migrate_2854_sky_ute_casino(){
    if(get_option('ftc_sky_ute_casino_2854')) return;

    $post = get_page_by_path('sky-ute-casino-and-resort', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Sky Ute Casino and Resort', OBJECT, 'ftc_portfolio');

    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => 'Sky Ute Casino and Resort',
        'post_name'    => 'sky-ute-casino-and-resort',
        'post_excerpt' => 'A full-service casino and resort website with gaming, dining, entertainment, and hospitality content.',
        'post_content' => '<p>Field Theory designed and developed the digital presence for Sky Ute Casino and Resort — a premier gaming and hospitality destination in Ignacio, Colorado.</p><p>The website brings together gaming, dining, entertainment, events, and resort amenities into a clear, engaging experience that helps guests discover what the property offers and plan their visit.</p><ul><li>Website Design &amp; Development</li><li>Resort &amp; Casino Content Architecture</li><li>Events &amp; Entertainment Integration</li><li>Mobile-First UX</li><li>Hospitality &amp; Gaming Navigation</li></ul>',
        'menu_order'   => 8,
    ];

    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }

    if(!$id || is_wp_error($id)){ update_option('ftc_sky_ute_casino_2854', 1); return; }

    update_post_meta($id, '_ftc_industry', 'Gaming / Hospitality');
    update_post_meta($id, '_ftc_project_url', 'http://skyutecasino.com/');
    update_post_meta($id, '_ftc_results', 'A polished casino and resort website with clearer guest pathways, stronger hospitality storytelling, and easier access to gaming, dining, and entertainment information.');
    update_post_meta($id, '_ftc_featured', '1');
    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');

    update_option('ftc_sky_ute_casino_2854', 1);
}
add_action('init', 'ftc_migrate_2854_sky_ute_casino', 76);
add_action('admin_init', 'ftc_migrate_2854_sky_ute_casino', 76);

function ftc_get_sky_ute_portfolio_post(){
    $post = get_page_by_path('sky-ute-casino-and-resort', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Sky Ute Casino and Resort', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts([
            'post_type'      => 'ftc_portfolio',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            's'              => 'Sky Ute Casino',
        ]);
        if($found) $post = $found[0];
    }
    return $post;
}

function ftc_sky_ute_portfolio_image_defs(){
    return [
        ['assets/images/portfolio/SkyUte_featured.jpg', 'Sky Ute Casino and Resort — Where the Winning is Easy', 'skyute-featured-2026', true],
        ['assets/images/portfolio/SkyUte_hotel.png',      'Sky Ute Casino and Resort — Hotel & Resort',          'skyute-hotel-2026',  false],
        ['assets/images/portfolio/SkyUte_dining.jpg',     'Sky Ute Casino and Resort — Relax and Play',          'skyute-dining-2026', false],
        ['assets/images/portfolio/SkyUte_events.jpg',     'Sky Ute Casino and Resort — Weddings & Events',       'skyute-events-2026', false],
    ];
}

function ftc_url_is_sky_ute_portfolio_asset($url){
    return stripos((string)$url, 'skyute') !== false;
}

function ftc_attachment_is_sky_ute_portfolio_asset($attachment_id){
    $attachment_id = absint($attachment_id);
    if(!$attachment_id) return false;

    $asset_key = get_post_meta($attachment_id, '_ftc_portfolio_asset_2854', true);
    if(is_string($asset_key) && strpos($asset_key, 'skyute-') === 0) return true;

    $url = wp_get_attachment_url($attachment_id);
    return $url && ftc_url_is_sky_ute_portfolio_asset($url);
}

function ftc_sky_ute_portfolio_gallery_is_current($post_id){
    $post_id = absint($post_id);
    if(!$post_id) return false;

    $expected = count(ftc_sky_ute_portfolio_image_defs());
    if($expected < 1) return false;

    $gallery_urls = ftc_portfolio_gallery_image_urls($post_id, 'large', false);
    if(count($gallery_urls) < $expected) return false;

    $skyute_count = 0;
    foreach($gallery_urls as $url){
        if(ftc_is_placeholder_image_url($url)) return false;
        if(ftc_url_is_sky_ute_portfolio_asset($url)) $skyute_count++;
        else return false;
    }
    if($skyute_count < $expected) return false;

    if(has_post_thumbnail($post_id)){
        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = get_the_post_thumbnail_url($post_id, 'large');
        if(ftc_is_placeholder_image_url($thumb_url)) return false;
        if($thumb_id && !ftc_attachment_is_sky_ute_portfolio_asset($thumb_id) && !ftc_url_is_sky_ute_portfolio_asset($thumb_url)) return false;
    }

    return true;
}

function ftc_sky_ute_portfolio_has_gallery($post_id){
    $post_id = absint($post_id);
    if(!$post_id) return false;

    $ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($post_id, '_ftc_gallery_ids', true))));
    foreach($ids as $attachment_id){
        if(wp_get_attachment_url($attachment_id)) return true;
    }

    foreach(array_filter(array_map('trim', explode("\n", (string)get_post_meta($post_id, '_ftc_gallery_urls', true)))) as $url){
        if($url && !ftc_is_placeholder_image_url($url)) return true;
    }

    if(has_post_thumbnail($post_id)){
        $thumb = get_the_post_thumbnail_url($post_id, 'large');
        if($thumb && !ftc_is_placeholder_image_url($thumb)) return true;
    }

    return false;
}

function ftc_sky_ute_portfolio_should_skip_auto_gallery($post_id, $force = false){
    $post_id = absint($post_id);
    if(!$post_id || $force) return false;

    if(get_post_meta($post_id, '_ftc_gallery_manual', true)) return true;
    if(ftc_sky_ute_portfolio_gallery_is_current($post_id)) return true;

    $ids = array_filter(array_map('absint', explode(',', (string)get_post_meta($post_id, '_ftc_gallery_ids', true))));
    foreach($ids as $attachment_id){
        $url = wp_get_attachment_url($attachment_id);
        if(!$url) continue;
        if(ftc_is_placeholder_image_url($url)) return false;
        if(!ftc_attachment_is_sky_ute_portfolio_asset($attachment_id)) return true;
    }

    foreach(array_filter(array_map('trim', explode("\n", (string)get_post_meta($post_id, '_ftc_gallery_urls', true)))) as $url){
        if(ftc_is_placeholder_image_url($url)) return false;
        if(!ftc_url_is_sky_ute_portfolio_asset($url)) return true;
    }

    if(has_post_thumbnail($post_id)){
        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = get_the_post_thumbnail_url($post_id, 'large');
        if($thumb_url && !ftc_is_placeholder_image_url($thumb_url)){
            if($thumb_id && !ftc_attachment_is_sky_ute_portfolio_asset($thumb_id) && !ftc_url_is_sky_ute_portfolio_asset($thumb_url)) return true;
        }
    }

    return false;
}

function ftc_apply_sky_ute_portfolio_gallery($post_id){
    $post_id = absint($post_id);
    if(!$post_id) return false;

    $attachment_ids = [];
    $gallery_urls = [];
    $featured_id = 0;

    foreach(ftc_sky_ute_portfolio_image_defs() as [$relative_path, $title, $asset_key, $is_featured]){
        $att_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_2854', $asset_key);
        if($att_id){
            $attachment_ids[] = $att_id;
            if($is_featured) $featured_id = $att_id;
            continue;
        }

        $source = FTC_PATH.ltrim($relative_path, '/');
        if(file_exists($source)){
            $gallery_urls[] = FTC_URL.ltrim($relative_path, '/');
        }
    }

    delete_post_meta($post_id, '_ftc_allow_placeholder_image');

    if($attachment_ids){
        update_post_meta($post_id, '_ftc_gallery_ids', implode(',', array_values(array_unique($attachment_ids))));
    } else {
        delete_post_meta($post_id, '_ftc_gallery_ids');
    }

    if($gallery_urls){
        update_post_meta($post_id, '_ftc_gallery_urls', implode("\n", array_values(array_unique($gallery_urls))));
    } else {
        delete_post_meta($post_id, '_ftc_gallery_urls');
    }

    if(is_int($featured_id) && $featured_id){
        set_post_thumbnail($post_id, $featured_id);
    } elseif($attachment_ids){
        set_post_thumbnail($post_id, $attachment_ids[0]);
    } elseif($gallery_urls){
        delete_post_meta($post_id, '_thumbnail_id');
    } elseif(has_post_thumbnail($post_id)){
        $thumb = get_the_post_thumbnail_url($post_id, 'large');
        if(!$thumb || ftc_is_placeholder_image_url($thumb)) delete_post_meta($post_id, '_thumbnail_id');
    }

    return ftc_sky_ute_portfolio_gallery_is_current($post_id);
}

function ftc_migrate_2854_sky_ute_casino_images($force = false){
    $post = ftc_get_sky_ute_portfolio_post();
    if($post && ftc_sky_ute_portfolio_should_skip_auto_gallery($post->ID, $force)){
        if(!get_option('ftc_sky_ute_casino_images_2854')) update_option('ftc_sky_ute_casino_images_2854', 1);
        return;
    }
    if(get_option('ftc_sky_ute_casino_images_2854') && $post && ftc_sky_ute_portfolio_gallery_is_current($post->ID)) return;
    if(!$post) return;

    if(ftc_apply_sky_ute_portfolio_gallery($post->ID)){
        update_option('ftc_sky_ute_casino_images_2854', 1);
        delete_post_meta($post->ID, '_ftc_gallery_manual');
    }
}
add_action('init', 'ftc_migrate_2854_sky_ute_casino_images', 78);
add_action('admin_init', 'ftc_migrate_2854_sky_ute_casino_images', 78);

function ftc_repair_sky_ute_gallery($force = false){
    $post = ftc_get_sky_ute_portfolio_post();
    if(!$post) return;
    if(ftc_sky_ute_portfolio_should_skip_auto_gallery($post->ID, $force)){
        if(!get_option('ftc_sky_ute_casino_images_2854')) update_option('ftc_sky_ute_casino_images_2854', 1);
        return;
    }
    if(get_option('ftc_sky_ute_casino_images_2854') && ftc_sky_ute_portfolio_gallery_is_current($post->ID)) return;

    delete_option('ftc_sky_ute_casino_images_2854');
    ftc_migrate_2854_sky_ute_casino_images($force);
}
add_action('init', 'ftc_repair_sky_ute_gallery', 77);
add_action('admin_init', 'ftc_repair_sky_ute_gallery', 77);

function ftc_migrate_sky_ute_casino_images_refresh_2026(){
    if(get_option('ftc_sky_ute_casino_images_2026')) return;
    $post = ftc_get_sky_ute_portfolio_post();
    if(!$post) return;
    delete_option('ftc_sky_ute_casino_images_2854');
    if(ftc_apply_sky_ute_portfolio_gallery($post->ID)){
        update_option('ftc_sky_ute_casino_images_2854', 1);
        update_option('ftc_sky_ute_casino_images_2026', 1);
    }
}
add_action('init', 'ftc_migrate_sky_ute_casino_images_refresh_2026', 79);
add_action('admin_init', 'ftc_migrate_sky_ute_casino_images_refresh_2026', 79);

function ftc_get_mountain_west_sales_portfolio_post(){
    $post = get_page_by_path('mountain-west-sales', OBJECT, 'ftc_portfolio');
    if(!$post) $post = get_page_by_title('Mountain West Sales', OBJECT, 'ftc_portfolio');
    if(!$post){
        $found = get_posts([
            'post_type'      => 'ftc_portfolio',
            's'              => 'Mountain West Sales',
            'posts_per_page' => 1,
            'post_status'    => 'any',
        ]);
        $post = $found[0] ?? null;
    }
    return $post;
}

function ftc_migrate_mountain_west_sales_2026(){
    if(get_option('ftc_mountain_west_sales_2026')) return;

    $post = ftc_get_mountain_west_sales_portfolio_post();
    $postarr = [
        'post_type'    => 'ftc_portfolio',
        'post_status'  => 'publish',
        'post_title'   => 'Mountain West Sales',
        'post_name'    => 'mountain-west-sales',
        'post_excerpt' => 'Custom Shopify ecommerce with inventory discovery, conversion tracking, and sales-team training.',
        'post_content' => '<p>Field Theory built a custom Shopify experience for Mountain West Sales — a regional hearth and home retailer — focused on product discovery, inventory clarity, and conversion.</p><p>The site combines a tailored storefront, category browsing, and measurable conversion events so the team can understand what shoppers explore, request, and buy.</p><ul><li>Custom Shopify Theme &amp; Storefront</li><li>Inventory &amp; Product Discovery UX</li><li>Conversion Event Tracking</li><li>Category &amp; Collection Architecture</li><li>Sales Team Training &amp; Handoff</li></ul>',
        'menu_order'   => 2,
    ];
    if($post){
        $postarr['ID'] = $post->ID;
        $id = wp_update_post($postarr);
    } else {
        $id = wp_insert_post($postarr);
    }
    if(!$id || is_wp_error($id)){
        update_option('ftc_mountain_west_sales_2026', 1);
        return;
    }

    $attachment_ids = [];
    $images = [
        ['assets/images/portfolio/MountainWestSales_home.png', 'Mountain West Sales homepage', 'mws-home-2026', true],
        ['assets/images/portfolio/MountainWestSales_shop.png', 'Mountain West Sales shop collection', 'mws-shop-2026', false],
    ];
    foreach($images as [$relative_path, $title, $asset_key, $is_featured]){
        if(!file_exists(FTC_PATH.$relative_path)) continue;
        $attachment_id = ftc_import_plugin_image_attachment($relative_path, $title, '_ftc_portfolio_asset_mws', $asset_key);
        if(!$attachment_id) continue;
        $attachment_ids[] = $attachment_id;
        if($is_featured) set_post_thumbnail($id, $attachment_id);
    }

    delete_post_meta($id, '_ftc_allow_placeholder_image');
    delete_post_meta($id, '_ftc_gallery_urls');
    if($attachment_ids){
        update_post_meta($id, '_ftc_gallery_ids', implode(',', array_values(array_unique(array_map('absint', $attachment_ids)))));
    }
    update_post_meta($id, '_ftc_industry', 'Ecommerce / Retail');
    update_post_meta($id, '_ftc_results', 'Custom Shopify storefront, improved product discovery, inventory-focused browsing, conversion event tracking, and team training.');
    update_post_meta($id, '_ftc_featured', '1');

    update_option('ftc_mountain_west_sales_2026', 1);
}
add_action('init', 'ftc_migrate_mountain_west_sales_2026', 80);
add_action('admin_init', 'ftc_migrate_mountain_west_sales_2026', 80);
