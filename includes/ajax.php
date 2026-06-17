<?php
if (!defined('ABSPATH')) exit;


function ftc_find_portfolio_by_search($term){
    $term = trim((string)$term);
    if ($term === '') return null;
    $q = new WP_Query([
        'post_type' => 'ftc_portfolio',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        's' => $term,
        'orderby' => 'relevance',
    ]);
    if($q->have_posts()){
        $q->the_post();
        $id = get_the_ID();
        wp_reset_postdata();
        return $id;
    }
    wp_reset_postdata();
    return null;
}

function ftc_find_service_by_search($term){
    $term = trim((string)$term);
    if ($term === '') return null;
    $q = new WP_Query([
        'post_type' => 'ftc_service',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        's' => $term,
        'orderby' => 'relevance',
    ]);
    if($q->have_posts()){
        $q->the_post();
        $id = get_the_ID();
        wp_reset_postdata();
        return $id;
    }
    wp_reset_postdata();
    return null;
}


function ftc_render_cpt_response_matches($matches, $settings=[]){
    echo '<div class="ftc-response-shell ftc-response-layout-matches">';
    echo '<header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr('I found a few relevant responses.').'">I found a few relevant responses.</h2><div class="ftc-answer-description">Choose the response that best fits what you need.</div></header>';
    echo '<section class="ftc-response-content"><div class="ftc-response-results-grid">';
    foreach($matches as $response){
        $prompt = $response['prompt_label'] ?: $response['title'];
        echo '<article class="ftc-response-result-card"><button type="button" data-prompt="'.esc_attr($prompt).'"><strong>'.esc_html($response['title']).'</strong>';
        if(!empty($response['description'])) echo '<span>'.esc_html($response['description']).'</span>';
        echo '<em>Open response →</em></button></article>';
    }
    echo '</div></section></div>';
}

function ftc_render_cpt_response_shell($response, $settings=[]){
    $title = $response['title'] ?? 'Response';
    $desc = $response['description'] ?? '';
    $type = $response['type'] ?? 'legacy';
    $layout = $response['legacy_layout'] ?? 'none';

    echo '<div class="ftc-response-shell ftc-response-layout-'.esc_attr($layout).' ftc-response-source-cpt ftc-response-type-'.esc_attr($type).'" data-response-title="'.esc_attr($title).'">';
    echo '<header class="ftc-response-header"><div class="ftc-response-title-label">'.esc_html($title).'</div>';
    $typed = $desc ?: $title;
    echo '<h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($typed).'">'.esc_html($typed).'</h2>';
    echo '</header><section class="ftc-response-content">';

    $rendered = false;

    if($type === 'elementor_template' && !empty($response['template_id']) && function_exists('ftc_render_elementor_template_by_id')){
        echo '<div class="ftc-response-elementor-template">'.ftc_render_elementor_template_by_id(absint($response['template_id'])).'</div>';
        $rendered = true;
    }

    if(!$rendered && $type === 'elementor_canvas' && !empty($response['id']) && function_exists('ftc_render_elementor_template_by_id')){
        echo '<div class="ftc-response-elementor-template">'.ftc_render_elementor_template_by_id(absint($response['id'])).'</div>';
        $rendered = true;
    }

    if(!$rendered && $type === 'blocks' && !empty($response['blocks']) && function_exists('ftc_render_response_engine_block')){
        foreach($response['blocks'] as $block) ftc_render_response_engine_block($block);
        $rendered = true;
    }

    if(!$rendered && !empty($response['html'])){
        echo '<div class="ftc-editable-content">'.ftc_render_editable_html($response['html']).'</div>';
        $rendered = true;
    }

    if(!$rendered && $type !== 'prompt_only'){
        switch($layout){
            case 'home': ftc_render_home_panel($settings); break;
            case 'about': ftc_render_about_panel($settings); break;
            case 'portfolio': ftc_render_portfolio_masonry(); break;
            case 'services': ftc_render_services_panel(); break;
            case 'faq': ftc_render_faq_panel(); break;
            case 'contact': ftc_render_contact_panel($settings); break;
            case 'testimonials': ftc_render_testimonials_panel(); break;
        }
    }

    echo '</section>';
    ftc_render_followups($response['followups'] ?? []);
    echo '</div>';
}

