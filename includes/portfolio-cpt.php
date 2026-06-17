<?php
if (!defined('ABSPATH')) exit;

function ftc_register_portfolio_cpt(){
    register_post_type('ftc_portfolio', [
        'labels' => ['name'=>'Concierge Portfolio','singular_name'=>'Portfolio Item','add_new_item'=>'Add Portfolio Item','edit_item'=>'Edit Portfolio Item'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-portfolio',
        'supports'=>['title','editor','thumbnail','excerpt','custom-fields'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-work'],
    ]);
    register_taxonomy('ftc_portfolio_tag','ftc_portfolio',['label'=>'Portfolio Tags','hierarchical'=>false,'show_ui'=>true,'show_in_menu'=>true,'rewrite'=>['slug'=>'ft-work-tag']]);

    register_post_type('ftc_service', [
        'labels' => ['name'=>'Concierge Services','singular_name'=>'Service','add_new_item'=>'Add Service','edit_item'=>'Edit Service'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-admin-tools',
        'supports'=>['title','editor','thumbnail','excerpt','page-attributes','custom-fields'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-service'],
    ]);
    register_taxonomy('ftc_service_group','ftc_service',['label'=>'Service Groups','hierarchical'=>true,'show_ui'=>true,'show_in_menu'=>true]);

    ftc_register_faq_cpt();
}
add_action('init','ftc_register_portfolio_cpt');

function ftc_register_faq_cpt(){
    register_post_type('ftc_faq', [
        'labels'=>['name'=>'Concierge FAQs','singular_name'=>'FAQ','add_new_item'=>'Add FAQ','edit_item'=>'Edit FAQ'],
        'public'=>true,'show_ui'=>true,'show_in_menu'=>'field-theory-concierge','menu_icon'=>'dashicons-editor-help',
        'supports'=>['title','editor','excerpt','page-attributes','custom-fields'],'show_in_rest'=>true,'has_archive'=>false,'rewrite'=>['slug'=>'ft-faq'],
    ]);
    register_taxonomy('ftc_faq_topic','ftc_faq',['label'=>'FAQ Topics','hierarchical'=>true,'show_ui'=>true,'show_in_menu'=>true,'rewrite'=>['slug'=>'ft-faq-topic']]);
}

function ftc_portfolio_meta_boxes(){
    add_meta_box('ftc_portfolio_details','Concierge Portfolio Details','ftc_portfolio_meta_box','ftc_portfolio','normal','high');
    add_meta_box('ftc_service_details','Concierge Service Details','ftc_service_meta_box','ftc_service','normal','high');
}
add_action('add_meta_boxes','ftc_portfolio_meta_boxes');

function ftc_portfolio_meta_box($post){
    wp_nonce_field('ftc_save_portfolio','ftc_portfolio_nonce');
    $industry=get_post_meta($post->ID,'_ftc_industry',true); $video=get_post_meta($post->ID,'_ftc_video_url',true); $url=get_post_meta($post->ID,'_ftc_project_url',true); $results=get_post_meta($post->ID,'_ftc_results',true); $featured=get_post_meta($post->ID,'_ftc_featured',true); $gallery=get_post_meta($post->ID,'_ftc_gallery_urls',true); $template_id=get_post_meta($post->ID,'_ftc_elementor_template_id',true);
    echo '<p><label><strong>Industry</strong></label><br><input type="text" name="ftc_industry" value="'.esc_attr($industry).'" class="widefat"></p>';
    echo '<p><label><strong>Project URL</strong></label><br><input type="url" name="ftc_project_url" value="'.esc_attr($url).'" class="widefat"></p>';
    echo '<p><label><strong>Video URL</strong></label><br><input type="url" name="ftc_video_url" value="'.esc_attr($video).'" class="widefat"></p>';
    echo '<p><label><strong>Elementor Template ID</strong> <span style="color:#666">Optional. If set, this full-width Elementor template replaces the default concierge project detail.</span></label><br><input type="number" name="ftc_elementor_template_id" value="'.esc_attr($template_id).'" class="widefat"></p>';
    echo '<p><label><strong>Multi Image Gallery URLs</strong> <span style="color:#666">One image URL per line. Use Media Library URLs.</span></label><br><textarea name="ftc_gallery_urls" class="widefat" rows="6">'.esc_textarea($gallery).'</textarea></p>';
    echo '<p><label><strong>Results / Impact</strong></label><br><textarea name="ftc_results" class="widefat" rows="4">'.esc_textarea($results).'</textarea></p>';
    echo '<p><label><input type="checkbox" name="ftc_featured" value="1" '.checked($featured,'1',false).'> Featured in Concierge</label></p>';
}
function ftc_service_meta_box($post){
    wp_nonce_field('ftc_save_service','ftc_service_nonce');
    $eyebrow=get_post_meta($post->ID,'_ftc_service_eyebrow',true); $image=get_post_meta($post->ID,'_ftc_service_image',true); $tasks=get_post_meta($post->ID,'_ftc_service_tasks',true); $featured=get_post_meta($post->ID,'_ftc_featured',true); $template_id=get_post_meta($post->ID,'_ftc_elementor_template_id',true);
    echo '<p><label><strong>Small Label / Icon Text</strong></label><br><input type="text" name="ftc_service_eyebrow" value="'.esc_attr($eyebrow).'" class="widefat" placeholder="API, SEO, DATA, AI"></p>';
    echo '<p><label><strong>Service Image URL</strong></label><br><input type="url" name="ftc_service_image" value="'.esc_attr($image).'" class="widefat"></p>';
    echo '<p><label><strong>Elementor Template ID</strong> <span style="color:#666">Optional. If set, this full-width Elementor template replaces the default concierge service detail.</span></label><br><input type="number" name="ftc_elementor_template_id" value="'.esc_attr($template_id).'" class="widefat"></p>';
    echo '<p><label><strong>Child Categories / Tasks</strong> <span style="color:#666">One per line</span></label><br><textarea name="ftc_service_tasks" class="widefat" rows="8">'.esc_textarea($tasks).'</textarea></p>';
    echo '<p><label><input type="checkbox" name="ftc_featured" value="1" '.checked($featured,'1',false).'> Featured in Concierge</label></p>';
}
function ftc_save_portfolio_meta($post_id){
    if (!isset($_POST['ftc_portfolio_nonce']) || !wp_verify_nonce($_POST['ftc_portfolio_nonce'],'ftc_save_portfolio')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; if (!current_user_can('edit_post',$post_id)) return;
    update_post_meta($post_id,'_ftc_industry',sanitize_text_field(wp_unslash($_POST['ftc_industry'] ?? '')));
    update_post_meta($post_id,'_ftc_video_url',esc_url_raw(wp_unslash($_POST['ftc_video_url'] ?? '')));
    update_post_meta($post_id,'_ftc_project_url',esc_url_raw(wp_unslash($_POST['ftc_project_url'] ?? '')));
    update_post_meta($post_id,'_ftc_results',sanitize_textarea_field(wp_unslash($_POST['ftc_results'] ?? '')));
    update_post_meta($post_id,'_ftc_gallery_urls',sanitize_textarea_field(wp_unslash($_POST['ftc_gallery_urls'] ?? '')));
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

function ftc_seed_default_services(){
    if (get_option('ftc_services_seeded_270')) return;
    $services = [
        ['Website Development & Core Tech','API','Websites, UX, WordPress, Drupal, integrations, performance, accessibility, and modern digital infrastructure.','https://placehold.co/960x540/242424/ffd94d?text=Website+Development',['Website User Experience Design','WordPress Development','Drupal Development','API Integrations','Accessibility & Performance','Technical Planning','Content Management Systems','Landing Pages']],
        ['Digital Marketing & Growth Strategy','GROWTH','SEO, content strategy, campaigns, conversion planning, AI visibility, and better customer journeys.','https://placehold.co/960x540/242424/ffd94d?text=Digital+Marketing',['Campaign Strategy','Content Strategy','Conversion Planning','Local Search','Paid Media Planning','Marketing Operations','Email Journeys','Reporting']],
        ['Search & Discovery Optimization (SEO / AEO)','SEO','Search visibility, answer engine optimization, content architecture, technical SEO, and useful search-ready websites.',FTC_URL.'assets/images/service-seo.svg',['Technical SEO','AI Search Visibility','Structured Data','Content Architecture','Local SEO','Editorial Planning','Search Audits','Optimization Roadmaps']],
        ['Ecommerce & Conversion Rate Optimization (CRO)','CRO','Ecommerce, product journeys, checkout experience, testing, conversion funnels, and measurable growth improvements.',FTC_URL.'assets/images/service-cro.svg',['Shop UX','Product Pages','Checkout Optimization','Conversion Funnels','A/B Test Planning','Memberships','Subscriptions','Analytics Events']],
        ['Data, Analysis & Visualization','DATA','GA4, dashboards, reporting, campaign measurement, and decision-ready insights.','https://placehold.co/960x540/242424/ffd94d?text=Data+%26+Analytics',['GA4 Configuration','Looker Studio Dashboards','KPI Planning','Campaign Reporting','Data Storytelling','Tag Management','Executive Summaries','Tracking Audits']],
        ['Creative Technology & Innovation','AI','Practical AI workflows, internal assistants, automation, and experimental digital tools.','https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',['AI Assistants','Workflow Automation','Internal Knowledge Tools','Lead Support','Prototype Development','Creative Experiments','Prompt Systems','Team Adoption']],
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
    ['Websites','What makes a website effective?','An effective website explains the business clearly, helps visitors find what they need, works on mobile, earns trust, and makes the next step obvious.'],
    ['Marketing','How do I know if my marketing is working?','Start with goals, conversion events, traffic quality, lead quality, and reporting that ties activity to outcomes.'],
    ['SEO / AEO','What is AI visibility?','AI visibility makes your expertise, brand, and content easier for AI-powered search and answer systems to understand and summarize.'],
    ['Analytics','What should we track?','Track meaningful events like forms, calls, downloads, purchases, key button clicks, qualified traffic sources, and conversion paths.'],
    ['AI','How can AI help my business?','AI can support research, reporting, content workflows, customer service, internal knowledge, and repetitive operations.'],
    ['Working Together','How can we work with Field Theory?','Start with a conversation. We will help clarify the problem, define the next right step, and build a practical plan.'],
]; }
function ftc_more_default_faqs(){ return []; }
function ftc_seed_default_faqs(){
    if (get_option('ftc_default_faqs_seeded_270')) return;
    foreach(ftc_default_faqs() as $i=>$faq){ [$topic,$q,$a]=$faq; if(get_page_by_title($q,OBJECT,'ftc_faq')) continue; $id=wp_insert_post(['post_type'=>'ftc_faq','post_status'=>'publish','post_title'=>$q,'post_content'=>'<p>'.esc_html($a).'</p>','menu_order'=>$i]); if($id&&!is_wp_error($id)) wp_set_object_terms($id,$topic,'ftc_faq_topic'); }
    update_option('ftc_default_faqs_seeded_270',1);
}
add_action('admin_init','ftc_seed_default_faqs');

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
                'Analytics Strategy: Analytics Strategy, Data Collection & Governance, KPI Development, North Star Metric Identification, Attribution Modeling',
                'Dashboards & Reporting: Dashboard Design, Business Intelligence, Executive Reporting, Custom Reporting Solutions, Automated Reporting, Business Intelligence Visualizations',
                'Marketing & Behavior: Marketing Analytics, Conversion Analytics, Performance Marketing Reporting, Behavioral Analytics, User Journey Analysis, Funnel Analysis, Predictive Insights',
                'ANNA Analytics Platform: Executive Dashboards, Marketing Performance Tracking, Lead Generation Reporting, CRO Monitoring, Customer Journey Analysis, Cross-Channel Attribution',
                'Data Sources: Google Analytics, Google Search Console, CRM Platforms, Ecommerce Platforms, Advertising Platforms, Custom APIs, Internal Databases'
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
                'Managed Growth: Campaign planning, reporting, optimization, content management, and ongoing advisory support'
            ]
        ],
        [
            'title'=>'Creative Technology & Innovation','slug'=>'creative-technology-innovation','eyebrow'=>'AI','image'=>'https://placehold.co/960x540/242424/ffd94d?text=AI+%26+Innovation',
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
    if (get_option('ftc_services_synced_2623')) return;
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
    update_option('ftc_services_synced_2623',1);
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
