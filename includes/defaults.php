<?php
if (!defined('ABSPATH')) exit;

function ftc_default_settings(){
    return [
        'dark_logo' => FTC_URL . 'assets/images/FieldTheory_2026_BrighterColors.svg',
        'light_logo' => FTC_URL . 'assets/images/FieldTheory_2026_BrighterColors.svg',
        'icon_logo' => FTC_URL . 'assets/images/FieldTheory_2026_BrighterColorsIcon.svg',
        'background_image' => FTC_URL . 'assets/images/FieldTheoryBackground.jpg',
        'tagline' => 'Better technology. Smarter marketing. Clearer growth.',
        'descriptor' => 'Web Design and Digital Marketing',
        'name_prompt' => 'How may we help you today?',
        'input_placeholder' => 'Ask Field Theory Lab...',
        'demo_video_url' => FTC_URL . 'assets/video/MobileDesign_FTL_2026.mp4',
        'contact_email' => 'jamie@fieldtheory.ai',
        'contact_phone' => '(505) 456-3193',
        'contact_url' => '',
        'calendly_url' => '',
        'recaptcha_site_key' => '',
        'recaptcha_secret_key' => '',
        'recaptcha_threshold' => '0.5',
    ];
}
function ftc_get_settings(){
    $defaults = ftc_default_settings();
    $settings = wp_parse_args((array)get_option('ftc_settings', []), $defaults);
    foreach ($defaults as $k=>$v) if ($settings[$k] === '') $settings[$k] = $v;
    if (($settings['contact_email'] ?? '') === 'hello@fieldtheory.ai') {
        $settings['contact_email'] = $defaults['contact_email'];
    }
    return $settings;
}

function ftc_default_responses(){
    return [
        'get_started' => [
            'title' => 'Get Started.',
            'description' => 'Start here to explore how Field Theory Lab helps organizations grow through websites, marketing, analytics, ecommerce, SEO/AEO, and practical AI systems.',
            'html' => '<p><strong>Better technology. Smarter marketing. Clearer growth.</strong></p><p>We help organizations improve websites, search visibility, analytics, conversion, digital marketing, and practical AI systems.</p>',
            'layout' => 'home',
            'followups' => ['Tell me about your company','Show me your work!','How can you help my company?','UX + Web development?','Help me understand my website and marketing data']
        ],
        'about' => [
            'title' => 'About Field Theory Lab.',
            'description' => 'A creative technology agency in Albuquerque helping organizations grow through websites, marketing, analytics, ecommerce, SEO/AEO, and practical AI.',
            'html' => '<p>Field Theory Lab is a creative technology agency in Albuquerque, New Mexico. We help organizations plan, design, build, measure, and improve digital systems that support real growth.</p><p>Our team brings together strategy, UX, web development, analytics, SEO/AEO, digital marketing, ecommerce, integrations, and practical AI implementation.</p>',
            'layout' => 'about',
            'followups' => ['Show me your work!','Our Services','Request a Proposal']
        ],
        'portfolio' => [
            'title' => 'Our Work',
            'description' => 'A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.',
            'html' => '<p>We design and build websites, implement AI, analyze data, improve customer experiences, and solve complex digital challenges through a blend of creativity, strategy, and technology.</p>',
            'layout' => 'portfolio',
            'followups' => ['Our Services','How can you help my company?','Request a Proposal']
        ],
        'services' => [
            'title' => 'Our Services',
            'description' => 'Website development, digital marketing, SEO/AEO, analytics, ecommerce, creative technology, and practical AI systems.',
            'html' => '<p>A sample of Field Theory services across websites, marketing, analytics, AI, ecommerce, and creative technology.</p>',
            'layout' => 'services',
            'followups' => ['Website Development & Core Tech','Digital Marketing & Growth Strategy','Search & Discovery Optimization','Data, Analysis & Visualization']
        ],
        'analytics' => [
            'title' => 'Data, Analysis & Visualization',
            'description' => 'Clear tracking, useful reporting, dashboards, and decision-ready insights.',
            'html' => '<p>We help organizations understand website and marketing data, set up GA4, create dashboards, and translate metrics into decisions.</p>',
            'layout' => 'service_detail',
            'service_slug' => 'data-analysis-visualization',
            'followups' => ['Our Services','Show me your work!','Request a Proposal']
        ],
        'ai' => [
            'title' => 'Technology, Innovation and A.I.',
            'description' => 'Practical AI workflows, useful automation, and experimental digital tools.',
            'html' => '<p>We use AI where it helps the business: internal assistants, knowledge systems, automation, lead support, reporting, and creative technology prototypes.</p>',
            'layout' => 'service_detail',
            'service_slug' => 'creative-technology-innovation',
            'followups' => ['Our Services','How can AI help my business?','Request a Proposal']
        ],
        'websites' => [
            'title' => 'Website Development & Core Tech',
            'description' => 'Websites, UX, WordPress, Drupal, integrations, performance, and accessibility.',
            'html' => '<p>We design and build websites that explain clearly, perform well, support search, and make the next step obvious.</p>',
            'layout' => 'service_detail',
            'service_slug' => 'website-development-core-tech',
            'followups' => ['Show me your work!','Our Services','Request a Proposal']
        ],
        'marketing' => [
            'title' => 'Digital Marketing & Growth Strategy',
            'description' => 'SEO, content, campaign planning, conversion strategy, and marketing measurement.',
            'html' => '<p>We connect strategy, content, search, campaigns, conversion, and reporting into a clearer growth system.</p>',
            'layout' => 'service_detail',
            'service_slug' => 'digital-marketing-growth-strategy',
            'followups' => ['Search & Discovery Optimization','Data, Analysis & Visualization','Request a Proposal']
        ],
        'contact' => [
            'title' => 'Work With Us',
            'description' => 'We would love to learn more about your organization and what you are trying to accomplish. This will only take a minute.',
            'html' => '',
            'layout' => 'contact',
            'followups' => ['Show me your work!','Our Services','Get Started']
        ],
        'faq' => [
            'title' => 'Frequently Asked Questions',
            'description' => 'Answers about websites, marketing, analytics, AI, SEO, AEO, UX, and working with Field Theory.',
            'html' => '<p>Here are common questions people ask when they are trying to improve their website, marketing, analytics, AI workflows, or customer experience.</p>',
            'layout' => 'faq',
            'followups' => ['Get Started','Our Services','Request a Proposal']
        ],
        'privacy' => [
            'title' => 'Privacy Policy',
            'description' => 'A simple privacy statement for the Field Theory Concierge experience.',
            'html' => '<p>We use the information you provide through this experience to respond to your questions, understand project needs, and improve the concierge. Do not submit sensitive personal information through the chat.</p><p>If you contact Field Theory Lab, your message may be stored and used to follow up about your inquiry.</p>',
            'layout' => 'none',
            'followups' => ['Get Started','Our Services','Request a Proposal']
        ],
        'testimonials' => [
            'title' => 'Testimonials',
            'description' => 'Client and referral notes about working with Field Theory.',
            'html' => '<p>Organizations usually come to Field Theory when the website, marketing, analytics, or digital system needs to work better.</p>',
            'layout' => 'testimonials',
            'followups' => ['Show me your work!','Our Services','Request a Proposal']
        ],
    ];
}
function ftc_get_responses(){ return wp_parse_args((array)get_option('ftc_responses', []), ftc_default_responses()); }
function ftc_get_demo_portfolio(){
    return [
        ['title'=>'PNM','industry'=>'Utility','description'=>'Customer-focused energy information and service journeys.','image'=>FTC_URL.'assets/images/PNM_Website3.jpg'],
        ['title'=>'NMEDD','industry'=>'Government / Economic Development','description'=>'A statewide economic development platform.','image'=>FTC_URL.'assets/images/NMEDD_Website.jpg'],
        ['title'=>'Rodgers & Co.','industry'=>'Water / Agriculture','description'=>'A mobile-first brand and website experience.','image'=>FTC_URL.'assets/images/Rodgers_MobileSite.jpg'],
        ['title'=>'St. Clair Winery','industry'=>'Consumer Brand','description'=>'Mobile product storytelling and ecommerce-style presentation.','image'=>FTC_URL.'assets/images/StClairMobile.jpg'],
        ['title'=>'OMNI CRE','industry'=>'Commercial Real Estate','description'=>'Strategic commercial real estate advisors website and content system.','image'=>FTC_URL.'assets/images/OMNICRE_Desktop_Mockup.jpg'],
        ['title'=>'MySchoolsABQ','industry'=>'Education','description'=>'School discovery, UX, and public information design.','image'=>FTC_URL.'assets/images/MySchoolsAQBDesktop.jpg'],
    ];
}


