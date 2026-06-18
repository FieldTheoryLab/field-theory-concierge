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

function ftc_normalize_prompt_text($value){
    $value = strtolower(html_entity_decode(wp_strip_all_tags((string)$value), ENT_QUOTES, 'UTF-8'));
    $value = str_replace('&', ' and ', $value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim(preg_replace('/\s+/', ' ', $value));
}

function ftc_service_slug_for_prompt($term){
    $t = ftc_normalize_prompt_text($term);
    if($t === '') return '';
    $map = [
        'search-discovery-optimization-seo-aeo' => ['search discovery','search optimization','seo','aeo','answer engine','ai search','schema','structured data','content architecture','local seo','maps visibility','voice search','featured snippet','search console','keyword research','citation optimization'],
        'ecommerce-conversion-rate-optimization-cro' => ['ecommerce','e commerce','commerce','cro','conversion rate','checkout','shopping cart','shopify','woocommerce','product pages','product listing','subscriptions','membership','google shopping','amazon advertising','funnel analysis','a b testing'],
        'data-analysis-visualization' => ['data','analytics','dashboard','dashboards','reporting','ga4','looker','kpi','attribution','tag management','business intelligence','anna','executive dashboard','tracking audit','data visualization'],
        'creative-technology-innovation' => ['technology innovation','technology innovation ai','creative technology','innovation','a i','ai automation','ai and automation','automation','ai assistant','ai agents','workflow automation','internal knowledge','prompt systems','prototype','calculator','configurator','interactive map','conversational interface'],
        'digital-marketing-growth-strategy' => ['digital marketing','growth strategy','marketing','campaign','content strategy','paid media','google ads','meta advertising','linkedin advertising','retargeting','email journeys','lead generation','demand generation','marketing automation','customer journey'],
        'website-development-core-tech' => ['website','web development','core tech','ux','ui','wordpress','drupal','cms','api integration','hosting','maintenance','accessibility','ada','performance','security','migration','replatforming','react','next js','node js','php'],
    ];
    foreach($map as $slug=>$needles){
        foreach($needles as $needle){
            if(strpos($t, ftc_normalize_prompt_text($needle)) !== false) return $slug;
        }
    }
    return '';
}

function ftc_find_service_by_search($term){
    $term = trim((string)$term);
    if ($term === '') return null;
    $mapped_slug = ftc_service_slug_for_prompt($term);
    if($mapped_slug){
        $mapped = get_page_by_path($mapped_slug, OBJECT, 'ftc_service');
        if($mapped && $mapped->post_status === 'publish') return $mapped->ID;
    }
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
    $needle = ftc_normalize_prompt_text($term);
    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'menu_order title','order'=>'ASC']);
    foreach($services as $service){
        $haystack = ftc_normalize_prompt_text(
            $service->post_title.' '.$service->post_excerpt.' '.$service->post_content.' '.
            get_post_meta($service->ID,'_ftc_service_eyebrow',true).' '.
            get_post_meta($service->ID,'_ftc_service_tasks',true)
        );
        if($needle && strpos($haystack, $needle) !== false) return $service->ID;
        foreach(explode(' ', $needle) as $part){
            if(strlen($part) >= 5 && strpos($haystack, $part) !== false) return $service->ID;
        }
    }
    return null;
}

function ftc_find_exact_service_by_prompt($term){
    $needle = ftc_normalize_prompt_text($term);
    if($needle === '') return null;
    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'menu_order title','order'=>'ASC']);
    foreach($services as $service){
        $title_key = ftc_normalize_prompt_text($service->post_title);
        $short_title_key = ftc_normalize_prompt_text(preg_replace('/\s*\([^)]+\)\s*/', ' ', $service->post_title));
        $slug_key = ftc_normalize_prompt_text($service->post_name);
        if($needle === $title_key || $needle === $short_title_key || $needle === $slug_key) return $service->ID;
        if(strlen($needle) >= 12 && $title_key && strpos($title_key, $needle) === 0) return $service->ID;
    }
    return null;
}

function ftc_service_child_match_score($needle, $key){
    if($needle === '' || $key === '') return 0;
    if($needle === $key) return 100;
    if(strlen($needle) >= 4 && strpos($key, $needle) !== false) return 82;
    $short_allowed = ['ai','api','ux','ui','seo','aeo','cro','ga4'];
    if(in_array($needle, $short_allowed, true) && preg_match('/(^|\s)'.preg_quote($needle,'/').'s?(\s|$)/', $key)) return 78;
    if(strlen($key) >= 8 && strpos($needle, $key) !== false) return 60;
    return 0;
}

function ftc_find_service_child_match($term){
    $needle = ftc_normalize_prompt_text($term);
    if($needle === '') return null;
    $preferred_slug = ftc_service_slug_for_prompt($term);
    $best = null;
    $best_score = 0;
    $services = get_posts(['post_type'=>'ftc_service','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'menu_order title','order'=>'ASC']);
    foreach($services as $service){
        $service_boost = ($preferred_slug && $service->post_name === $preferred_slug) ? 35 : 0;
        foreach(ftc_service_task_groups($service->ID) as $group=>$tasks){
            $group_key = ftc_normalize_prompt_text($group);
            $group_score = ftc_service_child_match_score($needle, $group_key);
            if($group_score){
                $score = $group_score + $service_boost;
                if($score > $best_score){
                    $best_score = $score;
                    $best = [
                    'service_id'=>$service->ID,
                    'service_title'=>get_the_title($service->ID),
                    'group'=>$group,
                    'task'=>$group,
                    'tasks'=>$tasks,
                    'is_group'=>true,
                    ];
                }
            }
            foreach($tasks as $task){
                $task_key = ftc_normalize_prompt_text($task);
                $task_score = ftc_service_child_match_score($needle, $task_key);
                if($task_score){
                    $score = $task_score + $service_boost;
                    if($score > $best_score){
                        $best_score = $score;
                        $best = [
                        'service_id'=>$service->ID,
                        'service_title'=>get_the_title($service->ID),
                        'group'=>$group,
                        'task'=>$task,
                        'tasks'=>$tasks,
                        'is_group'=>false,
                        ];
                    }
                }
            }
        }
    }
    return $best;
}

function ftc_is_exact_child_service_prompt($match, $term){
    if(!$match) return false;
    $needle = ftc_normalize_prompt_text($term);
    if($needle === '') return false;
    foreach(['task','group'] as $field){
        $value = ftc_normalize_prompt_text($match[$field] ?? '');
        if($value && $needle === $value) return true;
    }
    return false;
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
    $template_id = !empty($response['full_template_id']) ? absint($response['full_template_id']) : absint($response['template_id'] ?? 0);

    if($type === 'legacy' && in_array($layout, ['home','about','portfolio','services','contact'], true)){
        ftc_render_response_shell([
            'title'=>$title,
            'description'=>$desc,
            'html'=>$response['html'] ?? '',
            'layout'=>$layout,
            'followups'=>$response['followups'] ?? [],
        ], $settings);
        return;
    }

    echo '<div class="ftc-response-shell ftc-response-layout-'.esc_attr($layout).' ftc-response-source-cpt ftc-response-type-'.esc_attr($type).'" data-response-title="'.esc_attr($title).'">';
    echo '<header class="ftc-response-header"><div class="ftc-response-title-label">'.esc_html($title).'</div>';
    $typed = $desc ?: $title;
    echo '<h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($typed).'">'.esc_html($typed).'</h2>';
    echo '</header><section class="ftc-response-content">';

    $rendered = false;
    $preview_template_id = absint($response['preview_template_id'] ?? 0);

    if($preview_template_id && function_exists('ftc_render_elementor_template_by_id')){
        echo '<div class="ftc-response-preview-template">'.ftc_render_elementor_template_by_id($preview_template_id).'</div>';
    } elseif(!empty($response['content_preview'])){
        echo '<div class="ftc-response-preview-content">'.ftc_render_editable_html($response['content_preview']).'</div>';
    }

    if($type === 'elementor_template' && $template_id && function_exists('ftc_render_elementor_template_by_id')){
        echo '<div class="ftc-response-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div>';
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
        'portfolio'=>'portfolio','portfolios'=>'portfolio','all portfolios'=>'portfolio','show me all portfolios'=>'portfolio','work'=>'portfolio','project'=>'portfolio','case stud'=>'portfolio','show me'=>'portfolio',
        'service'=>'services','services'=>'services','all services'=>'services','show me all services'=>'services','our services'=>'services','help my company'=>'services','help my business'=>'services','what do you do'=>'services','improve our current site'=>'services','current site'=>'services','ecommerce'=>'services','hosting'=>'services','maintenance'=>'services','accessibility'=>'services','ada'=>'services',
        'web'=>'websites','ux'=>'websites','development'=>'websites','website'=>'websites','wordpress'=>'websites','drupal'=>'websites','better website'=>'websites',
        'seo'=>'marketing','aeo'=>'marketing','marketing'=>'marketing','search'=>'marketing','growth'=>'marketing',
        'data'=>'analytics','analytics'=>'analytics','dashboard'=>'analytics','report'=>'analytics','ga4'=>'analytics',
        'ai'=>'ai','automation'=>'ai','innovation'=>'ai',
        'contact'=>'contact','contact us'=>'contact','hire'=>'contact','hire our team'=>'contact','work together'=>'contact','call'=>'contact',
        'request proposal'=>'contact','request a proposal'=>'contact','proposal'=>'contact','schedule consultation'=>'contact','schedule a consultation'=>'contact','consultation'=>'contact','let us talk'=>'contact',"let's talk"=>'contact','lets talk'=>'contact','get started with a project'=>'contact',
        'get started'=>'get_started','start'=>'get_started','home'=>'get_started'
    ];
    foreach($map as $needle=>$key){ if(strpos($q,$needle)!==false && isset($responses[$key])) return $responses[$key]; }
    return $responses['get_started'] ?? reset($responses);
}

function ftc_is_about_prompt($term){
    $t = ftc_normalize_prompt_text($term);
    if($t === '') return false;
    return (bool)preg_match('/^(about|about us|about field theory|about field theory lab|tell me about your company|company|team|employees|people|who are you|field theory)$/', $t);
}

function ftc_is_portfolio_all_prompt($term){
    $t = ftc_normalize_prompt_text($term);
    return (bool)preg_match('/^(show me all portfolios|show me all projects|all portfolios|all projects|view all projects|portfolio all)$/', $t);
}

function ftc_is_contact_entry_prompt($term){
    return (bool)preg_match('/\b(contact|contact us|hire|hire our team|work together|request (a )?proposal|schedule (a )?consultation|let\'?s talk|call|inquiry|get started with a project)\b/i', (string)$term);
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
            'followups'=>['Helpful Prompts','Our Services','Request a Proposal']
        ];
        wp_reset_postdata();
        return $resp;
    }
    wp_reset_postdata();
    return null;
}