function ftc_pick_response($term){
    $q = strtolower(trim((string)$term));
    $responses = ftc_get_responses();
    $map = [
        'about field theory'=>'about','about us'=>'about','about'=>'about','who are you'=>'about','team'=>'about','employees'=>'about','people'=>'about','company'=>'about','field theory'=>'about',
        'faq'=>'faq','questions'=>'faq','question'=>'faq','question mark'=>'faq',
        'privacy'=>'privacy','testimonial'=>'testimonials','review'=>'testimonials',
        'portfolio'=>'portfolio','work'=>'portfolio','project'=>'portfolio','case stud'=>'portfolio','show me'=>'portfolio',
        'service'=>'services','our services'=>'services','help my company'=>'services','help my business'=>'services','what do you do'=>'services',
        'web'=>'websites','ux'=>'websites','development'=>'websites','website'=>'websites','wordpress'=>'websites','drupal'=>'websites',
        'seo'=>'marketing','aeo'=>'marketing','marketing'=>'marketing','search'=>'marketing','growth'=>'marketing',
        'data'=>'analytics','analytics'=>'analytics','dashboard'=>'analytics','report'=>'analytics','ga4'=>'analytics',
        'ai'=>'ai','automation'=>'ai','innovation'=>'ai',
        'contact'=>'contact','hire'=>'contact','work together'=>'contact','call'=>'contact',
        'get started'=>'get_started','start'=>'get_started','home'=>'get_started'
    ];
    foreach($map as $needle=>$key){ if(strpos($q,$needle)!==false && isset($responses[$key])) return $responses[$key]; }
    return $responses['get_started'] ?? reset($responses);
}


function ftc_find_faq_answer_by_search($term){
    $term = trim((string)$term);
    if($term === '') return null;
    $q = new WP_Query([
        'post_type'=>'ftc_faq',
        'post_status'=>'publish',
        'posts_per_page'=>1,
        's'=>$term,
        'orderby'=>'relevance'
    ]);
    if($q->have_posts()){
        $q->the_post();
        $resp = [
            'title'=>get_the_title(),
            'description'=>wp_trim_words(wp_strip_all_tags(get_the_content()), 30),
            'html'=>apply_filters('the_content', get_the_content()),
            'layout'=>'none',
            'followups'=>['FAQ','Our Services','Hire Our Team']
        ];
        wp_reset_postdata();
        return $resp;
    }
    wp_reset_postdata();
    return null;
}

function ftc_ajax_answer(){
    check_ajax_referer('ftc_nonce','nonce');
    $term = sanitize_text_field(wp_unslash($_POST['term'] ?? ''));
    $settings = ftc_get_settings();

    if(function_exists('ftc_find_response_cpt_matches')){
        $cpt_matches = ftc_find_response_cpt_matches($term);
        if(count($cpt_matches) > 1){
            ob_start();
            ftc_render_cpt_response_matches($cpt_matches, $settings);
            $html = ob_get_clean();
            wp_send_json_success(['html'=>$html]);
        }
        if(count($cpt_matches) === 1){
            ob_start();
            ftc_render_cpt_response_shell($cpt_matches[0], $settings);
            $html = ob_get_clean();
            wp_send_json_success(['html'=>$html]);
        }
    }
    if (preg_match('/\b(joke|funny|make me laugh|tell me another joke)\b/i', $term)) {
        $jokes = [
            'Why did the website go to therapy? Too many unresolved issues.',
            'I told my analytics dashboard a joke. It said the punchline had excellent engagement.',
            'Why did the marketer break up with the spreadsheet? It had too many rows and not enough feelings.',
            'A UX designer walks into a bar. Then quietly moves the door somewhere more intuitive.',
            'Why did the AI assistant get promoted? It had prompt attendance.',
            'I asked SEO for a joke. It said, “You will find the answer in position one.”',
            'Why was the landing page so confident? It had a clear call to action.',
            'A developer, designer, and strategist walk into a meeting. Somehow the button still needs to be bigger.',
            'Why did the data scientist bring a ladder? To reach higher confidence intervals.',
            'Field Theory joke: we do not chase trends. We A/B test them until they confess.'
        ];
        $response = ['title'=>'Okay, I have one.','description'=>$jokes[array_rand($jokes)],'html'=>'','layout'=>'none','followups'=>['Tell me another joke','Get Started']];
        ob_start(); ftc_render_response_shell($response,$settings,$term); $html=ob_get_clean(); wp_send_json_success(['html'=>$html]);
    }
    $faq_direct = ftc_find_faq_answer_by_search($term);
    if($faq_direct && preg_match('/\?|how|what|why|when|where|can|do you|should|cost|price|timeline|hosting|maintenance|support|seo|analytics|ai/i', $term)){
        ob_start(); ftc_render_response_shell($faq_direct,$settings,$term); $html=ob_get_clean(); wp_send_json_success(['html'=>$html]);
    }
    $portfolio_id = ftc_find_portfolio_by_search($term);
    if($portfolio_id){
        ob_start();
        ftc_render_project_detail_markup($portfolio_id);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }
    $service_id = ftc_find_service_by_search($term);
    if($service_id && preg_match('/service|web|site|seo|marketing|data|analytics|ecommerce|ai|automation|development|design/i', $term)){
        ob_start();
        echo '<div class="ftc-response-shell ftc-response-layout-service-detail"><header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr(get_the_title($service_id)).'">'.esc_html(get_the_title($service_id)).'</h2></header><section class="ftc-response-content">';
        ftc_render_service_detail_by_id($service_id);
        echo '</section></div>';
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }
    $response = ftc_pick_response($term);
    ob_start(); ftc_render_response_shell($response,$settings,$term); $html = ob_get_clean();
    wp_send_json_success(['html'=>$html]);
}
add_action('wp_ajax_ftc_answer','ftc_ajax_answer');
add_action('wp_ajax_nopriv_ftc_answer','ftc_ajax_answer');