function ftc_maybe_migrate_design_defaults(){
    $current = get_option('ftc_design_version');
    if ($current === FTC_VERSION) return;

    $defaults = ftc_default_settings();
    $settings = wp_parse_args((array)get_option('ftc_settings', []), $defaults);
    $legacy_video_url = 'http://ambiguous-elbow.flywheelsites.com/wp-content/uploads/2026/06/App_Promo_Preview_1.mp4';
    if (($settings['demo_video_url'] ?? '') === $legacy_video_url) {
        $settings['demo_video_url'] = $defaults['demo_video_url'];
    }
    if (($settings['contact_email'] ?? '') === 'hello@fieldtheory.ai') {
        $settings['contact_email'] = $defaults['contact_email'];
    }
    if (($settings['tagline'] ?? '') === 'Creative. Technical. Strategic.') {
        $settings['tagline'] = $defaults['tagline'];
    }

    update_option('ftc_settings', $settings);

    $responses = wp_parse_args((array)get_option('ftc_responses', []), ftc_default_responses());
    if(isset($responses['get_started'])){
        $responses['get_started']['html'] = '<p><strong>Better technology. Smarter marketing. Clearer growth.</strong></p><p>We help organizations improve websites, search visibility, analytics, conversion, digital marketing, and practical AI systems.</p>';
    }
    if(isset($responses['contact'])){
        $responses['contact']['title'] = 'Work With Us';
        $responses['contact']['description'] = 'Tell Field Theory what you are trying to improve. This will only take a minute.';
        $responses['contact']['followups'] = ['Show me your work!','Our Services','Get Started'];
    }
    if(isset($responses['ai'])){
        $responses['ai']['title'] = 'Technology, Innovation and A.I.';
    }
    foreach(['about','portfolio','analytics','ai','websites','marketing','faq','privacy','testimonials'] as $key){
        if(isset($responses[$key]['followups']) && is_array($responses[$key]['followups'])){
            $responses[$key]['followups'] = array_values(array_map(function($prompt){
                return $prompt === 'Hire Our Team' ? 'Request a Proposal' : $prompt;
            }, $responses[$key]['followups']));
        }
    }
    foreach($responses as $key=>$response){
        if(isset($responses[$key]['followups']) && is_array($responses[$key]['followups'])){
            $responses[$key]['followups'] = array_values(array_filter($responses[$key]['followups'], function($prompt){
                return $prompt !== 'Show Me All Services';
            }));
        }
    }
    update_option('ftc_responses', $responses);

    update_option('ftc_design_version', FTC_VERSION);
}
add_action('init','ftc_maybe_migrate_design_defaults', 5);