function ftc_direct_faq_response($term){
    $t = ftc_normalize_prompt_text($term);
    if($t === '') return null;

    $answers = [
        [
            'match'=>'/(how long|timeline|schedule|timeframe).*(website|site|project)|^(how long does a website take)$/',
            'title'=>'How long does a website take?',
            'description'=>'Most Field Theory website work falls into a few practical timeline ranges.',
            'html'=>'<p>Small website improvements or focused landing pages can often move in 2 to 6 weeks. A new marketing website usually lands closer to 8 to 14 weeks. Larger ecommerce, Drupal, content migration, accessibility, analytics, or integration-heavy projects can take 12 to 24 weeks or more.</p><p>The real answer depends on scope, content readiness, approvals, integrations, and how much strategy needs to happen before build. Field Theory usually starts by clarifying the goal, mapping the sections and systems, then giving you a practical timeline instead of a vague launch promise.</p>',
            'followups'=>['Request a Proposal','Website Development & Core Tech','What budget should I plan for?'],
        ],
        [
            'match'=>'/(budget|cost|price|pricing|how much|spend|investment)/',
            'title'=>'What budget should I plan for?',
            'description'=>'A useful budget depends on whether you need a focused fix, a rebuild, or an ongoing growth partner.',
            'html'=>'<p>For a focused website, analytics, SEO, or conversion improvement, many projects start in the lower five figures. Full website redesigns, ecommerce builds, custom integrations, accessibility remediation, or larger digital systems usually require a more substantial project budget. Ongoing support, reporting, SEO, marketing, and optimization can be scoped as a monthly partnership.</p><p>Field Theory is usually most helpful when we can see the current site, understand the business goal, and recommend a scope that matches the impact you want. The proposal quiz is the fastest way to get that conversation pointed in the right direction.</p>',
            'followups'=>['Request a Proposal','Our Services','How long does a website take?'],
        ],
        [
            'match'=>'/(measure|measurement|reporting|analytics|dashboard|kpi|roi|marketing working)/',
            'title'=>'How does Field Theory measure marketing?',
            'description'=>'We connect marketing activity to clearer signals, not just more reports.',
            'html'=>'<p>Field Theory can help define the right KPIs, clean up GA4 and tag management, connect campaign and conversion events, build dashboards, and turn reporting into a decision tool. The goal is to show what is working, what is not, and what should change next.</p><p>For many teams, that means fewer vanity metrics and better visibility into leads, sales, form quality, search visibility, content performance, ecommerce behavior, and the customer journey.</p>',
            'followups'=>['Data, Analysis & Visualization','Digital Marketing & Growth Strategy','Request a Proposal'],
        ],
        [
            'match'=>'/(proposal|request|work together|next step|start a project|hire)/',
            'title'=>'What happens after I request a proposal?',
            'description'=>'Field Theory uses the request to understand fit, scope, and the most useful next step.',
            'html'=>'<p>After you submit the proposal request, Field Theory reviews your services, goals, timeline, budget range, and any notes you share. From there, we can recommend a next step: a discovery call, a scoped audit, a phased plan, or a more detailed proposal.</p><p>The quiz is designed to collect the right context without making you write a giant brief from scratch.</p>',
            'followups'=>['Request a Proposal','Our Services','Show me your work!'],
        ],
        [
            'match'=>'/(hosting|maintenance|support|updates|care plan|managed)/',
            'title'=>'Can Field Theory help after launch?',
            'description'=>'Yes. We can support the ongoing technical, content, analytics, and optimization work.',
            'html'=>'<p>Field Theory can help with hosting guidance, WordPress and Drupal maintenance, updates, backups, performance checks, accessibility improvements, analytics, SEO/AEO, content support, reporting, and continuous improvement after launch.</p><p>Some teams need a launch partner. Others need an ongoing digital team. We can shape the support model around what your organization can manage internally and where you need help.</p>',
            'followups'=>['Website Development & Core Tech','Request a Proposal','Can you improve our current site?'],
        ],
        [
            'match'=>'/(accessibility|ada|wcag|compliance)/',
            'title'=>'Can Field Theory help with accessibility?',
            'description'=>'Yes. We can review, repair, and improve website accessibility in practical phases.',
            'html'=>'<p>Field Theory can help evaluate accessibility issues, improve content structure, forms, navigation, contrast, templates, components, and PDF or document workflows where needed. We can also help teams understand what should be fixed first and how to keep accessibility from becoming a one-time scramble.</p>',
            'followups'=>['Website Development & Core Tech','Request a Proposal','Can you improve our current site?'],
        ],
        [
            'match'=>'/(improve|fix|audit|current site|existing site|redesign|better website)/',
            'title'=>'Can Field Theory improve our current site?',
            'description'=>'Yes. We can help diagnose what is not working and improve it without forcing a full rebuild when that is not needed.',
            'html'=>'<p>Field Theory can audit the current experience, clarify messaging, improve UX, strengthen search visibility, clean up analytics, improve performance, address accessibility, modernize templates, and plan phased improvements around your goals.</p><p>Sometimes the right answer is a rebuild. Sometimes it is a smarter sequence of repairs. We help figure that out before jumping into production.</p>',
            'followups'=>['Website Development & Core Tech','Request a Proposal','Show me your work!'],
        ],
        [
            'match'=>'/(seo|aeo|ai search|search visibility|google|rank|ranking|answer engine)/',
            'title'=>'How does Field Theory help with SEO and AI search?',
            'description'=>'We work on the structure, content, and technical signals that help people and answer tools understand you.',
            'html'=>'<p>Field Theory can help with technical SEO, content architecture, structured data, local search, Search Console review, AI search visibility, answer engine optimization, FAQ structure, and clearer pages that explain what your organization provides.</p><p>The point is not generic SEO busywork. It is making your expertise easier to find, trust, cite, and act on.</p>',
            'followups'=>['Search & Discovery Optimization','Digital Marketing & Growth Strategy','Request a Proposal'],
        ],
        [
            'match'=>'/(ai|automation|assistant|workflow|internal knowledge|chatbot)/',
            'title'=>'Can Field Theory help with AI and automation?',
            'description'=>'Yes. We focus on practical AI systems that support real workflows.',
            'html'=>'<p>Field Theory can help with internal AI assistants, workflow automation, knowledge tools, reporting support, lead support flows, prototypes, and AI adoption planning. We try to keep AI useful, specific, and connected to the work your team actually does.</p>',
            'followups'=>['Technology, Innovation and A.I.','Data, Analysis & Visualization','Request a Proposal'],
        ],
        [
            'match'=>'/(ecommerce|conversion|cro|checkout|shopify|woocommerce|online store)/',
            'title'=>'How does Field Theory help ecommerce convert better?',
            'description'=>'We look at product journeys, checkout friction, analytics, and the decisions that affect revenue.',
            'html'=>'<p>Field Theory can help with ecommerce strategy, Shopify, WooCommerce, product pages, checkout optimization, subscriptions, conversion funnels, testing plans, analytics events, customer retention, and revenue-focused customer experience.</p>',
            'followups'=>['Ecommerce & Conversion Rate Optimization (CRO)','Request a Proposal','How do you measure marketing?'],
        ],
    ];

    foreach($answers as $answer){
        if(preg_match($answer['match'], $t)){
            return [
                'title'=>$answer['title'],
                'description'=>$answer['description'],
                'html'=>$answer['html'],
                'layout'=>'none',
                'followups'=>$answer['followups'],
            ];
        }
    }

    return null;
}

function ftc_help_prompt_items(){
    return [
        'Get Started',
        'Our Services',
        'Show me your work!',
        'Website Development & Core Tech',
        'Digital Marketing & Growth Strategy',
        'Search & Discovery Optimization',
        'Data, Analysis & Visualization',
        'Technology, Innovation and A.I.',
        'Ecommerce & Conversion',
        'Can you improve our current site?',
        'How long does a website take?',
        'What budget should I plan for?',
        'How do you measure marketing?',
        'Can you help with SEO and AI search?',
        'Can you help with ecommerce?',
        'Can you help with hosting and maintenance?',
        'Can you help with accessibility?',
        'Can you help with AI and automation?',
        'What happens after I request a proposal?',
        'Request a Proposal',
    ];
}

function ftc_render_helpful_prompt_response($settings=[]){
    echo '<div class="ftc-response-shell ftc-response-layout-help-prompts" data-response-title="'.esc_attr('Helpful Prompts').'" data-ftc-response-prompt="'.esc_attr('Helpful Prompts').'">';
    ftc_render_response_header_markup('Helpful prompts','Choose a topic and I will take you to the most useful Field Theory response.',$settings);
    echo '<section class="ftc-response-content"><div class="ftc-help-cloud ftc-response-prompt-cloud">';
    foreach(ftc_help_prompt_items() as $prompt){
        echo '<button type="button" data-prompt="'.esc_attr($prompt).'">'.esc_html($prompt).'</button>';
    }
    echo '</div></section></div>';
}