function ftc_ajax_menu(){
    check_ajax_referer('ftc_nonce','nonce');
    ob_start();
    echo '<div class="ftc-explore-list">';
    foreach([
        ['Get Started','Start with the Field Theory overview'],
        ['About Field Theory','Company, team, and approach'],
        ['Our Services','Services and capabilities'],
        ['Show me your work!','Portfolio and projects'],
        ['FAQ','Common questions'],
        ['Hire Our Team','Contact Field Theory'],
        ['Privacy Policy','Privacy statement']
    ] as $item){
        echo '<button type="button" class="ftc-menu-prompt" data-prompt="'.esc_attr($item[0]).'"><span>'.esc_html($item[0]).'</span><small>'.esc_html($item[1]).'</small></button>';
    }
    echo '</div>';
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_menu','ftc_ajax_menu');
add_action('wp_ajax_nopriv_ftc_menu','ftc_ajax_menu');


function ftc_render_editable_html($html){
    $html = (string)$html;
    if ($html === '') return '';
    // Admin-managed concierge content may include shortcodes such as [elementor-template id="123"].
    return do_shortcode(wp_kses_post($html));
}

function ftc_render_elementor_template_by_id($template_id){
    $template_id = absint($template_id);
    if (!$template_id) return '';
    return do_shortcode('[elementor-template id="' . $template_id . '"]');
}

function ftc_render_response_shell($response,$settings=[],$search_term=''){
    $title=$response['title'] ?? 'Get Started.'; $desc=$response['description'] ?? ''; $layout=$response['layout'] ?? 'none';
    echo '<div class="ftc-response-shell ftc-response-layout-'.esc_attr($layout).'">';
    echo '<header class="ftc-response-header"><div class="ftc-kicker">'.esc_html($settings['descriptor'] ?? 'Web Design and Digital Marketing').'</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($title).'">'.esc_html($title).'</h2>';
    if($desc) echo '<div class="ftc-answer-description">'.esc_html($desc).'</div>';
    echo '</header>';
    if(!empty($response['html'])) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
    echo '<section class="ftc-response-content">';
    switch($layout){
        case 'home': ftc_render_home_panel($settings); break;
        case 'about': ftc_render_about_panel($settings); break;
        case 'portfolio': ftc_render_portfolio_masonry(); break;
        case 'services': ftc_render_services_panel(); break;
        case 'service_detail': ftc_render_service_detail($response); break;
        case 'faq': ftc_render_faq_panel(); break;
        case 'contact': ftc_render_contact_panel($settings); break;
        case 'testimonials': ftc_render_testimonials_panel(); break;
    }
    echo '</section>';
    ftc_render_followups($response['followups'] ?? []);
    echo '</div>';
}

function ftc_render_home_panel($settings){
    $video = $settings['demo_video_url'] ?? '';
    echo '<div class="ftc-response-subsection ftc-response-subsection-start" data-response-section="intro">';
    echo '<div class="ftc-home-overview">';
    echo '<div class="ftc-home-media">';
    if($video){
        echo '<video controls autoplay muted loop playsinline preload="metadata"><source src="'.esc_url($video).'"></video>';
    } else {
        echo '<img src="'.esc_url(FTC_URL.'assets/images/placeholder-gray-16x9.svg').'" alt="Field Theory overview">';
    }
    echo '</div>';
    echo '<div class="ftc-home-copy"><h3>Creative. Technical. Strategic.</h3><p>Field Theory Lab helps organizations grow through better technology, smarter marketing, deeper insights, and practical AI. We design better web experiences, improve digital performance, and build systems that help teams make smarter decisions.</p><button class="ftc-red-link" type="button" data-prompt="Tell me about your company">Meet Field Theory Lab.</button></div>';
    echo '</div></div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-work" data-response-section="portfolio"><header class="ftc-subresponse-header"><div><span>Show me your work!</span><h3>Our Work</h3><p>A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.</p></div><button type="button" data-prompt="Show me your work!">Show me your work!</button></header>';
    echo '<div class="ftc-portfolio-latest-wrap"><button type="button" class="ftc-carousel-arrow ftc-carousel-prev ftc-portfolio-prev" data-ftc-portfolio-prev aria-label="Previous projects">‹</button>';
    ftc_render_portfolio_masonry(9);
    echo '<button type="button" class="ftc-carousel-arrow ftc-carousel-next ftc-portfolio-next" data-ftc-portfolio-next aria-label="Next projects">›</button></div></div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-services" data-response-section="services"><header class="ftc-subresponse-header"><div><span>How can you help my company?</span><h3>Our Services</h3><p>Explore the main ways Field Theory helps organizations improve websites, marketing, analytics, automation, ecommerce, and customer experience.</p></div><button type="button" data-prompt="How can you help my company?">Explore services</button></header>';
    ftc_render_services_panel(false);
    echo '</div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-testimonials" data-response-section="testimonials"><header class="ftc-subresponse-header"><div><span>What do clients say?</span><h3>Testimonials</h3><p>A few notes on what teams value when they work with Field Theory Lab.</p></div><button type="button" data-prompt="Testimonials">Read testimonials</button></header>';
    ftc_render_testimonials_panel();
    echo '</div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-about" data-response-section="about"><header class="ftc-subresponse-header"><div><span>Tell me about your company</span><h3>About Field Theory</h3><p>Field Theory Lab is a creative technology agency based in Albuquerque, New Mexico.</p></div><button type="button" data-prompt="Tell me about your company">Meet Field Theory</button></header>';
    ftc_render_about_panel($settings);
    echo '</div>';
}
function ftc_render_about_panel($settings){
    echo '<div class="ftc-about-layout ftc-about-redesign">';
    echo '<section class="ftc-about-copy"><h3>We are Field Theory Lab.</h3><p>Field Theory Lab is a creative technology agency based in Albuquerque, New Mexico. We help organizations plan, design, build, measure, and improve digital experiences that support real business growth.</p><p>We are built for teams that need strategy, design, web development, analytics, SEO/AEO, ecommerce, integrations, automation, and practical AI working together instead of living in separate silos.</p><ul><li>We design and build websites, ecommerce experiences, dashboards, and custom digital tools.</li><li>We support clients monthly through hosting, maintenance, analytics, SEO, content, performance, and optimization.</li><li>We work closely with internal marketing, communications, IT, operations, and leadership teams.</li></ul><button class="ftc-green-btn" type="button" data-prompt="Hire Our Team">Hire Our Team</button></section>';
    echo '<section class="ftc-team-grid" aria-label="Field Theory team and capabilities">';
    foreach([
        ['Jamie Rushad Gros','CEO / Technology Strategist','Strategy, UX, creative technology, marketing systems, analytics, AI implementation, and digital growth.'],
        ['Creative + UX','Experience Design','User journeys, interface design, messaging, content structure, visual systems, and conversion-focused experiences.'],
        ['Development + Data','Technology Delivery','WordPress, Drupal, Shopify/WooCommerce, dashboards, integrations, hosting, automation, and AI-enabled workflows.']
    ] as $item){
        echo '<article class="ftc-team-card"><div class="ftc-team-avatar"><img src="'.esc_url(FTC_URL.'assets/images/placeholder-gray-16x9.svg').'" alt=""></div><strong>'.esc_html($item[0]).'</strong><span>'.esc_html($item[1]).'</span><p>'.esc_html($item[2]).'</p></article>';
    }
    echo '</section></div>';
}
function ftc_get_services($limit=-1){
    $q=new WP_Query(['post_type'=>'ftc_service','post_status'=>'publish','posts_per_page'=>$limit,'orderby'=>'menu_order title','order'=>'ASC']);
    if(!$q->have_posts()){ return []; }
    $items=[]; while($q->have_posts()){ $q->the_post(); $id=get_the_ID(); $items[]=['id'=>$id,'title'=>get_the_title(),'desc'=>get_the_excerpt() ?: wp_trim_words(wp_strip_all_tags(get_the_content()),22),'eyebrow'=>get_post_meta($id,'_ftc_service_eyebrow',true),'image'=>get_post_meta($id,'_ftc_service_image',true),'tasks'=>array_filter(array_map('trim',explode("\n",get_post_meta($id,'_ftc_service_tasks',true))))]; } wp_reset_postdata(); return $items;
}

function ftc_service_default_image($title=''){
    return FTC_URL.'assets/images/placeholder-gray-16x9.svg';
}

function ftc_service_badges_for_title($title){
    $t = strtolower((string)$title);
    if(strpos($t,'website') !== false) return ['WordPress','Drupal','React','Hosting'];
    if(strpos($t,'ecommerce') !== false) return ['Shopify','WooCommerce','CRO','Amazon Ads'];
    if(strpos($t,'data') !== false) return ['GA4','Dashboards','ANNA','Looker'];
    if(strpos($t,'search') !== false || strpos($t,'seo') !== false) return ['SEO','AEO','Schema','Local'];
    if(strpos($t,'marketing') !== false || strpos($t,'growth') !== false) return ['Google Ads','Meta','Funnels','Content'];
    if(strpos($t,'creative') !== false || strpos($t,'ai') !== false) return ['AI Agents','Automation','Tools','UX'];
    return ['Strategy','Design','Build','Measure'];
}

function ftc_render_services_panel($full=true){
    $items=ftc_get_services($full ? -1 : 6); if(!$items){ return; }
    if(!$full){
        echo '<div class="ftc-service-carousel-wrap"><button type="button" class="ftc-carousel-arrow ftc-carousel-prev" data-ftc-carousel-prev aria-label="Previous services">‹</button><div class="ftc-service-carousel-track" data-ftc-carousel-track>';
    } else {
        echo '<div class="ftc-service-grid">';
    }
    foreach($items as $item){
        $image = $item['image'] ?: ftc_service_default_image($item['title'].' '.$item['eyebrow']);
        $badges = ftc_service_badges_for_title($item['title']);
        echo '<article class="ftc-service-card"><button type="button" class="ftc-service-open" data-ftc-service="'.esc_attr($item['id']).'">';
        echo '<div class="ftc-service-art"><img src="'.esc_url($image).'" alt="'.esc_attr($item['title']).'" loading="lazy"></div>';
        echo '<strong>'.esc_html($item['title']).'</strong><p>'.esc_html($item['desc']).'</p>';
        echo '<div class="ftc-tech-badges">'; foreach($badges as $badge) echo '<span>'.esc_html($badge).'</span>'; echo '</div>';
        echo '<em>Explore service →</em></button></article>';
    }
    if(!$full){
        echo '</div><button type="button" class="ftc-carousel-arrow ftc-carousel-next" data-ftc-carousel-next aria-label="Next services">›</button></div>';
    } else {
        echo '</div>';
    }
}
function ftc_render_service_detail($response){
    $slug = $response['service_slug'] ?? ''; $post=null;
    if($slug){ $posts=get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1]); if($posts) $post=$posts[0]; }
    if(!$post){ $posts=get_posts(['post_type'=>'ftc_service','posts_per_page'=>1]); if($posts) $post=$posts[0]; }
    if(!$post){ ftc_render_services_panel(); return; }
    ftc_render_service_detail_by_id($post->ID);
}
function ftc_render_service_detail_by_id($id){
    $template_id = get_post_meta($id,'_ftc_elementor_template_id',true);
    if($template_id){ echo '<div class="ftc-elementor-template ftc-service-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div>'; return; }
    $image=get_post_meta($id,'_ftc_service_image',true); if(!$image) $image=ftc_service_default_image(get_the_title($id));
    $tasks_raw=get_post_meta($id,'_ftc_service_tasks',true);
    $lines=array_values(array_filter(array_map('trim',explode("\n",$tasks_raw))));
    echo '<div class="ftc-service-detail"><button class="ftc-back-button" type="button" data-prompt="Our Services">Back to Services</button><div class="ftc-service-detail-hero"><div class="ftc-service-detail-copy"><h3>'.esc_html(get_the_title($id)).'</h3><div>'.wp_kses_post(apply_filters('the_content',get_post_field('post_content',$id))).'</div><p class="ftc-service-managed">We can support one-time builds or ongoing monthly partnerships across hosting, maintenance, analytics, SEO, content support, accessibility, security, reporting, and continuous improvement.</p><button class="ftc-green-btn" type="button" data-prompt="Hire Our Team">Hire Our Team</button></div>';
    echo '<div class="ftc-service-detail-image"><img src="'.esc_url($image).'" alt="'.esc_attr(get_the_title($id)).'"></div>';
    echo '</div><div class="ftc-child-grid">';
    $groups=[];
    foreach($lines as $line){
        if(strpos($line,':')!==false){ [$label,$items]=array_map('trim',explode(':',$line,2)); $groups[$label]=array_values(array_filter(array_map('trim',preg_split('/[,;]+/',$items)))); }
    }
    if(!$groups){
        $labels=['Strategy & Planning','Design & Experience','Development & Systems','Optimization & Support'];
        foreach(array_chunk($lines ?: ['Discovery and strategy','Design and UX','Technical implementation','Measurement and optimization'], max(1,ceil(max(1,count($lines))/4))) as $i=>$chunk){ $groups[$labels[$i] ?? 'Service Tasks']=$chunk; }
    }
    foreach($groups as $label=>$items){ echo '<article><h4>'.esc_html($label).'</h4><p>Practical capabilities we can plan, build, manage, measure, and improve.</p><ul>'; foreach($items as $t) echo '<li>'.esc_html($t).'</li>'; echo '</ul></article>'; }
    echo '</div></div>';
}
function ftc_render_portfolio_masonry($limit=-1){
    $count = ($limit > 0) ? $limit : 9;
    $is_home_latest = ($limit > 0);
    $q=new WP_Query(['post_type'=>'ftc_portfolio','post_status'=>'publish','posts_per_page'=>$count,'orderby'=>'date','order'=>'DESC']);
    echo '<div class="ftc-portfolio-panel'.($is_home_latest ? ' ftc-portfolio-latest' : ' ftc-portfolio-full').'">';
    if($q->have_posts()){
        $i=0;
        while($q->have_posts()){
            $q->the_post();
            ftc_portfolio_card(get_the_ID(), (!$is_home_latest && $i === 0));
            $i++;
        }
        wp_reset_postdata();
    } else {
        $i=0;
        foreach(array_slice(ftc_get_demo_portfolio(),0,$count) as $demo){
            $class = (!$is_home_latest && $i === 0) ? ' ftc-work-featured' : '';
            echo '<article class="ftc-work-card'.$class.'"><button type="button" data-prompt="Show me your work"><img src="'.esc_url($demo['image']).'" alt="'.esc_attr($demo['title']).'"><div class="ftc-work-info"><h3>'.esc_html($demo['title']).'</h3><p>'.esc_html($demo['description']).'</p><strong>View Project →</strong></div></button></article>';
            $i++;
        }
    }
    echo '</div>';
}
function ftc_portfolio_card($id, $featured=false){
    $img=has_post_thumbnail($id)?get_the_post_thumbnail_url($id,'large'):'';
    if(!$img){
        $gallery=get_post_meta($id,'_ftc_gallery_urls',true);
        $parts=array_values(array_filter(array_map('trim',explode("
",$gallery))));
        if($parts) $img=$parts[0];
    }
    if(!$img) $img = FTC_URL.'assets/images/placeholder-gray-16x9.svg';
    $class = $featured ? ' ftc-work-featured' : '';
    echo '<article class="ftc-work-card'.$class.'"><button type="button" data-ftc-project="'.esc_attr($id).'"><img src="'.esc_url($img).'" alt="'.esc_attr(get_the_title($id)).'">';
    echo '<div class="ftc-work-info"><h3>'.esc_html(get_the_title($id)).'</h3><p>'.esc_html(get_the_excerpt($id) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content',$id)),28)).'</p><strong>View Project →</strong></div></button></article>';
}
function ftc_render_faq_panel(){
    $q=new WP_Query(['post_type'=>'ftc_faq','post_status'=>'publish','posts_per_page'=>80,'orderby'=>'menu_order title','order'=>'ASC']); $groups=[];
    while($q->have_posts()){ $q->the_post(); $terms=get_the_terms(get_the_ID(),'ftc_faq_topic'); $topic=(!is_wp_error($terms)&&$terms)?$terms[0]->name:'General'; $groups[$topic][]= ['q'=>get_the_title(),'a'=>apply_filters('the_content',get_the_content())]; } wp_reset_postdata();
    echo '<div class="ftc-faq-layout">'; foreach($groups as $topic=>$items){ echo '<section class="ftc-faq-topic"><h3>'.esc_html($topic).'</h3>'; foreach($items as $item){ echo '<details class="ftc-faq-item"><summary>'.esc_html($item['q']).'</summary><div class="ftc-faq-answer">'.wp_kses_post($item['a']).'</div></details>'; } echo '</section>'; } echo '</div>';
}
function ftc_render_testimonials_panel(){ echo '<div class="ftc-testimonial-grid"><figure><blockquote>“A clearer website, cleaner message, and a team that understood the business problem before touching the design.”</blockquote><figcaption>Healthcare client</figcaption></figure><figure><blockquote>“Field Theory brought strategy, design, development, and analytics together without making the process feel heavy.”</blockquote><figcaption>Nonprofit partner</figcaption></figure><figure><blockquote>“The reporting finally made sense. We could see what was working and what to do next.”</blockquote><figcaption>Marketing director</figcaption></figure></div>'; }
function ftc_render_contact_panel($settings){
    echo '<div class="ftc-contact-layout"><div class="ftc-contact-copy"><h3>Tell us what you are trying to improve.</h3><p>Whether you need a stronger website, better analytics, more qualified leads, AI workflow support, SEO/AEO help, or an ongoing digital partner, we can help clarify the next right move.</p><ul><li>Website, UX, CMS, hosting, and maintenance</li><li>SEO, AEO, paid media, CRO, and reporting</li><li>Dashboards, integrations, automation, and AI agents</li></ul></div><form class="ftc-contact-form" action="'.esc_url($settings['contact_url'] ?: '#').'" method="get"><label>Name<input name="name" type="text" placeholder="Your name"></label><label>Email<input name="email" type="email" placeholder="you@example.com"></label><label>What can we help with?<textarea name="message" rows="5" placeholder="Tell us about the project, problem, or idea."></textarea></label><button type="submit">Hire Our Team</button></form></div>';
}
function ftc_render_followups($followups){
    if(!$followups) return;
    echo '<footer class="ftc-followups"><div class="ftc-followup-row">';
    foreach($followups as $f) echo '<button type="button" class="ftc-followup" data-prompt="'.esc_attr($f).'">'.esc_html($f).'</button>';
    echo '</div></footer>';
}
add_action('wp_ajax_ftc_portfolio_detail','ftc_ajax_portfolio_detail'); add_action('wp_ajax_nopriv_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
function ftc_ajax_service_detail(){ check_ajax_referer('ftc_nonce','nonce'); $id=absint($_POST['service_id']??0); if(!$id||get_post_type($id)!=='ftc_service') wp_send_json_error(); ob_start(); echo '<div class="ftc-response-shell ftc-response-layout-service-detail"><header class="ftc-response-header"><div class="ftc-kicker">Tell Me About Web Dev</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="We completed a fantastic project for '.esc_attr(get_the_title($id)).'">'.esc_html('We completed a fantastic project for '.get_the_title($id)).'</h2></header><section class="ftc-response-content">'; ftc_render_service_detail_by_id($id); echo '</section>'; ftc_render_followups(['Back to Services','Hire Our Team','Show me your work!']); echo '</div>'; wp_send_json_success(['html'=>ob_get_clean()]); }
add_action('wp_ajax_ftc_service_detail','ftc_ajax_service_detail'); add_action('wp_ajax_nopriv_ftc_service_detail','ftc_ajax_service_detail');
function ftc_ajax_post_detail(){ wp_send_json_error(); }
add_action('wp_ajax_ftc_post_detail','ftc_ajax_post_detail'); add_action('wp_ajax_nopriv_ftc_post_detail','ftc_ajax_post_detail');
function ftc_render_project_detail_markup($post_id){
    $post = get_post($post_id);
    if(!$post || $post->post_type !== 'ftc_portfolio') return;
    $imgs=[];
    if(has_post_thumbnail($post_id)) $imgs[]=get_the_post_thumbnail_url($post_id,'large');
    $gallery=get_post_meta($post_id,'_ftc_gallery_urls',true);
    foreach(array_filter(array_map('trim',explode("\n",$gallery))) as $u) $imgs[]=$u;
    if(!$imgs){
        $imgs[] = FTC_URL.'assets/images/placeholder-gray-16x9.svg';
        $imgs[] = FTC_URL.'assets/images/placeholder-gray-16x9.svg';
        $imgs[] = FTC_URL.'assets/images/placeholder-gray-16x9.svg';
    }
    $feature = array_shift($imgs);
    $industry = get_post_meta($post_id,'_ftc_industry',true);
    echo '<div class="ftc-response-shell ftc-response-layout-project">';
    echo '<header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr(get_the_title($post_id)).'">'.esc_html(get_the_title($post_id)).'</h2><div class="ftc-answer-description">'.esc_html(get_the_excerpt($post_id) ?: 'Project details, creative direction, and supporting images.').'</div></header>';
    echo '<section class="ftc-response-content ftc-project-page">';
    echo '<button type="button" class="ftc-back-button" data-prompt="Show me your work!">Back to Portfolio</button>';
    echo '<div class="ftc-project-detail">';
    echo '<div class="ftc-project-copy">';
    if($industry) echo '<p class="ftc-project-industry">'.esc_html($industry).'</p>';
    echo '<h3>'.esc_html(get_the_title($post_id)).'</h3>';
    echo '<div class="ftc-project-text">'.wp_kses_post(apply_filters('the_content',$post->post_content)).'</div>';
    echo '</div>';
    echo '<div class="ftc-project-feature"><img src="'.esc_url($feature).'" alt="'.esc_attr(get_the_title($post_id)).'"></div>';
    echo '</div>';
    if($imgs){
        echo '<div class="ftc-project-gallery">';
        foreach($imgs as $u) echo '<figure><img src="'.esc_url($u).'" alt="'.esc_attr(get_the_title($post_id)).'"></figure>';
        echo '</div>';
    }
    echo '</section>';
    ftc_render_followups(['Back to Portfolio','Our Services','Hire Our Team']);
    echo '</div>';
}




function ftc_ajax_portfolio_detail(){
    check_ajax_referer('ftc_nonce','nonce');
    $post_id = absint($_POST['post_id'] ?? 0);
    $post = get_post($post_id);
    if(!$post || $post->post_type !== 'ftc_portfolio'){
        wp_send_json_error(['message'=>'Project not found.']);
    }

    $template_id = get_post_meta($post_id,'_ftc_elementor_template_id',true);
    ob_start();
    if($template_id){
        echo '<div class="ftc-response-shell ftc-response-layout-project"><section class="ftc-response-content"><div class="ftc-elementor-template ftc-project-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div></section>';
        ftc_render_followups(['Back to Portfolio','Our Services','Hire Our Team']);
        echo '</div>';
        wp_send_json_success(['html'=>ob_get_clean()]);
    }

    ftc_render_project_detail_markup($post_id);
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
add_action('wp_ajax_nopriv_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