function ftc_ajax_answer(){
    check_ajax_referer('ftc_nonce','nonce');
    $term = sanitize_text_field(wp_unslash($_POST['term'] ?? ''));
    $settings = ftc_get_settings();

    if(ftc_is_about_prompt($term)){
        $responses = ftc_get_responses();
        $response = $responses['about'] ?? ftc_pick_response($term);
        ob_start();
        ftc_render_response_shell($response,$settings,$term);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    if(ftc_is_contact_entry_prompt($term)){
        $responses = ftc_get_responses();
        $response = $responses['contact'] ?? ftc_pick_response($term);
        ob_start();
        ftc_render_response_shell($response,$settings,$term);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    if(ftc_is_portfolio_all_prompt($term)){
        ob_start();
        ftc_open_response_shell('portfolio-all','All Portfolio Projects','Browse the wider Field Theory project list.',$settings,'Show Me All Portfolios','ftc-response-sequence ftc-response-sequence-portfolio-all');
        echo '<section class="ftc-response-content">';
        ftc_render_portfolio_all_content();
        echo '</section>';
        ftc_close_response_shell(['Our Services','Request a Proposal']);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    if(preg_match('/^\s*(faq|faqs|frequently asked questions|questions|question mark|helpful prompts|help prompts|help)\s*$/i', $term)){
        ob_start();
        ftc_render_helpful_prompt_response($settings);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    $exact_service_id = ftc_find_exact_service_by_prompt($term);
    if($exact_service_id){
        ob_start();
        ftc_render_service_detail_response_markup($exact_service_id);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    $child_service = ftc_find_service_child_match($term);
    if(ftc_is_exact_child_service_prompt($child_service, $term)){
        ob_start();
        ftc_render_child_service_response($child_service, $settings);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    $faq_direct = ftc_direct_faq_response($term);
    if($faq_direct){
        ob_start();
        ftc_render_response_shell($faq_direct,$settings,$term);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    $faq_cpt_direct = ftc_find_faq_answer_by_search($term);
    if($faq_cpt_direct && preg_match('/\?|how|what|why|when|where|can|do you|should|cost|price|timeline|hosting|maintenance|support|seo|analytics|ai/i', $term)){
        ob_start(); ftc_render_response_shell($faq_cpt_direct,$settings,$term); $html=ob_get_clean(); wp_send_json_success(['html'=>$html]);
    }

    if($child_service){
        ob_start();
        ftc_render_child_service_response($child_service, $settings);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

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
    $portfolio_id = ftc_find_portfolio_by_search($term);
    if($portfolio_id){
        ob_start();
        ftc_render_project_detail_markup($portfolio_id);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }
    $service_id = ftc_find_service_by_search($term);
    if($service_id && (ftc_service_slug_for_prompt($term) || preg_match('/service|web|site|seo|aeo|marketing|data|analytics|ecommerce|ai|automation|development|design|google|ads|campaign|checkout|schema|dashboard|ga4|drupal|wordpress|hosting|maintenance|accessibility|cro|shopify|woocommerce|conversion|content|paid|crm|api/i', $term))){
        $focus_label = ftc_service_focus_label($service_id, $term);
        ob_start();
        ftc_render_service_detail_response_markup($service_id, $focus_label);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }
    $response = ftc_pick_response($term);
    ob_start(); ftc_render_response_shell($response,$settings,$term); $html = ob_get_clean();
    wp_send_json_success(['html'=>$html]);
}
add_action('wp_ajax_ftc_answer','ftc_ajax_answer');
add_action('wp_ajax_nopriv_ftc_answer','ftc_ajax_answer');

function ftc_ajax_sequence_fragment(){
    check_ajax_referer('ftc_nonce','nonce');
    $sequence = sanitize_text_field(wp_unslash($_POST['sequence'] ?? ''));
    $index = absint($_POST['index'] ?? 0);
    if($sequence !== 'get-started' || $index < 1 || $index > 3){
        wp_send_json_error(['message'=>'Sequence fragment not found.'], 404);
    }

    $settings = ftc_get_settings();
    $responses = ftc_get_responses();
    $response = $responses['get_started'] ?? ftc_pick_response('Get Started');
    ob_start();
    ftc_render_get_started_sequence_fragment($index,$settings,$response);
    $html = ob_get_clean();
    wp_send_json_success(['html'=>$html]);
}
add_action('wp_ajax_ftc_sequence_fragment','ftc_ajax_sequence_fragment');
add_action('wp_ajax_nopriv_ftc_sequence_fragment','ftc_ajax_sequence_fragment');

function ftc_ajax_menu(){
    check_ajax_referer('ftc_nonce','nonce');
    ob_start();
    echo '<div class="ftc-explore-list">';
    foreach([
        ['Get Started with Field Theory','Get Started'],
        ['Tell me about your company','Tell me about your company'],
        ['What services do you provide','Our Services'],
        ['Show me your work','Show me your work!'],
        ['Explore FAQs','FAQ'],
        ['Request a Proposal or Contact Us','Request a Proposal']
    ] as $item){
        echo '<button type="button" class="ftc-menu-prompt" data-prompt="'.esc_attr($item[1]).'"><span>'.esc_html($item[0]).'</span></button>';
    }
    echo '</div>';
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_menu','ftc_ajax_menu');
add_action('wp_ajax_nopriv_ftc_menu','ftc_ajax_menu');

function ftc_clean_json_text_list($raw){
    $decoded = json_decode(wp_unslash((string)$raw), true);
    if(!is_array($decoded)) return [];
    $clean = [];
    foreach($decoded as $item){
        $item = sanitize_text_field((string)$item);
        if($item !== '') $clean[] = $item;
    }
    return array_values(array_unique($clean));
}

function ftc_calculate_lead_score($services, $timeline, $budget){
    $score = 0;
    foreach((array)$services as $service){
        if(preg_match('/Website|SEO|Analytics|AI/i', $service)) $score += 20;
        elseif(preg_match('/Ecommerce|Digital Marketing|Strategy/i', $service)) $score += 15;
        elseif(preg_match('/Hosting|ADA|Creative/i', $service)) $score += 10;
    }
    if($timeline === 'Immediately') $score += 25;
    elseif($timeline === 'Within 30 Days') $score += 20;
    elseif($timeline === 'Within 90 Days') $score += 15;
    elseif($timeline === 'Within 6 Months') $score += 10;

    if($budget === '$50,000+') $score += 25;
    elseif($budget === '$15,000-$50,000') $score += 20;
    elseif($budget === '$5,000-$15,000') $score += 10;

    $score = min(100, max(0, absint($score)));
    return [
        'score' => $score,
        'priority' => $score > 70 ? 'High' : ($score > 40 ? 'Medium' : 'Low'),
    ];
}

function ftc_verify_recaptcha_token($token, $settings){
    $site_key = trim((string)($settings['recaptcha_site_key'] ?? ''));
    $secret_key = trim((string)($settings['recaptcha_secret_key'] ?? ''));
    if($site_key === '' || $secret_key === '') return true;
    if(trim((string)$token) === '') return new WP_Error('ftc_recaptcha_missing', 'Please retry the captcha check.');

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'timeout' => 8,
        'body' => [
            'secret' => $secret_key,
            'response' => $token,
            'remoteip' => sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? '')),
        ],
    ]);

    if(is_wp_error($response)) return new WP_Error('ftc_recaptcha_unavailable', 'Captcha verification is temporarily unavailable.');

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if(empty($body['success'])) return new WP_Error('ftc_recaptcha_failed', 'Captcha verification failed. Please try again.');

    if(!empty($body['action']) && $body['action'] !== 'ftc_submit_inquiry'){
        return new WP_Error('ftc_recaptcha_action', 'Captcha verification failed. Please refresh and try again.');
    }

    $score = isset($body['score']) ? (float)$body['score'] : 1;
    $threshold = isset($settings['recaptcha_threshold']) ? (float)$settings['recaptcha_threshold'] : 0.5;
    if($score < max(0, min(1, $threshold))){
        return new WP_Error('ftc_recaptcha_score', 'Captcha verification failed. Please try again.');
    }

    return true;
}

function ftc_ajax_submit_inquiry(){
    check_ajax_referer('ftc_nonce','nonce');

    $services = ftc_clean_json_text_list($_POST['services'] ?? '[]');
    $company = sanitize_text_field(wp_unslash($_POST['company'] ?? ''));
    $website = esc_url_raw(wp_unslash($_POST['website'] ?? ''));
    $org_type = sanitize_text_field(wp_unslash($_POST['org_type'] ?? ''));
    $challenge = sanitize_text_field(wp_unslash($_POST['challenge'] ?? ''));
    $timeline = sanitize_text_field(wp_unslash($_POST['timeline'] ?? ''));
    $budget = sanitize_text_field(wp_unslash($_POST['budget'] ?? ''));
    $notes = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $contact_method = sanitize_text_field(wp_unslash($_POST['contact_method'] ?? 'Email'));
    $recaptcha_token = sanitize_text_field(wp_unslash($_POST['recaptcha_token'] ?? ''));

    if($name === '' || !is_email($email)){
        wp_send_json_error(['message'=>'Please provide a valid name and email.'], 400);
    }

    $settings = ftc_get_settings();
    $captcha = ftc_verify_recaptcha_token($recaptcha_token, $settings);
    if(is_wp_error($captcha)){
        wp_send_json_error(['message'=>$captcha->get_error_message()], 403);
    }

    $score = ftc_calculate_lead_score($services, $timeline, $budget);
    $title_name = $company ?: $name;
    $lead_id = wp_insert_post([
        'post_type'=>'ftc_lead',
        'post_status'=>'publish',
        'post_title'=>sprintf('Concierge Lead - %s - %s', $title_name, current_time('Y-m-d H:i')),
    ]);

    if(!$lead_id || is_wp_error($lead_id)){
        wp_send_json_error(['message'=>'Could not save proposal request.'], 500);
    }

    $meta = [
        '_ftc_lead_services'=>$services,
        '_ftc_lead_company'=>$company,
        '_ftc_lead_website'=>$website,
        '_ftc_lead_org_type'=>$org_type,
        '_ftc_lead_challenge'=>$challenge,
        '_ftc_lead_timeline'=>$timeline,
        '_ftc_lead_budget'=>$budget,
        '_ftc_lead_notes'=>$notes,
        '_ftc_lead_name'=>$name,
        '_ftc_lead_email'=>$email,
        '_ftc_lead_phone'=>$phone,
        '_ftc_lead_contact_method'=>$contact_method,
        '_ftc_lead_score'=>$score['score'],
        '_ftc_lead_priority'=>$score['priority'],
        '_ftc_lead_timestamp'=>current_time('mysql'),
    ];
    foreach($meta as $key=>$value) update_post_meta($lead_id, $key, $value);

    $to = sanitize_email($settings['contact_email'] ?? '');
    if(!$to || !is_email($to)) $to = 'jamie@fieldtheory.ai';

    $body = "New Field Theory Concierge proposal request\n\n";
    $body .= "Priority: {$score['priority']} ({$score['score']}/100)\n";
    $body .= "Services: ".implode(', ', $services)."\n";
    $body .= "Company: {$company}\n";
    $body .= "Website: {$website}\n";
    $body .= "Organization Type: {$org_type}\n";
    $body .= "Challenge: {$challenge}\n";
    $body .= "Timeline: {$timeline}\n";
    $body .= "Budget: {$budget}\n\n";
    $body .= "Name: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Phone: {$phone}\n";
    $body .= "Preferred Contact: {$contact_method}\n\n";
    $body .= "Notes:\n{$notes}\n\n";
    $body .= "Lead ID: {$lead_id}\n";

    $sent = wp_mail($to, 'New Field Theory Concierge proposal request', $body, ['Reply-To: '.$name.' <'.$email.'>']);

    wp_send_json_success([
        'lead_id'=>$lead_id,
        'score'=>$score['score'],
        'priority'=>$score['priority'],
        'email_sent'=>(bool)$sent,
    ]);
}
add_action('wp_ajax_ftc_submit_inquiry','ftc_ajax_submit_inquiry');
add_action('wp_ajax_nopriv_ftc_submit_inquiry','ftc_ajax_submit_inquiry');


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

function ftc_render_response_header_markup($title,$desc,$settings=[]){
    echo '<header class="ftc-response-header"><div class="ftc-kicker">'.esc_html($settings['descriptor'] ?? 'Web Design and Digital Marketing').'</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($title).'">'.esc_html($title).'</h2>';
    if($desc) echo '<div class="ftc-answer-description">'.esc_html($desc).'</div>';
    echo '</header>';
}

function ftc_open_response_shell($layout,$title,$desc,$settings=[],$prompt='',$extra_class=''){
    $classes = trim('ftc-response-shell ftc-response-layout-'.sanitize_html_class($layout).' '.$extra_class);
    echo '<div class="'.esc_attr($classes).'"'.($prompt ? ' data-ftc-response-prompt="'.esc_attr($prompt).'"' : '').' data-response-title="'.esc_attr($title).'">';
    ftc_render_response_header_markup($title,$desc,$settings);
}

function ftc_close_response_shell($followups=[]){
    ftc_render_followups($followups);
    echo '</div>';
}

function ftc_render_scroll_more_button($label='Scroll for more'){
    echo '<button type="button" class="ftc-scroll-more" data-ftc-scroll-more aria-label="'.esc_attr($label).'"><span aria-hidden="true"></span></button>';
}

function ftc_render_response_shell($response,$settings=[],$search_term=''){
    $title=$response['title'] ?? 'Get Started.'; $desc=$response['description'] ?? ''; $layout=$response['layout'] ?? 'none';
    if($layout === 'home'){
        ftc_render_get_started_sequence($response,$settings,true);
        return;
    }
    if($layout === 'portfolio'){
        ftc_render_portfolio_sequence($response,$settings);
        return;
    }
    if($layout === 'services'){
        ftc_render_services_sequence($response,$settings);
        return;
    }
    echo '<div class="ftc-response-shell ftc-response-layout-'.esc_attr($layout).'">';
    ftc_render_response_header_markup($title,$desc,$settings);
    if(!empty($response['html']) && !in_array($layout, ['about','contact'], true)) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
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

function ftc_render_get_started_sequence($response,$settings,$defer_fragments=false){
    $title=$response['title'] ?? 'Get Started.'; $desc=$response['description'] ?? '';
    ftc_open_response_shell('home',$title,$desc,$settings,'Get Started','ftc-response-sequence ftc-response-sequence-start');
    if(!empty($response['html'])) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
    echo '<section class="ftc-response-content">';
    if($defer_fragments){
        echo '<span hidden data-ftc-deferred-sequence="get-started" data-ftc-deferred-next="1" data-ftc-deferred-total="4" data-ftc-deferred-prompts="'.esc_attr('How can you help my company?|Show me your work!|Testimonials').'"></span>';
    }
    ftc_render_home_intro_panel($settings);
    ftc_render_scroll_more_button('Scroll to services');
    echo '</section>';
    ftc_close_response_shell([]);

    if($defer_fragments) return;

    ftc_render_get_started_sequence_fragment(1,$settings,$response);
    ftc_render_get_started_sequence_fragment(2,$settings,$response);
    ftc_render_get_started_sequence_fragment(3,$settings,$response);
}

function ftc_render_get_started_sequence_fragment($index,$settings,$response=[]){
    $index = absint($index);
    if($index === 1){
    ftc_open_response_shell('services','Our Services','Explore the main ways Field Theory helps organizations improve websites, marketing, analytics, automation, ecommerce, and customer experience.',$settings,'How can you help my company?','ftc-response-sequence ftc-response-sequence-services');
    echo '<section class="ftc-response-content">';
    ftc_render_services_section_one();
    echo '</section>';
    ftc_close_response_shell([]);
        return;
    }

    if($index === 2){
    ftc_open_response_shell('portfolio','Our Work','A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.',$settings,'Show me your work!','ftc-response-sequence ftc-response-sequence-portfolio');
    echo '<section class="ftc-response-content">';
    ftc_render_portfolio_section_one();
    echo '</section>';
    ftc_close_response_shell([]);
        return;
    }

    if($index === 3){
    ftc_open_response_shell('testimonials','Testimonials','A few notes on what teams value when they work with Field Theory Lab.',$settings,'Testimonials','ftc-response-sequence ftc-response-sequence-testimonials');
    echo '<section class="ftc-response-content">';
    ftc_render_testimonials_panel();
    echo '</section>';
    ftc_close_response_shell($response['followups'] ?? []);
    }
}

function ftc_render_portfolio_sequence($response,$settings){
    $title=$response['title'] ?? 'Our Work';
    $desc=$response['description'] ?? 'A sample of Field Theory projects.';
    ftc_open_response_shell('portfolio',$title,$desc,$settings,'Show me your work!','ftc-response-sequence ftc-response-sequence-portfolio');
    if(!empty($response['html'])) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
    echo '<section class="ftc-response-content">';
    ftc_render_portfolio_section_one();
    echo '</section>';
    ftc_close_response_shell($response['followups'] ?? []);
}

function ftc_render_services_sequence($response,$settings){
    $title=$response['title'] ?? 'Our Services';
    $desc=$response['description'] ?? 'Website development, marketing, analytics, ecommerce, creative technology, and practical AI systems.';
    ftc_open_response_shell('services',$title,$desc,$settings,'How can you help my company?','ftc-response-sequence ftc-response-sequence-services');
    if(!empty($response['html'])) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
    echo '<section class="ftc-response-content">';
    ftc_render_services_section_one();
    echo '</section>';
    ftc_close_response_shell($response['followups'] ?? []);
}

function ftc_render_home_intro_panel($settings){
    $video = $settings['demo_video_url'] ?? '';
    echo '<div class="ftc-home-overview">';
    echo '<div class="ftc-home-media">';
    if($video){
        echo '<video controls autoplay muted loop playsinline preload="metadata"><source src="'.esc_url($video).'"></video>';
    } else {
        echo '<img src="'.esc_url(FTC_URL.'assets/images/placeholder-gray-16x9.svg').'" alt="Field Theory overview">';
    }
    echo '</div>';
    echo '<div class="ftc-home-copy"><h3>Better technology. Smarter marketing. Clearer growth.</h3><p>Field Theory Lab helps organizations grow through better technology, smarter marketing, deeper insights, and practical AI. We design better web experiences, improve digital performance, and build systems that help teams make smarter decisions.</p><button class="ftc-red-link" type="button" data-prompt="How can you help my company?">How can you help my company?</button></div>';
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
    echo '<div class="ftc-home-copy"><h3>Better technology. Smarter marketing. Clearer growth.</h3><p>Field Theory Lab helps organizations grow through better technology, smarter marketing, deeper insights, and practical AI. We design better web experiences, improve digital performance, and build systems that help teams make smarter decisions.</p><button class="ftc-red-link" type="button" data-prompt="How can you help my company?">How can you help my company?</button></div>';
    echo '</div></div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-services" data-response-section="services"><header class="ftc-subresponse-header"><div><span>How can you help my company?</span><h3>Our Services</h3><p>Explore the main ways Field Theory helps organizations improve websites, marketing, analytics, automation, ecommerce, and customer experience.</p></div><button type="button" data-prompt="How can you help my company?">Explore services</button></header>';
    ftc_render_services_panel(false);
    echo '</div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-work" data-response-section="portfolio"><header class="ftc-subresponse-header"><div><span>Show me your work!</span><h3>Our Work</h3><p>A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.</p></div><button type="button" data-prompt="Show me your work!">Show me your work!</button></header>';
    echo '<div class="ftc-portfolio-latest-wrap"><button type="button" class="ftc-carousel-arrow ftc-carousel-prev ftc-portfolio-prev" data-ftc-portfolio-prev aria-label="Previous projects">‹</button>';
    ftc_render_portfolio_masonry(9);
    echo '<button type="button" class="ftc-carousel-arrow ftc-carousel-next ftc-portfolio-next" data-ftc-portfolio-next aria-label="Next projects">›</button></div></div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-testimonials" data-response-section="testimonials"><header class="ftc-subresponse-header"><div><span>What do clients say?</span><h3>Testimonials</h3><p>A few notes on what teams value when they work with Field Theory Lab.</p></div><button type="button" data-prompt="Testimonials">Read testimonials</button></header>';
    ftc_render_testimonials_panel();
    echo '</div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-about" data-response-section="about"><header class="ftc-subresponse-header"><div><span>Tell me about your company</span><h3>About Field Theory</h3><p>Field Theory Lab is a creative technology agency based in Albuquerque, New Mexico.</p></div><button type="button" data-prompt="Tell me about your company">Meet Field Theory</button></header>';
    ftc_render_about_panel($settings);
    echo '</div>';
}
function ftc_render_about_panel($settings){
    echo '<div class="ftc-about-layout ftc-about-redesign ftc-two-column-response">';
    echo '<section class="ftc-about-copy"><h3>Quick read on Field Theory.</h3><p>Field Theory Lab is a creative technology agency in Albuquerque helping organizations make websites, marketing, analytics, and digital systems clearer and more useful.</p>';
    echo '<ul class="ftc-about-glance">';
    echo '<li><strong>Creative:</strong> Better messaging, UX, design systems, content, and digital experiences.</li>';
    echo '<li><strong>Technical:</strong> WordPress, Drupal, ecommerce, integrations, hosting, maintenance, automation, and AI support.</li>';
    echo '<li><strong>Strategic:</strong> Clearer decisions, measurable growth, better reporting, and practical next steps.</li>';
    echo '</ul>';
    echo '<button class="ftc-green-btn" type="button" data-prompt="Request a Proposal">Request a Proposal</button></section>';
    $team = [
        ['initials'=>'JG','name'=>'Jamie Rushad Gros','role'=>'CEO / Technology Strategist / Technical Lead','bio'=>'Jamie brings 25 years of experience as a website developer and digital marketer. He works at the intersection of technology, creative, and marketing to help organizations make better digital decisions.'],
        ['initials'=>'TQ','name'=>'Tyler Quintana','role'=>'Full-Stack Developer','bio'=>'Tyler is a web developer with a business background and a graduate of the CNM Ingenuity Full-Stack Developer bootcamp. He focuses on practical web solutions that connect technical decisions to client needs.'],
        ['initials'=>'HS','name'=>'Hastimal (Hasti) Shah','role'=>'Website Developer','bio'=>'Hasti has worked on more than 200 WordPress and Drupal websites, with expertise across Elementor, Beaver Builder, Bricks, Gutenberg, DIVI, and major WordPress theme ecosystems.'],
    ];
    echo '<aside class="ftc-about-profile ftc-about-team" aria-label="Field Theory team">';
    echo '<div class="ftc-team-roster">';
    foreach($team as $member){
        echo '<article class="ftc-team-member-card"><div class="ftc-team-initials">'.esc_html($member['initials']).'</div><div><span>'.esc_html($member['role']).'</span><h3>'.esc_html($member['name']).'</h3><p>'.esc_html($member['bio']).'</p></div></article>';
    }
    echo '</div>';
    echo '<div class="ftc-profile-meta ftc-team-contact"><dl><div><dt>Based in</dt><dd>Albuquerque, New Mexico</dd></div><div><dt>Email</dt><dd><a href="mailto:jamie@fieldtheory.ai">jamie@fieldtheory.ai</a></dd></div></dl></div>';
    echo '</aside></div>';
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

function ftc_render_service_card_markup($item, $featured=false){
    $image = $item['image'] ?: ftc_service_default_image($item['title'].' '.$item['eyebrow']);
    $badges = ftc_service_badges_for_title($item['title']);
    echo '<article class="ftc-service-card'.($featured ? ' ftc-service-featured' : '').'"><button type="button" class="ftc-service-open" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
    echo '<div class="ftc-service-art"><img src="'.esc_url($image).'" alt="'.esc_attr($item['title']).'" loading="lazy"></div>';
    echo '<strong>'.esc_html($item['title']).'</strong><p>'.esc_html($item['desc']).'</p>';
    echo '<div class="ftc-tech-badges">'; foreach($badges as $badge) echo '<span>'.esc_html($badge).'</span>'; echo '</div>';
    echo '<em>Explore service &rarr;</em></button></article>';
}

function ftc_service_child_labels($item, $limit=4){
    $labels = [];
    foreach(($item['tasks'] ?? []) as $task){
        $task = trim((string)$task);
        if($task === '') continue;
        if(strpos($task, ':') !== false){
            [$label] = array_map('trim', explode(':', $task, 2));
            if($label !== '') $labels[] = $label;
        } else {
            $labels[] = $task;
        }
        if(count($labels) >= $limit) break;
    }
    return $labels;
}

function ftc_service_task_groups($id){
    $tasks_raw = get_post_meta($id,'_ftc_service_tasks',true);
    $lines = array_values(array_filter(array_map('trim',explode("\n",$tasks_raw))));
    $groups = [];
    foreach($lines as $line){
        if(strpos($line,':') !== false){
            [$label,$items] = array_map('trim',explode(':',$line,2));
            $items = array_values(array_filter(array_map('trim',preg_split('/[,;]+/',$items))));
            if($label && $items) $groups[$label] = $items;
        }
    }
    if(!$groups){
        $labels = ['Strategy & Planning','Design & Experience','Development & Systems','Optimization & Support'];
        foreach(array_chunk($lines ?: ['Discovery and strategy','Design and UX','Technical implementation','Measurement and optimization'], max(1,ceil(max(1,count($lines))/4))) as $i=>$chunk){
            $groups[$labels[$i] ?? 'Service Tasks'] = $chunk;
        }
    }
    return $groups;
}

function ftc_service_focus_label($id, $term){
    $needle = ftc_normalize_prompt_text($term);
    if($needle === '') return '';
    foreach(ftc_service_task_groups($id) as $group=>$items){
        if(ftc_normalize_prompt_text($group) === $needle || strpos(ftc_normalize_prompt_text($group), $needle) !== false) return $group;
        foreach($items as $item){
            $normalized = ftc_normalize_prompt_text($item);
            if($normalized === $needle || strpos($normalized, $needle) !== false || strpos($needle, $normalized) !== false) return $item;
        }
    }
    return '';
}

function ftc_service_detail_headline($id){
    $slug = (string)get_post_field('post_name', $id);
    $map = [
        'website-development-core-tech' => 'Field Theory can help you build a clearer, faster, more useful website.',
        'digital-marketing-growth-strategy' => 'Field Theory can help turn scattered marketing activity into a clearer growth system.',
        'search-discovery-optimization-seo-aeo' => 'Field Theory can help more people, search engines, and answer tools understand and trust your organization.',
        'ecommerce-conversion-rate-optimization-cro' => 'Field Theory can help you convert more online buyers.',
        'data-analysis-visualization' => 'Field Theory can help turn confusing data into decisions your team can actually use.',
        'creative-technology-innovation' => 'Field Theory can help turn useful AI, automation, and product ideas into working tools.',
    ];
    return $map[$slug] ?? 'Field Theory can help turn the right digital work into clearer business progress.';
}

function ftc_render_service_detail_response_markup($service_id, $focus_label=''){
    $title = get_the_title($service_id);
    $headline = ftc_service_detail_headline($service_id);
    echo '<div class="ftc-response-shell ftc-response-layout-service-detail" data-response-title="'.esc_attr($title).'" data-ftc-response-prompt="'.esc_attr($title).'" data-ftc-service-id="'.esc_attr($service_id).'"><header class="ftc-response-header"><div class="ftc-kicker">Our Services</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($title).'">'.esc_html($title).'</h2><div class="ftc-answer-description">'.esc_html($headline).'</div></header><section class="ftc-response-content">';
    ftc_render_service_detail_by_id($service_id, $focus_label);
    echo '</section></div>';
}

function ftc_render_service_category_card_markup($item){
    $labels = ftc_service_child_labels($item, 4);
    echo '<article class="ftc-service-category-card"><button type="button" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
    echo '<span>'.esc_html($item['eyebrow'] ?: 'SERVICE').'</span>';
    echo '<strong>'.esc_html($item['title']).'</strong>';
    echo '<p>'.esc_html($item['desc']).'</p>';
    if($labels){
        echo '<div class="ftc-service-child-chips">';
        foreach($labels as $label) echo '<i>'.esc_html($label).'</i>';
        echo '</div>';
    }
    echo '<em>Explore this service &rarr;</em>';
    echo '</button></article>';
}

function ftc_render_services_section_one(){
    $items=ftc_get_services(6);
    if(!$items){ return; }
    echo '<section class="ftc-services-section-one ftc-services-category-select" data-ftc-section-one><div class="ftc-services-category-grid">';
    foreach($items as $item) ftc_render_service_category_card_markup($item);
    echo '</div>';
    ftc_render_scroll_more_button('Scroll for more');
    echo '</section>';
}

function ftc_render_services_all_content(){
    $items=ftc_get_services(-1);
    if(!$items){ return; }
    echo '<section class="ftc-services-all-content ftc-services-child-directory" aria-label="All Field Theory services">';
    foreach($items as $item){
        echo '<article class="ftc-child-service-group">';
        echo '<header><span>'.esc_html($item['eyebrow'] ?: 'SERVICE').'</span><h3>'.esc_html($item['title']).'</h3><p>'.esc_html($item['desc']).'</p><button type="button" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">Open full service</button></header>';
        echo '<div class="ftc-child-service-grid">';
        foreach(ftc_service_task_groups($item['id']) as $group=>$tasks){
            echo '<section><h4>'.esc_html($group).'</h4><div>';
            foreach($tasks as $task){
                echo '<button type="button" data-prompt="'.esc_attr($task).'">'.esc_html($task).'</button>';
            }
            echo '</div></section>';
        }
        echo '</div></article>';
    }
    echo '</section>';
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
        echo '<article class="ftc-service-card"><button type="button" class="ftc-service-open" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
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

function ftc_child_service_points($task, $service_title='', $group=''){
    $task = trim((string)$task);
    $service_title = trim((string)$service_title);
    $group = trim((string)$group);
    $key = ftc_normalize_prompt_text($task);
    $service_key = ftc_normalize_prompt_text($service_title);
    $context = $service_title ?: 'Field Theory service';
    $topic = $task ?: 'this service';

    $rules = [
        ['/(hosting|infrastructure|cloud hosting|pantheon|acquia|flywheel|wp engine)/', [
            'Evaluate the right hosting environment for '.$topic.', including uptime, staging, backups, deployment workflow, and long-term ownership.',
            'Coordinate platform choices with security, performance, governance, and the internal team that will rely on the site after launch.',
            'Document a support model so hosting is not just where the site lives, but part of a maintainable digital operation.',
        ]],
        ['/(accessibility|ada|wcag)/', [
            'Review '.$topic.' through practical WCAG expectations, including structure, contrast, navigation, forms, media, and editor workflows.',
            'Prioritize accessibility fixes by user impact, technical complexity, and what your team can maintain after the first remediation pass.',
            'Create clear notes for design, development, content, and reporting so accessibility becomes part of the site process.',
        ]],
        ['/(maintenance|support|updates|hardening|security)/', [
            'Set up '.$topic.' around a realistic maintenance rhythm: updates, backups, monitoring, issue triage, and release checks.',
            'Protect the site with sensible security practices, platform review, dependency management, and escalation paths.',
            'Keep improvement work visible so support covers both technical health and the user-facing details that affect trust.',
        ]],
        ['/(performance|speed|core web)/', [
            'Audit '.$topic.' across load time, media weight, scripts, caching, templates, mobile behavior, and real user friction.',
            'Prioritize fixes that improve the experience without stripping away the design, content, or functionality users need.',
            'Measure before and after so performance work becomes a visible improvement, not a vague technical cleanup.',
        ]],
        ['/(wordpress|drupal|cms|enterprise cms|custom cms|content management)/', [
            'Shape '.$topic.' around editor usability, governance, flexible templates, reusable components, and long-term content operations.',
            'Build the CMS experience so marketing and communications teams can manage pages without breaking the system.',
            'Plan permissions, training, documentation, and release workflow so the platform stays useful after launch.',
        ]],
        ['/(migration|replatform|headless|react|next js|node js|php|custom web application|website development|api|crm|erp|integration)/', [
            'Map the technical requirements for '.$topic.', including data flow, dependencies, integrations, environments, and launch risk.',
            'Build the implementation in phases so complex web, API, CMS, and application work stays testable and understandable.',
            'Leave your team with cleaner documentation, handoff notes, and a system that can keep evolving.',
        ]],
        ['/(ux|ui|interface|information architecture|website strategy|governance|training)/', [
            'Use '.$topic.' to make the website easier to understand, easier to navigate, and easier for the right user to act on.',
            'Turn audience needs, content structure, and business priorities into a clearer page and interaction plan.',
            'Support the rollout with practical governance, training, and design decisions your team can sustain.',
        ]],
        ['/(checkout|shopping cart|product|shopify|woocommerce|subscription|ecommerce|commerce|loyalty|retention|amazon|google shopping|meta commerce|product listing)/', [
            'Evaluate '.$topic.' through the buyer journey: product confidence, cart friction, checkout clarity, payment flow, and post-purchase behavior.',
            'Use analytics and UX review to find where shoppers hesitate, abandon, or need more confidence before buying.',
            'Prioritize improvements that can increase conversion rate, order value, retention, or merchandising clarity.',
        ]],
        ['/(conversion|cro|a b testing|landing page|funnel|user behavior|revenue attribution|customer journey)/', [
            'Study '.$topic.' with a conversion lens: what users need, where the funnel leaks, and which changes are worth testing.',
            'Connect page behavior, analytics events, traffic source, and business outcome so optimization is grounded in evidence.',
            'Create a testable improvement plan instead of a random list of page tweaks.',
        ]],
        ['/(dashboard|reporting|business intelligence|executive|custom reporting|automated reporting|visualization|anna)/', [
            'Shape dashboards and reports around the decisions your team actually needs to make, not every metric the tools can export.',
            'Combine clean data structure, useful filters, and readable visual hierarchy so reporting becomes easier to trust.',
            'Build dashboards and reporting flows that can support leadership, marketing, sales, and operations without extra spreadsheet work.',
        ]],
        ['/(ga4|google analytics|tag management|data collection|data governance|kpi|north star|attribution|internal database|custom api|crm platform|advertising platform|ecommerce platform)/', [
            'Set up '.$topic.' so tracking, data sources, naming, and ownership are clear before reports are built.',
            'Connect analytics implementation to the questions your team needs answered across marketing, website, sales, and operations.',
            'Create cleaner measurement foundations so dashboards and insights do not depend on messy assumptions.',
        ]],
        ['/(marketing analytics|conversion analytics|behavioral|predictive|lead generation reporting|cross channel|performance marketing reporting|journey analysis)/', [
            'Use '.$topic.' to translate audience behavior and campaign activity into practical next moves.',
            'Look for patterns across channels, content, forms, ecommerce, and user paths so reporting points to action.',
            'Package findings in a way leadership and working teams can both use.',
        ]],
        ['/(technical seo|seo|keyword|search intent|on page|local seo|enterprise seo|site architecture|internal linking|schema|structured data|search console|content audit|competitive|link acquisition)/', [
            'Improve '.$topic.' by aligning site structure, technical signals, content quality, and search intent.',
            'Make pages easier for people, Google, and discovery systems to understand, trust, and connect to the right query.',
            'Prioritize fixes and content opportunities that can increase qualified visibility instead of chasing generic rankings.',
        ]],
        ['/(aeo|answer engine|ai search|ai citation|knowledge graph|faq optimization|entity|structured content|voice search|featured snippet|maps visibility|local discovery|chatgpt|gemini|perplexity|claude|bing|google)/', [
            'Prepare '.$topic.' for modern discovery across search engines, answer engines, maps, voice, and AI-assisted research.',
            'Clarify entities, answers, schema, page structure, and citation-friendly content so Field Theory can improve visibility beyond classic SEO.',
            'Track the opportunities where your organization should be easier to find, explain, and recommend.',
        ]],
        ['/(google ads|display|youtube|meta|facebook|instagram|linkedin|retargeting|paid media|performance marketing)/', [
            'Plan '.$topic.' around audience intent, landing-page fit, creative message, budget discipline, and conversion tracking.',
            'Connect paid media execution to campaign measurement so spend is easier to evaluate and optimize.',
            'Use reporting and testing to refine targeting, creative, and offers over time.',
        ]],
        ['/(marketing strategy|growth planning|campaign|content strategy|audience|persona|customer journey|lead generation|demand generation|marketing automation|retention|attribution|funnel optimization|customer experience)/', [
            'Shape '.$topic.' around the customer journey, the offer, the channels, and the moments where better marketing can create movement.',
            'Connect planning, content, automation, measurement, and follow-up so growth work is not scattered across disconnected tasks.',
            'Turn the strategy into a practical roadmap your team can execute, measure, and improve.',
        ]],
        ['/(ai strategy|ai agent|workflow automation|business process automation|custom ai|internal ai|customer service agent|lead qualification|internal knowledge|prompt system)/', [
            'Define '.$topic.' around a real workflow, clear guardrails, useful source material, and the people who need to trust the output.',
            'Prototype AI and automation in a way your team can test before committing to a larger build.',
            'Plan handoff, governance, and improvement cycles so the tool stays helpful instead of becoming a novelty.',
        ]],
        ['/(interactive|quiz|assessment|calculator|configurator|map|immersive|storytelling|animated|product finder|roi calculator|data visualization|conversational interface|self service|prototype|custom application|digital product|innovation consulting)/', [
            'Use '.$topic.' to turn a complex decision, story, product, or dataset into something users can explore and act on.',
            'Combine UX, content, animation, development, and measurement so the experience feels useful instead of decorative.',
            'Build a version that can be tested, improved, and connected to the larger website or marketing system.',
        ]],
        ['/(crm automation|reporting automation|marketing automation|business process automation|workflow optimization)/', [
            'Review '.$topic.' across the handoffs, repeated tasks, data gaps, and manual steps that slow the team down.',
            'Design automation around accuracy, visibility, and human review so the system supports people instead of hiding work.',
            'Connect the workflow to reporting and documentation so improvements can be maintained.',
        ]],
    ];

    foreach($rules as $rule){
        if(preg_match($rule[0], $key)) return $rule[1];
    }

    if(strpos($service_key,'ecommerce') !== false) return [
        'Frame '.$topic.' around the moments that affect buyer confidence, cart behavior, checkout completion, and repeat purchase.',
        'Use ecommerce analytics, UX review, and merchandising context to identify the highest-value improvements.',
        'Turn the work into a practical optimization path tied to revenue, conversion rate, and customer experience.',
    ];
    if(strpos($service_key,'data') !== false || strpos($service_key,'analysis') !== false) return [
        'Use '.$topic.' to make the right data easier to collect, understand, and use in day-to-day decisions.',
        'Connect the data source, dashboard, reporting rhythm, and business question before visualizing anything.',
        'Create outputs that help teams see what is happening and what should happen next.',
    ];
    if(strpos($service_key,'search') !== false || strpos($service_key,'seo') !== false) return [
        'Use '.$topic.' to improve how people, search engines, and AI answer tools understand what your organization provides.',
        'Connect technical structure, content clarity, schema, and discovery behavior into a focused search improvement plan.',
        'Prioritize the work most likely to increase qualified visibility and trust.',
    ];
    if(strpos($service_key,'marketing') !== false || strpos($service_key,'growth') !== false) return [
        'Shape '.$topic.' around the audience, offer, channel, message, and measurable next action.',
        'Connect campaign planning with content, automation, landing pages, analytics, and follow-up.',
        'Use the results to refine growth activity instead of adding more disconnected marketing tasks.',
    ];
    if(strpos($service_key,'technology') !== false || strpos($service_key,'innovation') !== false || strpos($service_key,'ai') !== false) return [
        'Turn '.$topic.' into a practical tool, workflow, or experience that solves a specific user or team problem.',
        'Prototype the experience with enough structure to test usefulness, data quality, and operational fit.',
        'Plan the next iteration around adoption, governance, measurement, and maintainability.',
    ];

    return [
        'Shape '.$topic.' inside '.$context.' so the work connects to the user experience, the technical system, and the business outcome.',
        'Identify the content, platform, data, workflow, and approval needs that make this service successful.',
        'Create a practical implementation path with priorities, measurement, and support after the first release.',
    ];
}

function ftc_render_child_service_response($match, $settings=[]){
    $service_id = absint($match['service_id'] ?? 0);
    $task = trim((string)($match['task'] ?? 'Service'));
    $group = trim((string)($match['group'] ?? ''));
    $service_title = $match['service_title'] ?? ($service_id ? get_the_title($service_id) : 'Field Theory service');
    $siblings = array_values(array_filter((array)($match['tasks'] ?? [])));
    $related = array_values(array_slice(array_filter($siblings, function($item) use ($task){
        return ftc_normalize_prompt_text($item) !== ftc_normalize_prompt_text($task);
    }), 0, 4));
    $heading = 'Here is how we approach ' . $task . '.';
    $description = 'Part of ' . $service_title;

    echo '<div class="ftc-response-shell ftc-response-layout-child-service" data-response-title="'.esc_attr($heading).'" data-ftc-response-prompt="'.esc_attr($task).'">';
    echo '<header class="ftc-response-header"><div class="ftc-kicker">'.esc_html($description).'</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($heading).'">'.esc_html($heading).'</h2></header>';
    echo '<section class="ftc-response-content">';
    echo '<div class="ftc-child-service-response">';
    echo '<div class="ftc-child-service-answer"><span>'.esc_html($description).'</span><h3>What we provide:</h3>';
    echo '<ul class="ftc-child-service-bullets">';
    foreach(ftc_child_service_points($task, $service_title, $group) as $point) echo '<li>'.esc_html($point).'</li>';
    echo '</ul>';
    echo '</div>';
    if($siblings){
        echo '<aside class="ftc-child-service-related"><h4>Related services</h4><div>';
        foreach($siblings as $sibling){
            echo '<button type="button" data-prompt="'.esc_attr($sibling).'"'.(ftc_normalize_prompt_text($sibling) === ftc_normalize_prompt_text($task) ? ' class="is-current"' : '').'>'.esc_html($sibling).'</button>';
        }
        echo '</div></aside>';
    }
    echo '</div>';
    echo '<div class="ftc-response-actions ftc-child-service-actions"><div class="ftc-response-actions-left"><button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button>';
    if($service_id) echo '<button type="button" class="ftc-blue-outline-btn ftc-open-category-action" data-ftc-service="'.esc_attr($service_id).'" data-ftc-service-label="'.esc_attr($service_title).'">View Full Service</button>';
    echo '</div><button type="button" class="ftc-green-btn ftc-request-proposal-action" data-prompt="Request a Proposal">Request a Proposal</button></div>';
    echo '</section>';
    echo '</div>';
}

function ftc_render_service_detail($response){
    $slug = $response['service_slug'] ?? ''; $post=null;
    if($slug){ $posts=get_posts(['post_type'=>'ftc_service','name'=>$slug,'posts_per_page'=>1]); if($posts) $post=$posts[0]; }
    if(!$post){ $posts=get_posts(['post_type'=>'ftc_service','posts_per_page'=>1]); if($posts) $post=$posts[0]; }
    if(!$post){ ftc_render_services_panel(); return; }
    ftc_render_service_detail_by_id($post->ID);
}
function ftc_render_service_detail_by_id($id, $focus_label=''){
    $template_id = get_post_meta($id,'_ftc_elementor_template_id',true);
    if($template_id){ echo '<div class="ftc-elementor-template ftc-service-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div>'; return; }
    $image=get_post_meta($id,'_ftc_service_image',true); if(!$image) $image=ftc_service_default_image(get_the_title($id));
    echo '<div class="ftc-service-detail"><div class="ftc-service-detail-hero"><div class="ftc-service-detail-copy"><div class="ftc-service-detail-body">'.wp_kses_post(apply_filters('the_content',get_post_field('post_content',$id))).'</div></div>';
    echo '<div class="ftc-service-detail-image"><img src="'.esc_url($image).'" alt="'.esc_attr(get_the_title($id)).'"></div>';
    echo '</div><div class="ftc-child-grid">';
    $focus_key = ftc_normalize_prompt_text($focus_label);
    foreach(ftc_service_task_groups($id) as $label=>$items){
        $is_group_focus = $focus_key && ftc_normalize_prompt_text($label) === $focus_key;
        echo '<article'.($is_group_focus ? ' class="is-focused-child-service"' : '').'><h4>'.esc_html($label).'</h4><p>Practical capabilities we can plan, build, manage, measure, and improve.</p><ul>';
        foreach($items as $t){
            $is_focus = $focus_key && (ftc_normalize_prompt_text($t) === $focus_key || strpos(ftc_normalize_prompt_text($t), $focus_key) !== false);
            echo '<li'.($is_focus ? ' class="is-focused-child-service"' : '').'><button type="button" class="ftc-child-service-link" data-prompt="'.esc_attr($t).'"><span>'.esc_html($t).'</span></button></li>';
        }
        echo '</ul></article>';
    }
    echo '</div><div class="ftc-response-actions ftc-service-detail-actions"><div class="ftc-response-actions-left"><button class="ftc-back-button" type="button" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div></div>';
}

function ftc_get_portfolio_sequence_items($count=9){
    $items=[];
    $q=new WP_Query([
        'post_type'=>'ftc_portfolio',
        'post_status'=>'publish',
        'posts_per_page'=>$count,
        'orderby'=>['menu_order'=>'ASC','title'=>'ASC'],
        'order'=>'ASC',
    ]);
    if($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $items[]=['type'=>'post','id'=>get_the_ID()];
        }
        wp_reset_postdata();
    }
    if(!$items){
        foreach(array_slice(ftc_get_demo_portfolio(),0,$count) as $demo){
            $items[]=['type'=>'demo','demo'=>$demo];
        }
    }
    return $items;
}

function ftc_render_portfolio_sequence_card($item,$featured=false){
    if(($item['type'] ?? '') === 'post') ftc_portfolio_card($item['id'], $featured);
    else ftc_demo_portfolio_card($item['demo'], $featured);
}

function ftc_render_portfolio_section_one(){
    $items=ftc_get_portfolio_sequence_items(14);
    if(!$items){ return; }
    $featured = array_slice($items, 0, 6);
    echo '<div class="ftc-portfolio-panel ftc-portfolio-full ftc-portfolio-featured-panel">';
    echo '<section class="ftc-portfolio-section-one ftc-portfolio-featured-grid" data-ftc-section-one aria-label="Featured portfolio projects">';
    echo '<div class="ftc-portfolio-featured-grid-list">';
    foreach($featured as $item) ftc_render_portfolio_sequence_card($item, false);
    echo '</div>';
    echo '<div class="ftc-portfolio-view-all"><button type="button" class="ftc-blue-outline-btn" data-prompt="Show Me All Portfolios">View All Projects</button></div>';
    ftc_render_scroll_more_button('Scroll for more');
    echo '</section></div>';
}

function ftc_render_portfolio_all_content(){
    $items=ftc_get_portfolio_sequence_items(24);
    if(!$items){ return; }
    echo '<div class="ftc-portfolio-panel ftc-portfolio-full">';
    echo '<section class="ftc-portfolio-all-content" aria-label="All portfolio projects"><div class="ftc-portfolio-grid">';
    foreach($items as $item) ftc_render_portfolio_sequence_card($item, false);
    echo '</div></section></div>';
}

function ftc_render_portfolio_masonry($limit=-1){
    $count = ($limit > 0) ? $limit : 9;
    $is_home_latest = ($limit > 0);
    $q=new WP_Query([
        'post_type'=>'ftc_portfolio',
        'post_status'=>'publish',
        'posts_per_page'=>$count,
        'orderby'=>['menu_order'=>'ASC','title'=>'ASC'],
        'order'=>'ASC',
    ]);
    echo '<div class="ftc-portfolio-panel'.($is_home_latest ? ' ftc-portfolio-latest' : ' ftc-portfolio-full').'">';
    if(!$is_home_latest){
        $ids = [];
        if($q->have_posts()){
            while($q->have_posts()){
                $q->the_post();
                $ids[] = get_the_ID();
            }
            wp_reset_postdata();
        }
        if($ids){
            echo '<section class="ftc-portfolio-section-one" data-ftc-section-one>';
            ftc_portfolio_card($ids[0], true);
            echo '</section>';
            if(count($ids) > 1){
                echo '<section class="ftc-portfolio-all-content" aria-label="All portfolio projects"><div class="ftc-portfolio-grid">';
                foreach(array_slice($ids, 1) as $id) ftc_portfolio_card($id, false);
                echo '</div></section>';
            }
            echo '</div>';
            return;
        }
        $demos = array_slice(ftc_get_demo_portfolio(),0,$count);
        if($demos){
            echo '<section class="ftc-portfolio-section-one" data-ftc-section-one>';
            ftc_demo_portfolio_card($demos[0], true);
            echo '</section>';
            if(count($demos) > 1){
                echo '<section class="ftc-portfolio-all-content" aria-label="All portfolio projects"><div class="ftc-portfolio-grid">';
                foreach(array_slice($demos, 1) as $demo) ftc_demo_portfolio_card($demo, false);
                echo '</div></section>';
            }
            echo '</div>';
            return;
        }
    }
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
function ftc_demo_portfolio_card($demo, $featured=false){
    $class = $featured ? ' ftc-work-featured' : '';
    echo '<article class="ftc-work-card'.$class.'"><button type="button" data-prompt="Show me your work"><img src="'.esc_url($demo['image']).'" alt="'.esc_attr($demo['title']).'"><div class="ftc-work-info"><h3>'.esc_html($demo['title']).'</h3><p>'.esc_html($demo['description']).'</p><strong>View Project &rarr;</strong></div></button></article>';
}
function ftc_is_placeholder_image_url($url){
    $url = strtolower((string)$url);
    if($url === '') return false;
    return strpos($url,'placeholder-gray') !== false || strpos($url,'placeholder-portfolio') !== false || strpos($url,'placehold.co') !== false;
}
function ftc_portfolio_gallery_image_urls($post_id, $size='large', $allow_placeholder=false){
    $imgs = [];
    foreach(array_filter(array_map('absint',explode(',',(string)get_post_meta($post_id,'_ftc_gallery_ids',true)))) as $attachment_id){
        $url = wp_get_attachment_image_url($attachment_id,$size);
        if($url && ($allow_placeholder || !ftc_is_placeholder_image_url($url))) $imgs[] = $url;
    }
    $gallery = get_post_meta($post_id,'_ftc_gallery_urls',true);
    foreach(array_filter(array_map('trim',explode("\n",$gallery))) as $u){
        if($u && ($allow_placeholder || !ftc_is_placeholder_image_url($u))) $imgs[] = $u;
    }
    return array_values(array_unique($imgs));
}
function ftc_portfolio_card($id, $featured=false){
    $allow_placeholder = get_post_meta($id,'_ftc_allow_placeholder_image',true) === '1';
    $img=has_post_thumbnail($id)?get_the_post_thumbnail_url($id,'large'):'';
    if($img && ftc_is_placeholder_image_url($img) && !$allow_placeholder) $img = '';
    if(!$img){
        $parts=ftc_portfolio_gallery_image_urls($id,'large',$allow_placeholder);
        if($parts) $img=$parts[0];
    }
    $class = ($featured ? ' ftc-work-featured' : '') . (!$img ? ' ftc-work-no-image' : '');
    echo '<article class="ftc-work-card'.$class.'"><button type="button" data-ftc-project="'.esc_attr($id).'">';
    if($img) echo '<img src="'.esc_url($img).'" alt="'.esc_attr(get_the_title($id)).'">';
    echo '<div class="ftc-work-info"><h3>'.esc_html(get_the_title($id)).'</h3><p>'.esc_html(get_the_excerpt($id) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content',$id)),28)).'</p><strong>View Project →</strong></div></button></article>';
}
function ftc_render_faq_preview_panel($limit=10){
    $q = new WP_Query([
        'post_type'=>'ftc_faq',
        'post_status'=>'publish',
        'posts_per_page'=>absint($limit),
        'orderby'=>'menu_order title',
        'order'=>'ASC',
    ]);
    $items = [];
    while($q->have_posts()){
        $q->the_post();
        $terms = get_the_terms(get_the_ID(),'ftc_faq_topic');
        $topic = (!is_wp_error($terms) && $terms) ? $terms[0]->name : 'FAQ';
        $items[] = ['topic'=>$topic, 'question'=>get_the_title()];
    }
    wp_reset_postdata();

    if(!$items){
        $items = [
            ['topic'=>'Start', 'question'=>'Get Started'],
            ['topic'=>'Services', 'question'=>'Our Services'],
            ['topic'=>'Work', 'question'=>'Show me your work!'],
            ['topic'=>'Budget', 'question'=>'What budget should I plan for?'],
            ['topic'=>'Timeline', 'question'=>'How long does a website take?'],
            ['topic'=>'SEO', 'question'=>'SEO / AEO'],
            ['topic'=>'AI', 'question'=>'AI & Automation'],
            ['topic'=>'Contact', 'question'=>'Request a Proposal'],
        ];
    }

    echo '<div class="ftc-faq-preview-grid">';
    foreach($items as $item){
        echo '<button type="button" class="ftc-faq-prompt" data-prompt="'.esc_attr($item['question']).'"><span>'.esc_html($item['topic']).'</span><strong>'.esc_html($item['question']).'</strong></button>';
    }
    echo '</div>';
}
function ftc_render_faq_panel(){
    echo '<div class="ftc-help-cloud ftc-response-prompt-cloud ftc-faq-prompt-cloud">';
    foreach(ftc_help_prompt_items() as $prompt){
        echo '<button type="button" data-prompt="'.esc_attr($prompt).'">'.esc_html($prompt).'</button>';
    }
    echo '</div>';
}
function ftc_render_testimonials_panel(){ echo '<div class="ftc-testimonial-grid"><figure><blockquote>“A clearer website, cleaner message, and a team that understood the business problem before touching the design.”</blockquote><figcaption>Healthcare client</figcaption></figure><figure><blockquote>“Field Theory brought strategy, design, development, and analytics together without making the process feel heavy.”</blockquote><figcaption>Nonprofit partner</figcaption></figure><figure><blockquote>“The reporting finally made sense. We could see what was working and what to do next.”</blockquote><figcaption>Marketing director</figcaption></figure></div>'; }
function ftc_contact_card_buttons($items, $selected=''){
    foreach($items as $item){
        $is_selected = $selected && $item === $selected;
        echo '<button type="button" class="ftc-quiz-card'.($is_selected ? ' is-selected' : '').'" data-ftc-choice data-value="'.esc_attr($item).'" aria-pressed="'.($is_selected ? 'true' : 'false').'"><span>'.esc_html($item).'</span></button>';
    }
}

function ftc_render_contact_panel($settings){
    $email = $settings['contact_email'] ?: 'jamie@fieldtheory.ai';
    $schedule_url = $settings['calendly_url'] ?: ($settings['contact_url'] ?: 'mailto:'.$email);

    echo '<div class="ftc-contact-onboarding ftc-two-column-response">';
    echo '<div class="ftc-contact-intro"><h3>Work With Us</h3><p>Share the shape of the project, what is working, what feels stuck, and what a useful next step would look like.</p><p class="ftc-contact-email">Email: <a href="mailto:'.esc_attr($email).'">'.esc_html($email).'</a></p></div>';
    echo '<div class="ftc-contact-quiz" data-ftc-contact-quiz data-schedule-url="'.esc_url($schedule_url).'">';
    echo '<div class="ftc-quiz-progress"><span data-ftc-quiz-progress-text>Step 1 of 8</span><div><i data-ftc-quiz-progress-bar></i></div></div>';
    echo '<div class="ftc-quiz-error" data-ftc-quiz-error role="alert" aria-live="polite"></div>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="services" data-multi><h4>What should the proposal focus on?</h4><p>Select all that apply.</p><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Website Development & Core Tech','Digital Marketing & Growth Strategy','Search & Discovery Optimization','Ecommerce & Conversion','Data, Analysis & Visualization','Technology, Innovation and A.I.','Not Sure Yet']);
    echo '</div><button type="button" class="ftc-quiz-next" data-ftc-next>Continue</button></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step><h4>Tell us about your organization.</h4><div class="ftc-quiz-fields"><label>Company Name<input type="text" data-ftc-input="company" required autocomplete="organization"></label><label>Website URL<input type="url" data-ftc-input="website" placeholder="https://"></label></div><button type="button" class="ftc-quiz-next" data-ftc-next>Continue</button></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="orgType"><h4>What best describes your organization?</h4><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Startup','Small Business','Mid-Size Company','Enterprise','Government','Nonprofit','Education','Healthcare','Other']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="challenge"><h4>What is your biggest challenge right now?</h4><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Need a Better Website','Need More Leads','Need More Sales','Need Better Analytics','Need Marketing Help','Need AI Implementation','Need Automation','Need Technical Support','Need a Strategic Partner','Other']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="timeline"><h4>How soon are you looking to get started?</h4><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Just Exploring','Within 6 Months','Within 90 Days','Within 30 Days','Immediately']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="budget"><h4>What budget range best fits your project?</h4><p>To help us recommend the right solution...</p><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Under $5,000','$5,000-$15,000','$15,000-$50,000','$50,000+','Not Sure Yet']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step><h4>Tell us a little more.</h4><label class="ftc-quiz-textarea">What is on your mind?<textarea data-ftc-input="notes" rows="5" placeholder="Share anything useful about the project, timing, goals, or what feels stuck."></textarea></label><button type="button" class="ftc-quiz-next" data-ftc-next>Continue</button></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="contactMethod"><h4>Great. How can we reach you?</h4><div class="ftc-quiz-fields"><label>Name<input type="text" data-ftc-input="name" required autocomplete="name"></label><label>Email<input type="email" data-ftc-input="email" required autocomplete="email"></label><label>Phone<input type="tel" data-ftc-input="phone" autocomplete="tel"></label></div><p>Preferred contact method</p><div class="ftc-quiz-card-grid ftc-quiz-card-grid-small">';
    ftc_contact_card_buttons(['Email','Phone','Text Message'], 'Email');
    echo '</div><button type="button" class="ftc-quiz-next" data-ftc-next>Review</button></section>';

    echo '<section class="ftc-quiz-step ftc-quiz-complete" data-ftc-quiz-step><h4>Review your proposal request.</h4><p>Here is what Field Theory will receive when you submit.</p><div class="ftc-quiz-summary" data-ftc-submission-summary></div><div class="ftc-quiz-actions"><a class="ftc-quiz-schedule" href="'.esc_url($schedule_url).'" target="_blank" rel="noopener">Schedule a Call</a><button type="button" class="ftc-quiz-submit" data-ftc-submit-inquiry>Submit Proposal Request</button></div><div class="ftc-quiz-submit-status" data-ftc-submit-status aria-live="polite"></div></section>';
    echo '</div></div>';
}
function ftc_render_followups($followups){
    if(!$followups) return;
    echo '<footer class="ftc-followups"><div class="ftc-followup-row">';
    foreach($followups as $f) echo '<button type="button" class="ftc-followup" data-prompt="'.esc_attr($f).'">'.esc_html($f).'</button>';
    echo '</div></footer>';
}
function ftc_ajax_service_detail(){
    check_ajax_referer('ftc_nonce','nonce');
    $id = absint($_POST['service_id'] ?? 0);
    $post = $id ? get_post($id) : null;
    if(!$post || $post->post_type !== 'ftc_service' || $post->post_status !== 'publish') {
        wp_send_json_error(['message'=>'Service not found.']);
    }

    ob_start();
    ftc_render_service_detail_response_markup($id);
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_service_detail','ftc_ajax_service_detail'); add_action('wp_ajax_nopriv_ftc_service_detail','ftc_ajax_service_detail');
function ftc_ajax_post_detail(){ wp_send_json_error(); }
add_action('wp_ajax_ftc_post_detail','ftc_ajax_post_detail'); add_action('wp_ajax_nopriv_ftc_post_detail','ftc_ajax_post_detail');
function ftc_render_project_detail_markup($post_id){
    $post = get_post($post_id);
    if(!$post || $post->post_type !== 'ftc_portfolio') return;
    $imgs=[];
    $allow_placeholder = get_post_meta($post_id,'_ftc_allow_placeholder_image',true) === '1';
    if(has_post_thumbnail($post_id)){
        $thumb = get_the_post_thumbnail_url($post_id,'large');
        if($thumb && ($allow_placeholder || !ftc_is_placeholder_image_url($thumb))) $imgs[]=$thumb;
    }
    foreach(ftc_portfolio_gallery_image_urls($post_id,'large',$allow_placeholder) as $u) $imgs[]=$u;
    $imgs = array_values(array_unique($imgs));
    $feature = array_shift($imgs);
    $industry = get_post_meta($post_id,'_ftc_industry',true);
    echo '<div class="ftc-response-shell ftc-response-layout-project">';
    echo '<header class="ftc-response-header"><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr(get_the_title($post_id)).'">'.esc_html(get_the_title($post_id)).'</h2><div class="ftc-answer-description">'.esc_html(get_the_excerpt($post_id) ?: 'Project details, creative direction, and supporting images.').'</div></header>';
    echo '<section class="ftc-response-content ftc-project-page">';
    echo '<div class="ftc-project-detail'.(!$feature ? ' ftc-project-detail-no-image' : '').'">';
    echo '<div class="ftc-project-copy">';
    if($industry) echo '<p class="ftc-project-industry">'.esc_html($industry).'</p>';
    echo '<h3>'.esc_html(get_the_title($post_id)).'</h3>';
    echo '<div class="ftc-project-text">'.wp_kses_post(apply_filters('the_content',$post->post_content)).'</div>';
    echo '</div>';
    if($feature) echo '<div class="ftc-project-feature"><img src="'.esc_url($feature).'" alt="'.esc_attr(get_the_title($post_id)).'"></div>';
    echo '</div>';
    if($imgs){
        echo '<div class="ftc-project-gallery">';
        foreach($imgs as $u) echo '<figure><img src="'.esc_url($u).'" alt="'.esc_attr(get_the_title($post_id)).'"></figure>';
        echo '</div>';
    }
    echo '<div class="ftc-response-actions ftc-project-actions"><div class="ftc-response-actions-left"><button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Show me your work!" data-prompt="Show me your work!">Back to Portfolio</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div>';
    echo '</section>';
    echo '</div>';
}




function ftc_ajax_portfolio_detail(){
    check_ajax_referer('ftc_nonce','nonce');
    $post_id = absint($_POST['post_id'] ?? 0);
    $post = get_post($post_id);
    if(!$post || $post->post_type !== 'ftc_portfolio' || $post->post_status !== 'publish'){
        wp_send_json_error(['message'=>'Project not found.']);
    }

    $template_id = get_post_meta($post_id,'_ftc_elementor_template_id',true);
    ob_start();
    if($template_id){
        echo '<div class="ftc-response-shell ftc-response-layout-project"><section class="ftc-response-content"><div class="ftc-elementor-template ftc-project-elementor-template">'.ftc_render_elementor_template_by_id($template_id).'</div></section>';
        echo '<div class="ftc-response-actions ftc-project-actions"><div class="ftc-response-actions-left"><button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Show me your work!" data-prompt="Show me your work!">Back to Portfolio</button><button type="button" class="ftc-blue-outline-btn" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div>';
        echo '</div>';
        wp_send_json_success(['html'=>ob_get_clean()]);
    }

    ftc_render_project_detail_markup($post_id);
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
add_action('wp_ajax_nopriv_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
