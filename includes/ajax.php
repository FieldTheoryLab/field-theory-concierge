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
    if(function_exists('ftc_route_compact_key_loose')){
        $needle_loose = ftc_route_compact_key_loose($needle);
        $key_loose = ftc_route_compact_key_loose($key);
        if($needle_loose !== '' && $needle_loose === $key_loose) return 96;
    }
    if(strlen($needle) >= 4 && strpos($key, $needle) !== false) return 82;
    $short_allowed = ['ai','api','ux','ui','seo','aeo','cro','ga4'];
    if(in_array($needle, $short_allowed, true) && preg_match('/(^|\s)'.preg_quote($needle,'/').'s?(\s|$)/', $key)) return 78;
    if(strlen($key) >= 8 && strpos($needle, $key) !== false) return 60;
    return 0;
}

function ftc_find_service_child_match($term){
    $term = ftc_resolve_service_task_prompt($term);
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
    $has_elementor_canvas = !empty($response['id']) && get_post_meta(absint($response['id']),'_elementor_edit_mode',true) === 'builder';
    $prompt_label = $response['prompt_label'] ?? $title;

    if(!$has_elementor_canvas && $type === 'legacy' && in_array($layout, ['home','about','portfolio','services','contact'], true)){
        ftc_render_response_shell([
            'title'=>$title,
            'description'=>$desc,
            'html'=>$response['html'] ?? '',
            'layout'=>$layout,
            'prompt'=>$prompt_label,
            'followups'=>$response['followups'] ?? [],
        ], $settings);
        return;
    }

    echo '<div class="ftc-response-shell ftc-response-layout-'.esc_attr($layout).' ftc-response-source-cpt ftc-response-type-'.esc_attr($type).'" data-response-title="'.esc_attr($title).'" data-ftc-response-prompt="'.esc_attr($prompt_label).'">';
    echo '<header class="ftc-response-header"><div class="ftc-response-title-label">'.esc_html($title).'</div>';
    $typed = $desc ?: $title;
    echo '<h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($typed).'">'.esc_html($typed).'</h2>';
    if($prompt_label) echo '<span class="ftc-question-chip ftc-question-chip-pop" aria-hidden="true">'.esc_html($prompt_label).'</span>';
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

    if(!$rendered && ($type === 'elementor_canvas' || $has_elementor_canvas) && !empty($response['id']) && function_exists('ftc_render_elementor_template_by_id')){
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
    if(ftc_is_go_time_prompt($term)) return null;
    $q = ftc_normalize_prompt_text($term);
    if($q === '') return null;
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
        'get started'=>'get_started','start'=>'get_started','home'=>'get_started','overview'=>'get_started'
    ];
    foreach($map as $needle=>$key){
        if($q === $needle || ($needle !== 'start' && strpos($q, $needle) !== false)){
            if(isset($responses[$key])) return $responses[$key];
        }
    }
    if($q === 'start' && isset($responses['get_started'])) return $responses['get_started'];
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

function ftc_response_library_entries(){
    return [
        [
            'id'=>'cluster-a-abq-nm-authority',
            'title'=>'Albuquerque and New Mexico coverage',
            'category'=>'local-authority',
            'cluster'=>'A',
            'question'=>'Are you based in Albuquerque and do you serve all of New Mexico?',
            'prompt_variants'=>['Are you based in Albuquerque','Do you work statewide in New Mexico','Do you work in Santa Fe','Do you work in Las Cruces'],
            'answer'=>'Yes. Field Theory Lab is based in Albuquerque and supports organizations across New Mexico, including Santa Fe, Las Cruces, Rio Rancho, Farmington, Roswell, Clovis, Los Alamos, and nearby communities.',
            'short_answer'=>'Albuquerque based, statewide service across New Mexico.',
            'keywords'=>['albuquerque web agency','new mexico digital agency','santa fe website help','las cruces marketing support','nm statewide service'],
            'intent'=>'location_authority',
            'services'=>['Website Development & Core Tech','Digital Marketing & Growth Strategy','Search & Discovery Optimization'],
            'industries'=>['Public Sector','Healthcare','Education','Nonprofit','Utilities'],
            'locations'=>['Albuquerque','Santa Fe','Las Cruces','Rio Rancho','Farmington','Roswell','Los Alamos','Statewide New Mexico'],
            'technologies'=>['WordPress','Drupal','GA4','Google Search Console'],
            'follow_up_prompts'=>['Where in New Mexico do you work?','Industries We Support','Request a Proposal'],
            'suggested_next_actions'=>['Share your city and goals','Request a proposal scope call'],
            'related_entries'=>['cluster-j-location-service-clusters','cluster-i-industries'],
            'schema_type'=>'faq_response',
            'seo_title'=>'Albuquerque web and marketing team for New Mexico',
            'seo_description'=>'Field Theory Lab is Albuquerque based and supports website, SEO, analytics, AI, and marketing projects across New Mexico.'
        ],
        [
            'id'=>'cluster-b-website-core-tech',
            'title'=>'Website development and core technology',
            'category'=>'services',
            'cluster'=>'B',
            'question'=>'I need a website',
            'prompt_variants'=>['I need a website','Can you build a website','Can you improve our current site','Website Development & Core Tech'],
            'answer'=>'We can help whether you need a new build or a practical upgrade. Our website work covers strategy, UX, WordPress, Drupal, performance, accessibility, content structure, and integrations so your site supports real business goals.',
            'short_answer'=>'New websites and practical website upgrades built for performance and growth.',
            'keywords'=>['website development','wordpress development','drupal development','core web vitals','website redesign'],
            'intent'=>'website_service',
            'services'=>['Website Development & Core Tech'],
            'industries'=>['Healthcare','Education','Government','B2B','Nonprofit'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['WordPress','Drupal','PHP','React','Node.js'],
            'follow_up_prompts'=>['How long does a website take?','What budget should I plan for?','Request a Proposal'],
            'suggested_next_actions'=>['Send your current site URL','Share top conversion goals'],
            'related_entries'=>['cluster-k-pricing-process-proposal'],
            'schema_type'=>'service_response',
            'seo_title'=>'Website development in Albuquerque and New Mexico',
            'seo_description'=>'Field Theory Lab plans, builds, and improves websites in Albuquerque and across New Mexico.'
        ],
        [
            'id'=>'cluster-c-ecommerce-cro',
            'title'=>'Ecommerce and conversion optimization',
            'category'=>'services',
            'cluster'=>'C',
            'question'=>'How can you improve ecommerce conversion?',
            'prompt_variants'=>['Can you help with ecommerce','Ecommerce & Conversion','My ecommerce conversion is low'],
            'answer'=>'We improve product pages, navigation, cart flow, checkout, and tracking so more visitors become buyers. We focus on practical changes, measurement, and test plans that improve revenue without guessing.',
            'short_answer'=>'We improve ecommerce journeys and checkout conversion with analytics-backed fixes.',
            'keywords'=>['ecommerce cro','shopify optimization','woocommerce conversion','checkout friction','average order value'],
            'intent'=>'ecommerce_cro',
            'services'=>['Ecommerce & Conversion Rate Optimization (CRO)'],
            'industries'=>['Retail','Consumer Brand','B2B Ecommerce'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['Shopify','WooCommerce','GA4','Microsoft Clarity'],
            'follow_up_prompts'=>['How do you measure marketing?','What budget should I plan for?','Request a Proposal'],
            'suggested_next_actions'=>['Share platform and conversion rate baseline','Identify your top drop-off page'],
            'related_entries'=>['cluster-e-data-analytics-viz','cluster-k-pricing-process-proposal'],
            'schema_type'=>'service_response',
            'seo_title'=>'Ecommerce CRO services in New Mexico',
            'seo_description'=>'Field Theory Lab helps ecommerce teams in Albuquerque and New Mexico improve conversion rates and checkout performance.'
        ],
        [
            'id'=>'cluster-d-seo-aeo-geo',
            'title'=>'SEO, AEO, GEO, and local search',
            'category'=>'search',
            'cluster'=>'D',
            'question'=>'Can you help with AI search and local search?',
            'prompt_variants'=>['Can you help with AI search','SEO / AEO','Search & Discovery Optimization','Can you help with local SEO'],
            'answer'=>'Yes. We combine technical SEO, content architecture, schema, local search optimization, and answer-engine strategy so people can find you in Google, map results, and AI-assisted search tools.',
            'short_answer'=>'We handle SEO, AEO, GEO, and local NM search visibility.',
            'keywords'=>['seo','aeo','geo','ai search','local search','google business profile','search console'],
            'intent'=>'search_visibility',
            'services'=>['Search & Discovery Optimization'],
            'industries'=>['Professional Services','Healthcare','Education','Government'],
            'locations'=>['Albuquerque','Santa Fe','Las Cruces','New Mexico'],
            'technologies'=>['Google Search Console','Schema.org','GA4','Google Business Profile'],
            'follow_up_prompts'=>['Do you work in Santa Fe','How do you measure marketing?','Request a Proposal'],
            'suggested_next_actions'=>['Share priority services and target cities','Run technical and content baseline audit'],
            'related_entries'=>['cluster-a-abq-nm-authority','cluster-e-data-analytics-viz'],
            'schema_type'=>'service_response',
            'seo_title'=>'SEO and AI search support in New Mexico',
            'seo_description'=>'Albuquerque based SEO, AEO, GEO, and local search support for organizations across New Mexico.'
        ],
        [
            'id'=>'cluster-e-data-analytics-viz',
            'title'=>'Data, analytics, and visualization',
            'category'=>'analytics',
            'cluster'=>'E',
            'question'=>'Help me understand my website and marketing data',
            'prompt_variants'=>['Help me understand my website and marketing data','Analytics & Dashboards','How do you measure marketing'],
            'answer'=>'We clean up tracking, define practical KPIs, and build dashboards your team can use for decisions. This usually includes GA4, tag management, funnel analysis, and reporting connected to pipeline or revenue goals.',
            'short_answer'=>'We make tracking and dashboards decision-ready.',
            'keywords'=>['ga4 setup','dashboard reporting','kpi framework','tag manager','looker studio'],
            'intent'=>'analytics_measurement',
            'services'=>['Data, Analysis & Visualization'],
            'industries'=>['Healthcare','Education','Public Sector','Ecommerce'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['GA4','Google Tag Manager','Looker Studio','BigQuery','Tableau','Microsoft Clarity'],
            'follow_up_prompts'=>['My website is not generating leads','Digital Marketing & Growth Strategy','Request a Proposal'],
            'suggested_next_actions'=>['Share current analytics access','List the top business decisions you need weekly'],
            'related_entries'=>['cluster-f-digital-marketing-growth','cluster-k-pricing-process-proposal'],
            'schema_type'=>'service_response',
            'seo_title'=>'Analytics and dashboard support Albuquerque NM',
            'seo_description'=>'Field Theory Lab builds measurement systems and dashboards that connect marketing activity to real outcomes.'
        ],
        [
            'id'=>'cluster-f-digital-marketing-growth',
            'title'=>'Digital marketing and growth strategy',
            'category'=>'marketing',
            'cluster'=>'F',
            'question'=>'My website is not generating leads',
            'prompt_variants'=>['My website is not generating leads','Digital Marketing & Growth Strategy','Can you help with lead generation'],
            'answer'=>'We diagnose where lead flow breaks across messaging, traffic quality, landing pages, forms, trust signals, and follow-up systems. Then we prioritize a focused plan so your website and marketing improvements work together instead of in isolation.',
            'short_answer'=>'We fix lead generation by aligning traffic, messaging, UX, and conversion flow.',
            'keywords'=>['lead generation','digital marketing strategy','campaign optimization','funnel optimization','conversion strategy'],
            'intent'=>'marketing_growth',
            'services'=>['Digital Marketing & Growth Strategy','Search & Discovery Optimization'],
            'industries'=>['B2B','Healthcare','Education','Professional Services'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['Google Ads','Meta Ads','HubSpot','Salesforce','GA4'],
            'follow_up_prompts'=>['How do you measure marketing?','Can you improve our current site?','Request a Proposal'],
            'suggested_next_actions'=>['Share monthly traffic and lead baseline','Identify your highest-value lead action'],
            'related_entries'=>['cluster-e-data-analytics-viz','cluster-l-comparison-decision'],
            'schema_type'=>'service_response',
            'seo_title'=>'Lead generation strategy Albuquerque New Mexico',
            'seo_description'=>'Practical digital marketing and conversion strategy from an Albuquerque based team serving New Mexico.'
        ],
        [
            'id'=>'cluster-g-ai-automation-creative-tech',
            'title'=>'AI, automation, and creative technology',
            'category'=>'ai',
            'cluster'=>'G',
            'question'=>'Can you help with AI and automation?',
            'prompt_variants'=>['Can you help with AI and automation','AI & Automation','Technology, Innovation and A.I.'],
            'answer'=>'Yes. We build practical AI and automation workflows such as internal assistants, content operations support, and process automation tied to clear business outcomes. We focus on systems your team can use and maintain.',
            'short_answer'=>'Practical AI and automation tied to real operations.',
            'keywords'=>['ai automation','workflow automation','internal ai assistant','creative technology','ai implementation'],
            'intent'=>'ai_automation',
            'services'=>['Technology, Innovation and A.I.'],
            'industries'=>['Healthcare','Education','Public Sector','B2B'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['OpenAI','Anthropic','Google Gemini','APIs','RAG'],
            'follow_up_prompts'=>['Can you help with AI search','How do you measure marketing?','Request a Proposal'],
            'suggested_next_actions'=>['Define the exact workflow to improve','Identify data sources and owners'],
            'related_entries'=>['cluster-d-seo-aeo-geo','cluster-k-pricing-process-proposal'],
            'schema_type'=>'service_response',
            'seo_title'=>'AI automation services in Albuquerque NM',
            'seo_description'=>'Field Theory Lab designs practical AI and automation systems for teams across New Mexico.'
        ],
        [
            'id'=>'cluster-h-accessibility-ada',
            'title'=>'Accessibility and ADA support',
            'category'=>'accessibility',
            'cluster'=>'H',
            'question'=>'Can you help with accessibility and ADA?',
            'prompt_variants'=>['Can you help with accessibility','ADA Accessibility','Do you handle WCAG'],
            'answer'=>'Yes. We can audit and remediate accessibility issues in practical phases, then help your team maintain accessible design, content, and development patterns over time.',
            'short_answer'=>'Accessibility audits, remediation, and ongoing ADA support.',
            'keywords'=>['ada compliance','wcag audit','website accessibility remediation','accessible content workflows'],
            'intent'=>'accessibility_support',
            'services'=>['Website Development & Core Tech'],
            'industries'=>['Government','Education','Healthcare','Nonprofit'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['WCAG 2.2','Accessibility testing','WordPress','Drupal'],
            'follow_up_prompts'=>['Can you improve our current site?','What budget should I plan for?','Request a Proposal'],
            'suggested_next_actions'=>['Run accessibility baseline scan','Prioritize high-impact issues by user risk'],
            'related_entries'=>['cluster-b-website-core-tech','cluster-k-pricing-process-proposal'],
            'schema_type'=>'service_response',
            'seo_title'=>'ADA and website accessibility support New Mexico',
            'seo_description'=>'Field Theory Lab helps Albuquerque and New Mexico organizations improve ADA and WCAG website accessibility.'
        ],
        [
            'id'=>'cluster-i-industries',
            'title'=>'Industries we support',
            'category'=>'industries',
            'cluster'=>'I',
            'question'=>'What industries does Field Theory support?',
            'prompt_variants'=>['Industries We Support','Do you work with nonprofits','Do you work with government'],
            'answer'=>'We regularly support healthcare, education, public sector and tribal programs, nonprofits, utilities, ecommerce, and B2B service organizations. If your team needs clearer digital performance, we can usually help.',
            'short_answer'=>'Healthcare, education, public sector, nonprofits, utilities, ecommerce, and B2B.',
            'keywords'=>['industries','healthcare marketing','government website','nonprofit digital strategy','education web'],
            'intent'=>'industry_fit',
            'services'=>['Website Development & Core Tech','Digital Marketing & Growth Strategy','Data, Analysis & Visualization'],
            'industries'=>['Healthcare','Education','Government','Nonprofit','Utilities','Ecommerce','B2B'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['WordPress','Drupal','GA4'],
            'follow_up_prompts'=>['Albuquerque and New Mexico coverage','Show me your work!','Request a Proposal'],
            'suggested_next_actions'=>['Share your organization type and constraints','Review portfolio examples by industry'],
            'related_entries'=>['cluster-a-abq-nm-authority','cluster-j-location-service-clusters'],
            'schema_type'=>'faq_response',
            'seo_title'=>'Industry experience across New Mexico organizations',
            'seo_description'=>'Field Theory Lab supports healthcare, education, government, nonprofits, utilities, ecommerce, and B2B teams.'
        ],
        [
            'id'=>'cluster-j-location-service-clusters',
            'title'=>'Location service clusters',
            'category'=>'locations',
            'cluster'=>'J',
            'question'=>'What New Mexico areas do you serve and for what services?',
            'prompt_variants'=>['Where in New Mexico do you work?','Location Service Clusters','Do you work in Santa Fe','Do you work in Las Cruces'],
            'answer'=>'Albuquerque and Rio Rancho teams often ask for website modernization and analytics cleanup. Santa Fe organizations usually ask for SEO, content, and local visibility strategy. Las Cruces and southern New Mexico teams often focus on ecommerce, lead generation, and practical automation. We support all of these statewide.',
            'short_answer'=>'Service priorities vary by city, and we support statewide.',
            'keywords'=>['new mexico cities','santa fe seo','las cruces ecommerce','albuquerque web development'],
            'intent'=>'location_services',
            'services'=>['Website Development & Core Tech','Search & Discovery Optimization','Ecommerce & Conversion Rate Optimization (CRO)','Technology, Innovation and A.I.'],
            'industries'=>['Public Sector','Healthcare','Education','Retail','B2B'],
            'locations'=>['Albuquerque','Rio Rancho','Santa Fe','Las Cruces','Farmington','Roswell','Statewide New Mexico'],
            'technologies'=>['WordPress','Drupal','GA4','Shopify'],
            'follow_up_prompts'=>['Are you based in Albuquerque','I need a website','Request a Proposal'],
            'suggested_next_actions'=>['Tell us your city and service priority','Request a scoped recommendation'],
            'related_entries'=>['cluster-a-abq-nm-authority','cluster-b-website-core-tech'],
            'schema_type'=>'faq_response',
            'seo_title'=>'New Mexico website and marketing service areas',
            'seo_description'=>'Albuquerque based Field Theory Lab supports Santa Fe, Las Cruces, Rio Rancho, and all New Mexico regions.'
        ],
        [
            'id'=>'cluster-k-pricing-process-proposal',
            'title'=>'Pricing, process, and proposal',
            'category'=>'process',
            'cluster'=>'K',
            'question'=>'What budget should I plan for and what happens next?',
            'prompt_variants'=>['What budget should I plan for?','What happens after I request a proposal?','How long does a website take?'],
            'answer'=>'Most focused projects start in the lower five figures, while larger website, ecommerce, and integration programs require broader scope and timeline. After a proposal request, we review goals, current performance, constraints, and budget range, then recommend a clear next step.',
            'short_answer'=>'Scoped pricing with a clear discovery-to-proposal process.',
            'keywords'=>['pricing','budget','proposal process','timeline','project scope'],
            'intent'=>'pricing_process',
            'services'=>['All Services'],
            'industries'=>['All'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['Discovery workshops','Analytics audits'],
            'follow_up_prompts'=>['Request a Proposal','I need a website','My website is not generating leads'],
            'suggested_next_actions'=>['Submit proposal form with goals and timeline','Schedule discovery call'],
            'related_entries'=>['cluster-b-website-core-tech','cluster-f-digital-marketing-growth'],
            'schema_type'=>'faq_response',
            'seo_title'=>'Website project pricing and proposal process',
            'seo_description'=>'Learn Field Theory Lab pricing ranges, project timeline expectations, and proposal process.'
        ],
        [
            'id'=>'cluster-l-comparison-decision',
            'title'=>'Comparison and decision support',
            'category'=>'decision',
            'cluster'=>'L',
            'question'=>'Should we rebuild, optimize, or phase work over time?',
            'prompt_variants'=>['Should we rebuild or optimize','Can you improve our current site','How can I decide what to do first'],
            'answer'=>'We usually start with a practical diagnostic to compare three paths: focused fixes, phased modernization, or full rebuild. The right choice depends on current platform health, content complexity, integrations, and how fast results are needed.',
            'short_answer'=>'We compare rebuild vs optimization and recommend the best path.',
            'keywords'=>['rebuild vs redesign','website audit','phased roadmap','decision support'],
            'intent'=>'decision_support',
            'services'=>['Website Development & Core Tech','Data, Analysis & Visualization'],
            'industries'=>['Healthcare','Education','Government','B2B'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['Technical audit','Content audit','Analytics baseline'],
            'follow_up_prompts'=>['Can you improve our current site?','Request a Proposal','Show me your work!'],
            'suggested_next_actions'=>['Share CMS and hosting details','Prioritize goals by quarter'],
            'related_entries'=>['cluster-k-pricing-process-proposal','cluster-b-website-core-tech'],
            'schema_type'=>'faq_response',
            'seo_title'=>'Website decision framework rebuild or optimize',
            'seo_description'=>'A practical framework for deciding whether to optimize your current site or rebuild.'
        ],
        [
            'id'=>'cluster-m-natural-mobile-prompts',
            'title'=>'Natural language mobile prompts',
            'category'=>'mobile-prompts',
            'cluster'=>'M',
            'question'=>'Natural prompt coverage',
            'prompt_variants'=>['I need a website','Do you work in Santa Fe','Can you help with AI search','My website is not generating leads'],
            'answer'=>'Yes. Those mobile-style prompts map directly to website, local New Mexico service coverage, AI search optimization, and lead generation support so people can ask naturally and still get focused answers.',
            'short_answer'=>'Natural mobile prompts are fully supported.',
            'keywords'=>['mobile prompt','natural language query','quick prompt'],
            'intent'=>'mobile_prompt_support',
            'services'=>['Website Development & Core Tech','Search & Discovery Optimization','Digital Marketing & Growth Strategy'],
            'industries'=>['All'],
            'locations'=>['Albuquerque','New Mexico'],
            'technologies'=>['Prompt routing'],
            'follow_up_prompts'=>['I need a website','Do you work in Santa Fe','Can you help with AI search','My website is not generating leads'],
            'suggested_next_actions'=>['Try any natural question in plain language'],
            'related_entries'=>['cluster-a-abq-nm-authority','cluster-f-digital-marketing-growth'],
            'schema_type'=>'faq_response',
            'seo_title'=>'Natural language prompts for Field Theory concierge',
            'seo_description'=>'Ask website, SEO, AI search, and local New Mexico service questions in natural language.'
        ],
    ];
}

function ftc_response_library_score($term, $entry){
    $score = 0;
    $term = trim((string)$term);
    if($term === '') return 0;
    $signals = array_merge(
        [$entry['question'] ?? '', $entry['title'] ?? ''],
        (array)($entry['prompt_variants'] ?? []),
        (array)($entry['keywords'] ?? [])
    );
    foreach($signals as $signal){
        $needle = ftc_normalize_prompt_text($signal);
        if($needle === '') continue;
        if($term === $needle) $score += 100;
        elseif(strpos($term, $needle) !== false) $score += 70;
        elseif(strlen($term) >= 5 && strpos($needle, $term) !== false) $score += 30;
    }
    return $score;
}

function ftc_find_response_library_entry($term){
    $normalized = ftc_normalize_prompt_text($term);
    if($normalized === '') return null;
    $best = null;
    $best_score = 0;
    foreach(ftc_response_library_entries() as $entry){
        $score = ftc_response_library_score($normalized, $entry);
        if($score > $best_score){
            $best = $entry;
            $best_score = $score;
        }
    }
    return $best_score >= 70 ? $best : null;
}

function ftc_response_library_to_shell($entry){
    if(!$entry) return null;
    $answer = trim((string)($entry['answer'] ?? ''));
    $short = trim((string)($entry['short_answer'] ?? ''));
    $html = '';
    if($answer !== '') $html .= '<p>'.esc_html($answer).'</p>';
    return [
        'title'=>$entry['question'] ?? ($entry['title'] ?? 'Field Theory Response'),
        'description'=>$short !== '' ? $short : wp_trim_words(wp_strip_all_tags($answer), 20),
        'html'=>$html,
        'layout'=>'none',
        'followups'=>array_values(array_unique(array_filter((array)($entry['follow_up_prompts'] ?? [])))),
    ];
}

function ftc_direct_faq_response($term){
    $t = ftc_normalize_prompt_text($term);
    if($t === '') return null;

    $library_entry = ftc_find_response_library_entry($term);
    if($library_entry){
        return ftc_response_library_to_shell($library_entry);
    }

    $answers = [
        [
            'match'=>'/(how long|timeline|schedule|timeframe).*(website|site|project)|^(how long does a website take)$/',
            'title'=>'How long does a website take?',
            'description'=>'Most Field Theory website work falls into a few practical timeline ranges.',
            'html'=>'<p>Small website improvements or focused landing pages can often move in 2 to 6 weeks. A new marketing website usually lands closer to 8 to 14 weeks. Larger ecommerce, Drupal, content migration, accessibility, analytics, or integration-heavy projects can take 12 to 24 weeks or more.</p><p>The real answer depends on scope, content readiness, approvals, integrations, and how much strategy needs to happen before build. Field Theory starts by clarifying goals, mapping content and systems, and giving you a practical timeline.</p>',
            'followups'=>['Request a Proposal','Website Development & Core Tech','What budget should I plan for?'],
        ],
        [
            'match'=>'/(budget|cost|price|pricing|how much|spend|investment)/',
            'title'=>'What budget should I plan for?',
            'description'=>'A useful budget depends on whether you need a focused fix, a rebuild, or an ongoing growth partner.',
            'html'=>'<p>For focused website, analytics, SEO, or conversion improvements, many projects start in the lower five figures. Full redesigns, ecommerce builds, integrations, accessibility remediation, or larger systems require broader scope and budget. Ongoing support can be scoped as a monthly partnership.</p><p>Field Theory Lab is based in Albuquerque and supports teams statewide across New Mexico.</p>',
            'followups'=>['Request a Proposal','Our Services','How long does a website take?'],
        ],
        [
            'match'=>'/(measure|measurement|reporting|analytics|dashboard|kpi|roi|marketing working)/',
            'title'=>'How does Field Theory measure marketing?',
            'description'=>'We connect marketing activity to clearer signals, not just more reports.',
            'html'=>'<p>Field Theory can define practical KPIs, clean up GA4 and tag management, connect campaigns to conversion events, and build dashboards for decisions. The goal is to show what is working, what is not, and what should change next.</p>',
            'followups'=>['Data, Analysis & Visualization','Digital Marketing & Growth Strategy','Request a Proposal'],
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
    $base = [
        'Get Started',
        'Our Services',
        'Show me your work!',
        'Website Development & Core Tech',
        'Digital Marketing & Growth Strategy',
        'Search & Discovery Optimization',
        'Data, Analysis & Visualization',
        'Technology, Innovation and A.I.',
        'Ecommerce & Conversion',
        'SEO / AEO',
        'AI & Automation',
        'ADA Accessibility',
        'Industries We Support',
        'Where in New Mexico do you work?',
        'I need a website',
        'Do you work in Santa Fe',
        'Can you help with AI search',
        'My website is not generating leads',
        'Can you improve our current site?',
        'How long does a website take?',
        'What budget should I plan for?',
        'How do you measure marketing?',
        'What happens after I request a proposal?',
        'Request a Proposal',
    ];
    return array_values(array_unique($base));
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

function ftc_is_go_time_prompt($term){
    $t = ftc_normalize_prompt_text($term);
    if($t === '') return false;
    return (bool) preg_match('/^(?:it s |its )?go(?:\s|-)?time$/', $t);
}

function ftc_is_get_started_prompt($term){
    if(ftc_is_go_time_prompt($term)) return false;
    $t = ftc_normalize_prompt_text($term);
    return $t !== '' && in_array($t, ['get started','start','home','overview'], true);
}

function ftc_go_time_spline_url(){
    return FTC_URL . 'assets/spline/go-time.splinecode';
}

function ftc_go_time_spline_ajax_url(){
    return add_query_arg([
        'action' => 'ftc_go_time_spline',
        'nonce' => wp_create_nonce('ftc_go_time_spline'),
    ], admin_url('admin-ajax.php'));
}

function ftc_go_time_spline_direct_url(){
    return FTC_URL . 'assets/spline/go-time.php';
}

function ftc_serve_go_time_spline(){
    $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
    if($nonce === '' || !wp_verify_nonce($nonce, 'ftc_go_time_spline')){
        status_header(403);
        exit('Forbidden');
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    $path = FTC_PATH . 'assets/spline/go-time.splinecode';
    if (!is_readable($path)) {
        status_header(404);
        exit('Scene not found');
    }
    nocache_headers();
    header('Content-Type: application/octet-stream');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}
add_action('wp_ajax_nopriv_ftc_go_time_spline', 'ftc_serve_go_time_spline');
add_action('wp_ajax_ftc_go_time_spline', 'ftc_serve_go_time_spline');

function ftc_go_time_spline_community_preview(){
    return 'https://app.spline.design/file/240a5bb3-0f6f-4231-b9f7-533d50207489?view=preview';
}

function ftc_go_time_spline_fallback_urls(){
    return [
        ftc_go_time_spline_direct_url(),
        ftc_go_time_spline_ajax_url(),
    ];
}

function ftc_render_go_time_response($settings){
    $chapters = [
        [
            'id' => 'websites',
            'headline' => 'Websites built to perform.',
            'copy' => 'Fast, accessible, conversion-ready experiences across every screen — engineered for speed, clarity, and growth.',
            'accent' => '#10b981',
        ],
        [
            'id' => 'data',
            'headline' => 'Turn metrics into decisions.',
            'copy' => 'Dashboards, attribution, and reporting that connect the dots — so your team sees what is working and what to do next.',
            'accent' => '#8b5cf6',
        ],
    ];

    echo '<div class="ftc-response-shell ftc-response-layout-go-time ftc-response-go-time" data-ftc-response-prompt="Go Time" data-response-title="Go Time">';
    echo '<section class="ftc-response-content">';
    echo '<div class="ftc-go-time-spline ftc-go-time-spline--boot is-loading" data-ftc-go-time-spline>';
    echo '<div class="ftc-go-time-spline-viewport" aria-hidden="true">';
    echo '<canvas class="ftc-go-time-spline-canvas"></canvas>';
    echo '<iframe class="ftc-go-time-spline-iframe" title="Go Time studio scene" tabindex="-1" loading="eager" allow="fullscreen; autoplay; xr-spatial-tracking"></iframe>';
    echo '</div>';
    echo '<div class="ftc-go-time-spline-atmosphere ftc-go-time-atmosphere" aria-hidden="true"></div>';
    echo '<div class="ftc-go-time-spline-copy">';
    foreach($chapters as $i => $chapter){
        $hidden = $i === 0 ? 'false' : 'true';
        echo '<article class="ftc-go-time-spline-panel'.($i === 0 ? ' is-active' : '').'" data-station="'.(int) $i.'" data-accent="'.esc_attr($chapter['accent']).'" data-card-id="'.esc_attr($chapter['id']).'" aria-hidden="'.$hidden.'">';
        echo '<h2 class="ftc-go-time-headline">'.esc_html($chapter['headline']).'</h2>';
        echo '<p class="ftc-go-time-body">'.esc_html($chapter['copy']).'</p>';
        echo '</article>';
    }
    echo '</div>';
    echo '<div class="ftc-go-time-spline-scroll" aria-hidden="true" style="height:320vh"></div>';
    echo '<section class="ftc-go-time-spline-closing ftc-go-time-closing">';
    echo '<div class="ftc-go-time-closing-inner">';
    echo '<div class="ftc-go-time-closing-grid">';
    echo '<aside class="ftc-go-time-closing-aside">';
    echo '<p class="ftc-go-time-closing-kicker">Ready when you are</p>';
    echo '<h3 class="ftc-go-time-closing-title">Let&rsquo;s build something that moves the needle.</h3>';
    $email = $settings['contact_email'] ?: 'jamie@fieldtheory.ai';
    $phone = trim((string)($settings['contact_phone'] ?? ''));
    if($phone === '') $phone = '(505) 456-3193';
    $phone_link = ftc_phone_link_value($phone);
    echo '<div class="ftc-contact-direct-actions ftc-go-time-closing-actions">';
    if($phone_link){
        echo '<a class="ftc-contact-direct ftc-contact-call" href="tel:'.esc_attr($phone_link).'">Call '.esc_html($phone).'</a>';
        echo '<a class="ftc-contact-direct ftc-contact-text" href="sms:'.esc_attr($phone_link).'">Text '.esc_html($phone).'</a>';
    }
    echo '<a class="ftc-contact-direct ftc-contact-email-link" href="mailto:'.esc_attr($email).'">Email '.esc_html($email).'</a>';
    echo '</div></aside>';
    echo '<div class="ftc-go-time-closing-form">';
    ftc_render_contact_panel($settings, ['quiz_only' => true]);
    echo '</div></div></div>';
    echo '</section>';
    echo '</div>';
    echo '</section>';
    ftc_close_response_shell([]);
}

function ftc_ajax_answer(){
    check_ajax_referer('ftc_nonce','nonce');
    $term = sanitize_text_field(wp_unslash($_POST['term'] ?? ''));
    $term = ftc_resolve_service_task_prompt($term);
    $settings = ftc_get_settings();
    if(ftc_is_go_time_prompt($term)){
        ob_start();
        ftc_render_go_time_response($settings);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    if(ftc_is_get_started_prompt($term)){
        $response = ftc_pick_response('Get Started');
        ob_start();
        ftc_render_get_started_sequence($response,$settings,true);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }

    if(function_exists('ftc_core_response_for_prompt')){
        $core_response = ftc_core_response_for_prompt($term);
        if($core_response){
            ob_start();
            ftc_render_cpt_response_shell($core_response, $settings);
            $html = ob_get_clean();
            wp_send_json_success(['html'=>$html]);
        }
    }

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

    $child_service = ftc_find_service_child_match($term);
    if(ftc_is_exact_child_service_prompt($child_service, $term)){
        ob_start();
        ftc_render_child_service_response($child_service, $settings, $term);
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
        ftc_render_child_service_response($child_service, $settings, $term);
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
    if(!$response && ftc_is_go_time_prompt($term)){
        ob_start();
        ftc_render_go_time_response($settings);
        $html = ob_get_clean();
        wp_send_json_success(['html'=>$html]);
    }
    if(!$response){
        $responses = ftc_get_responses();
        $response = $responses['get_started'] ?? reset($responses);
    }
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
        ['Get Started with Field Theory',null,'/get-started/'],
        ['Tell me about your company','Tell me about your company'],
        ['What services do you provide',null,'/services/'],
        ['Show me your work','Show me your work!'],
        ['Explore FAQs','FAQ'],
        ['Request a Proposal or Contact Us','Request a Proposal']
    ] as $item){
        if(!empty($item[2])){
            echo '<button type="button" class="ftc-menu-prompt" data-redirect="'.esc_attr($item[2]).'"><span>'.esc_html($item[0]).'</span></button>';
        } else {
            echo '<button type="button" class="ftc-menu-prompt" data-prompt="'.esc_attr($item[1]).'"><span>'.esc_html($item[0]).'</span></button>';
        }
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

function ftc_recaptcha_action(){
    return 'ftc_submit_inquiry';
}

function ftc_recaptcha_threshold($settings){
    return max(0, min(1, (float)($settings['recaptcha_threshold'] ?? 0.5)));
}

function ftc_recaptcha_score_too_low($score, $settings){
    return $score < ftc_recaptcha_threshold($settings);
}

function ftc_verify_recaptcha_token($token, $settings){
    $site_key = trim((string)($settings['recaptcha_site_key'] ?? ''));
    $secret_key = trim((string)($settings['recaptcha_secret_key'] ?? ''));
    if($site_key === '' || $secret_key === ''){
        return true;
    }
    if(trim((string)$token) === '') return new WP_Error('ftc_recaptcha_missing', 'Please retry the captcha check.');

    return ftc_verify_recaptcha_siteverify_token($token, $secret_key, $settings);
}

function ftc_verify_recaptcha_siteverify_token($token, $secret_key, $settings){
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
    if(!is_array($body) || empty($body['success'])){
        $codes = is_array($body['error-codes'] ?? null) ? $body['error-codes'] : [];
        if(in_array('timeout-or-duplicate', $codes, true)){
            return new WP_Error('ftc_recaptcha_failed', 'Captcha expired. Please submit again.');
        }
        return new WP_Error('ftc_recaptcha_failed', 'Captcha verification failed. Please try again.');
    }

    $expected_action = ftc_recaptcha_action();
    if(!empty($body['action']) && $body['action'] !== $expected_action){
        return new WP_Error('ftc_recaptcha_action', 'Captcha verification failed. Please refresh and try again.');
    }

    if(!isset($body['score'])){
        return new WP_Error('ftc_recaptcha_score', 'Captcha verification failed. Please try again.');
    }

    if(ftc_recaptcha_score_too_low((float)$body['score'], $settings)){
        return new WP_Error('ftc_recaptcha_score', 'Captcha verification failed. Please try again.');
    }

    return true;
}

function ftc_inquiry_client_ip(){
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach($keys as $key){
        if(empty($_SERVER[$key])) continue;
        $raw = sanitize_text_field(wp_unslash((string)$_SERVER[$key]));
        $first = trim(explode(',', $raw)[0]);
        if(filter_var($first, FILTER_VALIDATE_IP)) return $first;
    }
    return 'unknown';
}

function ftc_inquiry_rate_limit_key($prefix, $subject){
    return 'ftc_rl_' . $prefix . '_' . md5((string)$subject);
}

function ftc_inquiry_rate_limited($key, $limit, $window_seconds){
    $hits = (int) get_transient($key);
    if($hits >= $limit) return true;
    set_transient($key, $hits + 1, $window_seconds);
    return false;
}

function ftc_ajax_submit_inquiry(){
    check_ajax_referer('ftc_nonce','nonce');

    $honeypot = sanitize_text_field(wp_unslash($_POST['ftc_hp'] ?? ''));
    if($honeypot !== ''){
        wp_send_json_error(['message'=>'Could not submit request. Please try again.'], 400);
    }

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

    if($name === '' || !is_email($email)){
        wp_send_json_error(['message'=>'Please provide a valid name and email.'], 400);
    }

    $settings = ftc_get_settings();
    $recaptcha_result = ftc_verify_recaptcha_token(wp_unslash($_POST['recaptcha_token'] ?? ''), $settings);
    if(is_wp_error($recaptcha_result)){
        wp_send_json_error(['message'=>$recaptcha_result->get_error_message()], 403);
    }

    $ip_address = ftc_inquiry_client_ip();
    $ip_key = ftc_inquiry_rate_limit_key('ip', $ip_address);
    if(ftc_inquiry_rate_limited($ip_key, 8, 10 * MINUTE_IN_SECONDS)){
        wp_send_json_error(['message'=>'Please wait a few minutes before submitting again.'], 429);
    }
    $identity_key = ftc_inquiry_rate_limit_key('identity', strtolower($email) . '|' . $ip_address);
    if(ftc_inquiry_rate_limited($identity_key, 3, 30 * MINUTE_IN_SECONDS)){
        wp_send_json_error(['message'=>'You recently submitted a request. Please wait and try again shortly.'], 429);
    }

    $score = ftc_calculate_lead_score($services, $timeline, $budget);
    $title_name = $company ?: $name;
    $lead_id = wp_insert_post([
        'post_type'=>'ftc_lead',
        'post_status'=>'publish',
        'post_title'=>sprintf('Proposal Request - %s - %s', $title_name, current_time('Y-m-d H:i')),
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
    // Strip the concierge shortcode itself to prevent a nested full-app render.
    $html = preg_replace('/\[\/?(?:ft_concierge|field_theory_concierge)[^\]]*\]/i', '', $html);
    // Admin-managed concierge content may include shortcodes such as [elementor-template id="123"].
    return do_shortcode(wp_kses_post($html));
}

function ftc_render_elementor_template_by_id($template_id){
    $template_id = absint($template_id);
    if (!$template_id) return '';
    if(class_exists('\\Elementor\\Plugin')){
        $content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id, true);
        if($content !== '') return $content;
    }
    return do_shortcode('[elementor-template id="' . $template_id . '"]');
}

function ftc_render_response_header_markup($title,$desc,$settings=[],$prompt=''){
    echo '<header class="ftc-response-header"><div class="ftc-kicker">'.esc_html($settings['descriptor'] ?? 'Web Design and Digital Marketing').'</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($title).'">'.esc_html($title).'</h2>';
    if($desc) echo '<div class="ftc-answer-description">'.esc_html($desc).'</div>';
    if($prompt) echo '<span class="ftc-question-chip ftc-question-chip-pop" aria-hidden="true">'.esc_html($prompt).'</span>';
    echo '</header>';
}

function ftc_open_response_shell($layout,$title,$desc,$settings=[],$prompt='',$extra_class=''){
    $classes = trim('ftc-response-shell ftc-response-layout-'.sanitize_html_class($layout).' '.$extra_class);
    echo '<div class="'.esc_attr($classes).'"'.($prompt ? ' data-ftc-response-prompt="'.esc_attr($prompt).'"' : '').' data-response-title="'.esc_attr($title).'">';
    ftc_render_response_header_markup($title,$desc,$settings,$prompt);
}

function ftc_close_response_shell($followups=[]){
    ftc_render_followups($followups);
    echo '</div>';
}

function ftc_render_scroll_more_button($label='Scroll for more'){
    echo '<button type="button" class="ftc-scroll-more" data-ftc-scroll-more aria-label="'.esc_attr($label).'"><span aria-hidden="true"></span></button>';
}

function ftc_render_revert_action_button(){
    echo '<button type="button" class="ftc-revert-action-btn" data-ftc-revert-action aria-label="Go back"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></button>';
}

function ftc_render_response_shell($response,$settings=[],$search_term=''){
    $title=$response['title'] ?? 'Get Started.'; $desc=$response['description'] ?? ''; $layout=$response['layout'] ?? 'none';
    $is_data_analytics_response = (($response['id'] ?? '') === 'cluster-e-data-analytics-viz');
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
    if($layout === 'contact'){
        echo '<div class="ftc-response-shell ftc-response-layout-contact">';
        echo '<section class="ftc-response-content ftc-contact-response-content">';
        ftc_render_contact_panel($settings);
        echo '</section>';
        ftc_render_followups($response['followups'] ?? []);
        echo '</div>';
        return;
    }
    $shell_classes = 'ftc-response-shell ftc-response-layout-'.esc_attr($layout);
    if($is_data_analytics_response) $shell_classes .= ' ftc-response-has-dashboard-gallery';
    echo '<div class="'.esc_attr($shell_classes).'">';
    $prompt = $search_term ?: ($response['prompt'] ?? $title);
    ftc_render_response_header_markup($title,$desc,$settings,$prompt);
    if(!empty($response['html'])) echo '<section class="ftc-answer-body ftc-editable-content">'.ftc_render_editable_html($response['html']).'</section>';
    echo '<section class="ftc-response-content">';
    if($is_data_analytics_response){
        ftc_render_data_vendor_logos_section();
        ftc_render_dashboard_design_gallery();
    }
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
    echo '<div class="ftc-response-shell ftc-response-layout-home ftc-response-sequence ftc-response-sequence-start ftc-get-started-video-only" data-ftc-response-prompt="Get Started" data-response-title="Get Started.">';
    echo '<section class="ftc-response-content ftc-get-started-video-content">';
    if($defer_fragments){
        echo '<span hidden data-ftc-deferred-sequence="get-started" data-ftc-deferred-next="1" data-ftc-deferred-total="4" data-ftc-deferred-prompts="'.esc_attr('How can you help my company?|Show me your work!|Testimonials').'"></span>';
    }
    ftc_render_home_intro_panel($settings);
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
    ftc_open_response_shell('services','Our Services','Explore the main ways Field Theory helps organizations improve websites, marketing, search visibility, ecommerce, automation, and practical AI.',$settings,'How can you help my company?','ftc-response-sequence ftc-response-sequence-services');
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
    $followups = array_values(array_filter((array)($response['followups'] ?? [])));
    $followups = array_values(array_filter($followups, function($prompt){
        return ftc_normalize_prompt_text($prompt) !== ftc_normalize_prompt_text('Show me your work!');
    }));
    array_splice($followups, min(1, count($followups)), 0, ['Show me your work!']);
    ftc_close_response_shell(array_values(array_unique($followups)));
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

function ftc_spline_carousel_url(){
    return 'https://my.spline.design/widgetscarouselcopycopy-cYoGJHfZX5a4XZqTbYCQ46dk-pBV/';
}

function ftc_render_spline_carousel($context = 'response'){
    $aria = 'Field Theory service categories — interactive 3D grid';
    $cards = [
        ['id'=>'websites',  'label'=>'Website Development & Core Tech', 'tagline'=>'Performance-first websites built to convert.'],
        ['id'=>'ecommerce', 'label'=>'Ecommerce & CRO',                  'tagline'=>'Turn browsers into buyers.'],
        ['id'=>'seo',       'label'=>'Search & SEO / AEO',               'tagline'=>'Rank higher, answer smarter.'],
        ['id'=>'marketing', 'label'=>'Digital Marketing & Growth',       'tagline'=>'Campaigns that compound over time.'],
        ['id'=>'ai',        'label'=>'Technology, Innovation and A.I.',  'tagline'=>"Build tomorrow's edge today."],
        ['id'=>'data',      'label'=>'Data, Analysis & Visualization',   'tagline'=>'Turn metrics into decisions.'],
    ];
    echo '<div id="ftc-get-started-grid" class="ftc-services-grid-wrap" role="region" aria-label="'.esc_attr($aria).'">';
    foreach($cards as $i => $card){
        echo '<div class="ftc-grid-card" data-card-idx="'.esc_attr($i).'" data-card-id="'.esc_attr($card['id']).'" role="button" tabindex="0" aria-label="'.esc_attr($card['label']).'">';
        echo '<div class="ftc-grid-canvas"></div>';
        echo '<div class="ftc-grid-info">';
        echo '<strong>'.esc_html($card['label']).'</strong>';
        echo '<p>'.esc_html($card['tagline']).'</p>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';
}

function ftc_render_home_intro_panel($settings){
    echo '<div class="ftc-get-started-video-hero ftc-get-started-video-fade" aria-label="Field Theory Lab overview video">';
    ftc_render_get_started_video($settings);
    echo '</div>';
}

function ftc_get_started_video_url($settings){
    $settings = is_array($settings) ? $settings : [];
    $video = trim((string)($settings['demo_video_url'] ?? ''));
    if($video !== '') return $video;
    $fallback = FTC_PATH . 'assets/video/App_Promo_Preview_1.mp4';
    if(file_exists($fallback)) return FTC_URL . 'assets/video/App_Promo_Preview_1.mp4';
    return FTC_URL . 'assets/video/MobileDesign_FTL_2026.mp4';
}

function ftc_render_get_started_video($settings){
    $video = esc_url(ftc_get_started_video_url($settings));
    echo '<div class="ftc-hero-video-wrap" data-ftc-hero-video>';
    echo '<video class="ftc-hero-video" data-ftc-hero-video-el playsinline muted loop preload="metadata" autoplay disablepictureinpicture controlslist="nodownload noplaybackrate noremoteplayback nofullscreen">';
    echo '<source src="'.$video.'" type="video/mp4">';
    echo '</video>';
    echo '<button type="button" class="ftc-hero-video-toggle" data-ftc-hero-video-toggle aria-label="Pause video" aria-pressed="true"><span class="ftc-hero-video-toggle-icon" aria-hidden="true"></span></button>';
    echo '</div>';
}

function ftc_render_home_panel($settings){
    $video = $settings['demo_video_url'] ?? '';
    $tagline = $settings['tagline'] ?? 'TURN DATA INTO GROWTH.';
    $intro_body = $settings['intro_body'] ?? 'We combine websites, data, marketing, and AI to create measurable business results.';
    echo '<div class="ftc-response-subsection ftc-response-subsection-start" data-response-section="intro">';
    echo '<div class="ftc-home-overview">';
    echo '<div class="ftc-home-media">';
    if($video){
        echo '<video controls autoplay muted loop playsinline preload="metadata"><source src="'.esc_url($video).'"></video>';
    } else {
        echo '<img src="'.esc_url(FTC_URL.'assets/images/placeholder-gray-16x9.svg').'" alt="Field Theory overview">';
    }
    echo '</div>';
    echo '<div class="ftc-home-copy"><h3 class="ftc-intro-heading-fade">'.esc_html($tagline).'</h3><p>'.esc_html($intro_body).'</p><button class="ftc-red-link" type="button" data-prompt="How can you help my company?">How can you help my company?</button></div>';
    echo '</div></div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-services" data-response-section="services"><header class="ftc-subresponse-header"><div><span>How can you help my company?</span><h3>Our Services</h3><p>Explore the main ways Field Theory helps organizations improve websites, marketing, analytics, automation, ecommerce, and customer experience.</p></div><button type="button" data-prompt="How can you help my company?">Explore services</button></header>';
    ftc_render_services_panel(false);
    echo '</div>';

    echo '<div class="ftc-response-subsection ftc-response-subsection-work" data-response-section="portfolio"><header class="ftc-subresponse-header"><div><span>Show me your work!</span><h3>Our Work</h3><p>A sample of Field Theory projects across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.</p></div><button type="button" data-prompt="Show me your work!">Show me your work!</button></header>';
    ftc_render_portfolio_grid(6);
    echo '</div>';

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
        ['photo'=>'team-jamie.jpg','initials'=>'JG','name'=>'Jamie Rushad Gros','role'=>'CEO / Technology Strategist / Technical Lead','bio'=>'Jamie brings 25 years of experience as a website developer and digital marketer. He works at the intersection of technology, creative, and marketing to help organizations make better digital decisions.'],
        ['photo'=>'team-tyler.jpg','initials'=>'TQ','name'=>'Tyler Quintana','role'=>'Full-Stack Developer','bio'=>'Tyler is a web developer with a business background and a graduate of the CNM Ingenuity Full-Stack Developer bootcamp. He focuses on practical web solutions that connect technical decisions to client needs.'],
        ['photo'=>'team-hasti.jpg','initials'=>'HS','name'=>'Hastimal (Hasti) Shah','role'=>'Website Developer','bio'=>'Hasti has worked on more than 200 WordPress and Drupal websites, with expertise across Elementor, Beaver Builder, Bricks, Gutenberg, DIVI, and major WordPress theme ecosystems.'],
    ];
    echo '<aside class="ftc-about-profile ftc-about-team" aria-label="Field Theory team">';
    echo '<h2 class="ftc-team-section-headline">Our Leadership</h2>';
    echo '<div class="ftc-team-roster">';
    foreach($team as $member){
        $photo_url = !empty($member['photo']) ? esc_url(FTC_URL.'assets/images/'.$member['photo']) : '';
        $avatar = $photo_url
            ? '<div class="ftc-team-photo"><img src="'.esc_attr($photo_url).'" alt="'.esc_attr($member['name']).'" loading="lazy" width="128" height="128"></div>'
            : '<div class="ftc-team-initials">'.esc_html($member['initials']).'</div>';
        echo '<article class="ftc-team-member-card">'.$avatar.'<div><span>'.esc_html($member['role']).'</span><h3>'.esc_html($member['name']).'</h3><p>'.esc_html($member['bio']).'</p></div></article>';
    }
    echo '</div>';
    echo '<div class="ftc-profile-meta ftc-team-contact"><dl><div><dt>Based in</dt><dd>Albuquerque, New Mexico</dd></div><div><dt>Email</dt><dd><a href="mailto:jamie@fieldtheory.ai">jamie@fieldtheory.ai</a></dd></div></dl></div>';
    echo '</aside></div>';
}
function ftc_service_is_catalog_visible($post_id){
    return true;
}

function ftc_get_services($limit=-1){
    $q=new WP_Query(['post_type'=>'ftc_service','post_status'=>'publish','posts_per_page'=>$limit,'orderby'=>'menu_order title','order'=>'ASC']);
    if(!$q->have_posts()){ return []; }
    $items=[]; while($q->have_posts()){ $q->the_post(); $id=get_the_ID(); if(!ftc_service_is_catalog_visible($id)) continue; $items[]=['id'=>$id,'title'=>get_the_title(),'desc'=>get_the_excerpt() ?: wp_trim_words(wp_strip_all_tags(get_the_content()),22),'eyebrow'=>get_post_meta($id,'_ftc_service_eyebrow',true),'image'=>get_the_post_thumbnail_url($id,'large') ?: get_post_meta($id,'_ftc_service_image',true),'tasks'=>array_filter(array_map('trim',explode("\n",get_post_meta($id,'_ftc_service_tasks',true))))]; } wp_reset_postdata(); return $items;
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
    if(strpos($t,'creative') !== false || strpos($t,'ai') !== false || strpos($t,'innovation') !== false) return ['Cursor','OpenAI','AI Agents','Automation'];
    return ['Strategy','Design','Build','Measure'];
}

function ftc_render_service_card_markup($item, $featured=false){
    $badges = ftc_service_badges_for_title($item['title']);
    $vkey = ftc_service_visual_key($item['title']);
    echo '<article class="ftc-service-card'.($featured ? ' ftc-service-featured' : '').'"><button type="button" class="ftc-service-open" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
    echo '<div class="ftc-service-3d" data-service="'.esc_attr($vkey).'" aria-hidden="true"></div>';
    echo '<strong>'.esc_html($item['title']).'</strong><p>'.esc_html($item['desc']).'</p>';
    echo '<div class="ftc-tech-badges">'; foreach($badges as $badge) echo '<span>'.esc_html($badge).'</span>'; echo '</div>';
    echo '<em>Explore service &rarr;</em></button></article>';
}

function ftc_service_visual_key($title){
    $t = strtolower((string)$title);
    if(strpos($t,'website') !== false || strpos($t,'web development') !== false || strpos($t,'core tech') !== false) return 'web';
    if(strpos($t,'ecommerce') !== false || strpos($t,'conversion') !== false || strpos($t,'cro') !== false) return 'commerce';
    if(strpos($t,'data') !== false || strpos($t,'analysis') !== false || strpos($t,'visualization') !== false) return 'data';
    if(strpos($t,'search') !== false || strpos($t,'seo') !== false || strpos($t,'aeo') !== false || strpos($t,'discovery') !== false) return 'search';
    if(strpos($t,'marketing') !== false || strpos($t,'growth') !== false) return 'marketing';
    if(strpos($t,'ai') !== false || strpos($t,'automation') !== false) return 'ai';
    if(strpos($t,'innovation') !== false || strpos($t,'technology') !== false) return 'innovation';
    return 'innovation';
}

function ftc_service_uses_webgl_visual($key, $is_detail = false){
    /* All 6 service detail pages get a 3D visual element */
    return $is_detail;
}

function ftc_is_service_catalog_image_url($url){
    $url = strtolower((string)$url);
    if($url === '') return false;
    return strpos($url, '/assets/images/service-') !== false
        || strpos($url, '/assets/images/placeholder-service-') !== false;
}

function ftc_service_detail_hero_image_url($id){
    /* Only real uploaded detail images block WebGL. Card catalog SVGs (_ftc_service_image)
       and slug defaults are for listing cards, not detail hero visuals. */
    $candidates = [];
    $thumb = get_the_post_thumbnail_url($id, 'large');
    if($thumb) $candidates[] = $thumb;
    $detail = trim((string)get_post_meta($id, '_ftc_detail_image', true));
    if($detail) $candidates[] = $detail;
    foreach($candidates as $url){
        if($url && !ftc_is_placeholder_image_url($url) && !ftc_is_service_catalog_image_url($url)){
            return esc_url_raw($url);
        }
    }
    return '';
}

function ftc_render_service_webgl_visual($title, $is_detail = false){
    $key = ftc_service_visual_key($title);
    if($is_detail){
        echo '<div class="ftc-service-3d" data-service="'.esc_attr($key).'" aria-hidden="true"></div>';
        return;
    }
    $classes = 'ftc-service-webgl ftc-service-webgl-'.esc_attr($key).' has-static-fallback';
    echo '<div class="'.esc_attr($classes).'" data-ftc-service-visual="'.esc_attr($key).'" aria-hidden="true"><span></span></div>';
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

function ftc_format_service_task_label($text){
    $text = trim((string)$text);
    if($text === '') return $text;
    $text = preg_replace('/^and\s+/i', '', $text);
    if(preg_match('/[A-Z]{2,}|[A-Z][a-z]|[\/\(\&]/', $text)) return $text;
    return ucwords(strtolower($text));
}

function ftc_service_task_prompt_aliases(){
    return [
        'Website Development' => ['Web Dev', 'web-dev', 'webdev'],
    ];
}

function ftc_service_task_display_label($task){
    $task = trim((string)$task);
    if($task === '') return $task;
    $display_map = [
        'Website Development' => 'Web Dev',
    ];
    foreach($display_map as $canonical=>$display){
        if(ftc_normalize_prompt_text($canonical) === ftc_normalize_prompt_text($task)) return $display;
    }
    return $task;
}

function ftc_resolve_service_task_prompt($term){
    $term = trim((string)$term);
    if($term === '') return '';
    $needle = ftc_normalize_prompt_text($term);
    $needle_loose = function_exists('ftc_route_compact_key_loose') ? ftc_route_compact_key_loose($term) : '';
    foreach(ftc_service_task_prompt_aliases() as $canonical=>$aliases){
        if(ftc_normalize_prompt_text($canonical) === $needle) return $canonical;
        if($needle_loose !== '' && function_exists('ftc_route_compact_key_loose') && $needle_loose === ftc_route_compact_key_loose($canonical)) return $canonical;
        foreach((array)$aliases as $alias){
            if(ftc_normalize_prompt_text($alias) === $needle) return $canonical;
            if($needle_loose !== '' && function_exists('ftc_route_compact_key_loose') && $needle_loose === ftc_route_compact_key_loose($alias)) return $canonical;
        }
    }
    return $term;
}

function ftc_child_category_slug($label){
    return sanitize_title((string)$label);
}

function ftc_child_category_accent_class($index){
    $slot = ((max(1, absint($index)) - 1) % 5) + 1;
    return 'ftc-child-accent-' . $slot;
}

function ftc_service_task_groups($id){
    $tasks_raw = get_post_meta($id,'_ftc_service_tasks',true);
    $lines = array_values(array_filter(array_map('trim',explode("\n",$tasks_raw))));
    $groups = [];
    foreach($lines as $line){
        if(strpos($line,':') !== false){
            [$label,$items] = array_map('trim',explode(':',$line,2));
            $items = array_values(array_filter(array_map(function($item){
                return ftc_format_service_task_label(trim((string)$item));
            }, preg_split('/[,;]+/',$items))));
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

function ftc_service_detail_body_html($id){
    $slug = (string)get_post_field('post_name', $id);
    $map = [
        'ecommerce-conversion-rate-optimization-cro' =>
            '<p>We build and optimize ecommerce experiences that make it easier for customers to find, trust, and buy. Whether you\'re on Shopify, WooCommerce, or a custom platform, we improve the full journey — from product pages and cart flow to checkout and post-purchase retention.</p>' .
            '<p>Our conversion rate optimization work is grounded in real behavioral data: heatmaps, session recordings, funnel analysis, and A/B testing. We identify where buyers are dropping off and test practical changes that compound over time.</p>' .
            '<p><strong>The outcome:</strong> more revenue from the traffic you already have, and a cleaner foundation for the growth you\'re building toward.</p>',
        'website-development-core-tech' =>
            '<p>We design and build websites that explain what you do clearly, load fast, support search, and make it easy for visitors to take the next step. Every project starts with understanding your audience and what they need to move forward.</p>' .
            '<p>Our stack includes WordPress, Drupal, custom builds, headless setups, integrations, and performance optimization. We handle accessibility, Core Web Vitals, structured data, and hosting — so the site works well on every device and in every search context.</p>' .
            '<p><strong>The outcome:</strong> a website your team is proud of, that grows with your organization and doesn\'t need constant repair.</p>',
        'digital-marketing-growth-strategy' =>
            '<p>We connect strategy, content, paid search, social campaigns, conversion, and reporting into a single growth system. Marketing works better when the pieces fit together — when your SEO, ads, content, and measurement all point toward the same goals.</p>' .
            '<p>We plan, run, and optimize campaigns across Google Ads, Meta, email, and organic channels. We track what drives qualified traffic and conversions, not just clicks and impressions.</p>' .
            '<p><strong>The outcome:</strong> a marketing program that compounds — building on what works and cutting what doesn\'t, with clear reporting your team can actually use.</p>',
        'search-discovery-optimization-seo-aeo' =>
            '<p>We help organizations rank higher in traditional search and show up clearly in AI-powered answer engines. Modern search is no longer just keywords — it\'s about structured content, topical authority, and being the source that AI systems cite and surface.</p>' .
            '<p>Our work includes technical SEO audits, content strategy, schema markup, answer engine optimization (AEO), and ongoing performance tracking. We optimize for how real people search and how AI tools interpret and summarize information.</p>' .
            '<p><strong>The outcome:</strong> more organic visibility, more qualified traffic, and a content foundation that builds authority over time.</p>',
        'data-analysis-visualization' =>
            '<p>We help organizations move from scattered metrics to clear, decision-ready reporting. That means setting up proper tracking (GA4, GTM, CRM integrations), building dashboards that reflect your actual business goals, and translating data into actions your team can act on.</p>' .
            '<p>We work with Google Analytics, Looker Studio, Microsoft Clarity, Kissmetrics, and custom reporting setups. For behavior and conversion work, we use heatmaps, session recordings, and funnel analysis to see where users hesitate, drop off, or convert.</p>' .
            '<p>We also help organizations that have data but aren\'t sure what it\'s telling them — identifying patterns, anomalies, and opportunities in existing data.</p>' .
            '<p><strong>The outcome:</strong> a measurement system your team trusts, and reporting that actually informs decisions instead of just filling slides.</p>',
        'creative-technology-innovation' =>
            '<p>We help organizations build practical AI systems, useful automations, and experimental digital tools. Not AI for its own sake — AI where it reduces friction, saves time, improves service, or creates a meaningful advantage for your team or customers.</p>' .
            '<p>Our work includes custom GPT-backed tools, internal knowledge assistants, automated workflows, AI-enhanced content systems, and product prototypes. We pair business context with technical implementation to build things that actually get used.</p>' .
            '<p><strong>The outcome:</strong> a working AI capability that your team understands, uses daily, and can build on — not a proof of concept that sits on a shelf.</p>',
    ];
    return $map[$slug] ?? '';
}

function ftc_render_data_vendor_logos_section(){
    $logo_base = FTC_URL . 'assets/images/logos/vendors/';
    echo '<div class="ftc-data-vendors" aria-label="Analytics and reporting platforms Field Theory works with">';
    echo '<ul class="ftc-data-vendors-logos">';
    foreach([
        ['google-analytics.svg', 'Google Analytics', 'Google Analytics', 'ftc-vendor-logo-wide'],
        ['google-tag-manager.svg', 'Google Tag Manager', 'Google Tag Manager', 'ftc-vendor-logo-wide'],
        ['looker-studio.svg', 'Looker Studio', 'Looker Studio', 'ftc-vendor-logo-wide'],
        ['microsoft-clarity.svg', 'Microsoft Clarity', 'Microsoft Clarity', 'ftc-vendor-logo-wide'],
        ['kissmetrics.svg', 'Kissmetrics', 'Kissmetrics', ''],
        ['hubspot.svg', 'HubSpot', 'HubSpot', ''],
    ] as $brand){
        $img_class = trim('ftc-vendor-logo '.($brand[3] ?? ''));
        echo '<li>';
        echo '<button type="button" class="ftc-data-vendors-link" data-prompt="'.esc_attr($brand[2]).'" aria-label="'.esc_attr($brand[1]).' — learn how Field Theory works with '.$brand[1].'">';
        echo '<img class="'.esc_attr($img_class).'" src="'.esc_url($logo_base.$brand[0]).'" alt="'.esc_attr($brand[1]).'" loading="lazy" width="168" height="32">';
        echo '</button></li>';
    }
    echo '</ul></div>';
}

function ftc_render_ai_toolkit_section(){
    echo '<section class="ftc-ai-toolkit" aria-label="AI tools and models Field Theory uses">';
    echo '<div class="ftc-ai-toolkit-intro">';
    echo '<span class="ftc-ai-toolkit-kicker">Model Strategy</span>';
    echo '<h3>Models &amp; applications</h3>';
    echo '<p>We choose model families based on the work itself: rapid drafting, deep reasoning, multimodal analysis, retrieval-backed assistants, and automated workflows tied to your business systems.</p>';
    echo '</div>';
    echo '<div class="ftc-ai-toolkit-models">';
    echo '<h4>Core model stack</h4>';
    echo '<ul class="ftc-ai-model-list">';
    foreach([
        ['OpenAI GPT-4o / GPT-4.1', 'Content drafting, code generation, structured outputs, and customer-facing assistants'],
        ['OpenAI o-series', 'Multi-step reasoning, planning, and complex workflow decisions'],
        ['Claude (Anthropic)', 'Long-context analysis, document review, and careful technical writing'],
        ['Gemini (Google)', 'Multimodal tasks, search-adjacent workflows, and Google ecosystem integrations'],
        ['Embeddings &amp; RAG', 'Internal knowledge search, support tools, and retrieval-backed assistants'],
        ['Cursor Agents', 'Faster implementation, refactors, tests, and production-ready prototypes'],
    ] as $row){
        echo '<li><strong>'.esc_html($row[0]).'</strong><span>'.esc_html($row[1]).'</span></li>';
    }
    echo '</ul></div></section>';
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
    $vkey = ftc_service_visual_key($item['title']);
    echo '<article class="ftc-service-category-card"><button type="button" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
    echo '<div class="ftc-service-3d" data-service="'.esc_attr($vkey).'" aria-hidden="true"></div>';
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
    echo '</section>';
}

function ftc_render_services_all_content(){
    $items=ftc_get_services(-1);
    if(!$items){ return; }
    echo '<section class="ftc-services-all-content ftc-services-child-directory" aria-label="All Field Theory services">';
    foreach($items as $item){
        echo '<article class="ftc-child-service-group">';
        echo '<header><span>'.esc_html($item['eyebrow'] ?: 'SERVICE').'</span><h3>'.esc_html($item['title']).'</h3><p>'.esc_html($item['desc']).'</p><button type="button" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">Open full service</button></header>';
        echo '</article>';
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
        $badges = ftc_service_badges_for_title($item['title']);
        $vkey = ftc_service_visual_key($item['title']);
        echo '<article class="ftc-service-card"><button type="button" class="ftc-service-open" data-ftc-service="'.esc_attr($item['id']).'" data-ftc-service-label="'.esc_attr($item['title']).'">';
        echo '<div class="ftc-service-3d" data-service="'.esc_attr($vkey).'" aria-hidden="true"></div>';
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
        ['/(heatmap|heat map|session recording|microsoft clarity|clarity|kissmetrics|kiss metrics|behavioral analytics)/', [
            'Use '.$topic.' to see where users hesitate, click, scroll, and drop off before you change the experience.',
            'Connect heatmaps and session recordings to funnel analysis so UX and conversion improvements are grounded in real behavior.',
            'Turn behavioral findings into practical tests, page fixes, and reporting your team can act on.',
        ]],
        ['/(bigquery|big query|looker studio|tableau|google tag manager|gtm)/', [
            'Set up '.$topic.' with clean naming, ownership, and source structure so reporting stays trustworthy as data volume grows.',
            'Connect '.$topic.' to the business questions your team needs answered across marketing, product, and operations.',
            'Build reporting flows that make '.$topic.' easier to maintain, audit, and extend over time.',
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

function ftc_child_service_logo_files($task){
    $key = ftc_normalize_prompt_text($task);
    if($key === '') return null;

    $exact = [
        'drupal' => ['drupal.svg'],
        'wordpress' => ['wordpress.svg'],
        'react' => ['react.svg'],
        'next.js' => ['nextdotjs.svg'],
        'nextjs' => ['nextdotjs.svg'],
        'node.js' => ['nodedotjs.svg'],
        'nodejs' => ['nodedotjs.svg'],
        'php' => ['php.svg'],
        'custom cms development' => ['cms-custom.svg'],
        'acquia' => ['acquia.svg'],
        'pantheon' => ['pantheon.svg'],
        'shopify' => ['shopify.svg'],
        'shopify plus' => ['shopify.svg'],
        'woocommerce' => ['woocommerce.svg'],
        'cloud hosting platforms' => ['amazonaws.svg', 'googlecloud.svg', 'microsoftazure.svg'],
        'ecommerce platforms' => ['shopify.svg', 'woocommerce.svg', 'magento.svg'],
        'custom ecommerce platforms' => ['shopify.svg', 'woocommerce.svg', 'magento.svg'],
        'custom ecommerce solutions' => ['shopify.svg', 'woocommerce.svg', 'magento.svg'],
        'advertising platforms' => ['meta.svg', 'instagram.svg', 'linkedin.svg', 'google.svg', 'amazon.svg', 'x.svg', 'pinterest.svg', 'reddit.svg'],
        'crm platforms' => ['hubspot.svg', 'salesforce.svg', 'zoho.svg'],
        'google analytics' => ['googleanalytics.svg'],
        'google tag manager' => ['googletagmanager.svg'],
        'looker studio' => ['lookerstudio.svg'],
        'tableau' => ['tableau.svg'],
        'bigquery' => ['bigquery.svg'],
        'microsoft clarity' => ['microsoftclarity.svg'],
        'kissmetrics' => ['kissmetrics.svg'],
        'heatmaps' => ['microsoftclarity.svg'],
        'session recordings' => ['microsoftclarity.svg'],
        'google search console' => ['googlesearchconsole.svg'],
        'google ads' => ['google.svg'],
        'google ads certified' => ['google.svg'],
        'google display network' => ['google.svg'],
        'google shopping' => ['google.svg'],
        'youtube advertising' => ['youtube.svg'],
        'meta advertising' => ['meta.svg'],
        'facebook advertising' => ['meta.svg'],
        'meta commerce' => ['meta.svg'],
        'instagram advertising' => ['instagram.svg'],
        'linkedin advertising' => ['linkedin.svg'],
        'amazon advertising' => ['amazon.svg'],
        'amazon store optimization' => ['amazon.svg'],
        'shopify development' => ['shopify.svg'],
        'woocommerce development' => ['woocommerce.svg'],
        'hubspot' => ['hubspot.svg'],
        'salesforce' => ['salesforce.svg'],
        'zoho' => ['zoho.svg'],
        'crm integrations' => ['hubspot.svg', 'salesforce.svg', 'zoho.svg'],
        'crm automation' => ['hubspot.svg', 'salesforce.svg', 'zoho.svg'],
    ];
    if(isset($exact[$key])) return $exact[$key];

    $rules = [
        '/\bgoogle ads\b/' => ['google.svg'],
        '/\bgoogle display\b/' => ['google.svg'],
        '/\bgoogle shopping\b/' => ['google.svg'],
        '/\byoutube advertising\b/' => ['youtube.svg'],
        '/\bmeta advertising\b/' => ['meta.svg'],
        '/\bfacebook advertising\b/' => ['meta.svg'],
        '/\bmeta commerce\b/' => ['meta.svg'],
        '/\binstagram advertising\b/' => ['instagram.svg'],
        '/\blinkedin advertising\b/' => ['linkedin.svg'],
        '/\bamazon advertising\b/' => ['amazon.svg'],
        '/\bamazon store\b/' => ['amazon.svg'],
        '/\bshopify\b/' => ['shopify.svg'],
        '/\bwoocommerce\b/' => ['woocommerce.svg'],
        '/\bmagento\b/' => ['magento.svg'],
        '/\bhubspot\b/' => ['hubspot.svg'],
        '/\bsalesforce\b/' => ['salesforce.svg'],
        '/\bzoho\b/' => ['zoho.svg'],
        '/\bgoogle analytics\b/' => ['googleanalytics.svg'],
        '/\bgoogle tag manager\b/' => ['googletagmanager.svg'],
        '/\blooker studio\b/' => ['lookerstudio.svg'],
        '/\btableau\b/' => ['tableau.svg'],
        '/\bbig\s?query\b/' => ['bigquery.svg'],
        '/\bmicrosoft clarity\b/' => ['microsoftclarity.svg'],
        '/\bkissmetrics\b/' => ['kissmetrics.svg'],
        '/\bheatmap(s)?\b/' => ['microsoftclarity.svg'],
        '/\bsession recordings?\b/' => ['microsoftclarity.svg'],
        '/\bgoogle search console\b/' => ['googlesearchconsole.svg'],
        '/\bsearch console\b/' => ['googlesearchconsole.svg'],
        '/\bpinterest\b/' => ['pinterest.svg'],
        '/\breddit ads\b/' => ['reddit.svg'],
        '/\btwitter advertising\b/' => ['x.svg'],
        '/\bx advertising\b/' => ['x.svg'],
        '/\bretargeting campaigns\b/' => ['meta.svg', 'google.svg'],
        '/\bperformance marketing\b/' => ['google.svg', 'meta.svg'],
    ];
    foreach($rules as $pattern => $files){
        if(preg_match($pattern, $key)) return $files;
    }

    return null;
}

function ftc_is_heatmap_analytics_task($task, $group = ''){
    $key = ftc_normalize_prompt_text($task);
    $group_key = ftc_normalize_prompt_text($group);
    if($key === '') return false;

    $exact = [
        'heatmaps',
        'session recordings',
        'microsoft clarity',
        'kissmetrics',
        'scroll analysis',
        'click tracking',
    ];
    if(in_array($key, $exact, true)) return true;

    if(strpos($group_key, 'heatmapping') !== false || strpos($group_key, 'session analytics') !== false) return true;

    $patterns = [
        '/\bheatmap(s)?\b/',
        '/\bsession recordings?\b/',
        '/\bmicrosoft clarity\b/',
        '/\bkissmetrics\b/',
        '/\bscroll analysis\b/',
        '/\bclick tracking\b/',
    ];
    foreach($patterns as $pattern){
        if(preg_match($pattern, $key)) return true;
    }

    return false;
}

function ftc_render_heatmap_analytics_visual(){
    $file = 'clarity-heatmap-mockup.svg';
    $path = FTC_PATH . 'assets/images/heatmaps/' . $file;
    if(!is_readable($path) && !file_exists($path)) return false;
    $url = FTC_URL . 'assets/images/heatmaps/' . $file;
    echo '<div class="ftc-child-service-logo is-heatmap-visual" aria-label="Heatmap and Microsoft Clarity analytics example">';
    echo '<img src="'.esc_url($url).'" alt="Heatmap visualization with click hotspots and Microsoft Clarity branding" loading="lazy" decoding="async" />';
    echo '</div>';
    return true;
}

function ftc_render_child_service_logo($task, $group = ''){
    if(ftc_is_heatmap_analytics_task($task, $group)){
        if(ftc_render_heatmap_analytics_visual()) return;
    }

    $files = ftc_child_service_logo_files($task);
    if(!$files) return;
    $images = '';
    foreach($files as $file){
        $path = FTC_PATH . 'assets/images/logos/' . $file;
        if(!is_readable($path) && !file_exists($path)) continue;
        $url = FTC_URL . 'assets/images/logos/' . $file;
        $images .= '<img src="'.esc_url($url).'" alt="" loading="lazy" decoding="async" />';
    }
    if($images === '') return;
    echo '<div class="ftc-child-service-logo" aria-hidden="true">'.$images.'</div>';
}

function ftc_is_customer_journey_mapping_task($task, $prompt = ''){
    foreach(array_filter([$task, $prompt], 'strlen') as $value){
        if(ftc_normalize_prompt_text($value) === ftc_normalize_prompt_text('Customer Journey Mapping')) return true;
    }
    return false;
}

function ftc_is_dashboard_design_task($task){
    return ftc_normalize_prompt_text($task) === ftc_normalize_prompt_text('Dashboard Design');
}

function ftc_is_quizzes_assessments_task($task, $prompt = ''){
    $target = function_exists('ftc_route_compact_key_loose')
        ? ftc_route_compact_key_loose('Quizzes & Assessments')
        : 'quizzesassessments';
    foreach(array_filter([$task, $prompt], 'strlen') as $value){
        if(function_exists('ftc_route_compact_key_loose') && ftc_route_compact_key_loose($value) === $target) return true;
        if(ftc_normalize_prompt_text($value) === ftc_normalize_prompt_text('Quizzes & Assessments')) return true;
    }
    return false;
}

function ftc_render_child_service_examples_section_open($box_class = ''){
    $box_class = trim((string)$box_class);
    echo '<section class="ftc-child-service-examples" aria-labelledby="ftc-child-service-examples-title">';
    echo '<h3 class="ftc-child-service-examples-title" id="ftc-child-service-examples-title">Examples</h3>';
    echo '<div class="ftc-child-service-examples-box'.($box_class !== '' ? ' '.esc_attr($box_class) : '').'">';
}

function ftc_render_child_service_examples_section_close(){
    echo '</div></section>';
}

function ftc_render_ai_website_assessment_example(){
    echo '<div class="ftc-ai-assessment-example" aria-label="AI Website Assessment example">';
    echo '<div class="ft-ai-assessment" data-ft-ai-assessment>';
    echo '<div class="ft-ai-card">';
    echo '<div class="ft-ai-glow" aria-hidden="true"></div>';
    echo '<div class="ft-ai-header">';
    echo '<div class="ft-ai-mark" aria-hidden="true">FT</div>';
    echo '<div><div class="ft-ai-kicker">AI Website Assessment</div><h2>How ready is your website for growth?</h2></div>';
    echo '</div>';
    echo '<div class="ft-ai-meta">';
    echo '<span data-ft-ai-step-label>Question 1 of 5</span>';
    echo '<span data-ft-ai-score-preview>Live Score: 0%</span>';
    echo '</div>';
    echo '<div class="ft-ai-progress"><div data-ft-ai-progress-bar></div></div>';
    echo '<div data-ft-ai-content></div>';
    echo '<div class="ft-ai-actions">';
    echo '<button type="button" class="ft-ai-btn ft-ai-secondary" data-ft-ai-back>Back</button>';
    echo '<button type="button" class="ft-ai-btn" data-ft-ai-next>Continue</button>';
    echo '</div></div></div></div>';
}

function ftc_render_client_pulse_example(){
    echo '<div class="ftc-client-pulse-example" aria-label="Client Pulse NPS example">';
    echo '<div class="ft-pulse" data-ft-client-pulse>';
    echo '<div class="ft-pulse-shell">';
    echo '<div class="ft-pulse-left">';
    echo '<div class="ft-pulse-tag">Client Pulse</div>';
    echo '<h2>How likely are you to recommend us?</h2>';
    echo '<p>A simple branded review experience for collecting NPS, feedback, and testimonial-ready comments.</p>';
    echo '<div class="ft-pulse-stats">';
    echo '<div><strong data-ft-pulse-score>0</strong><span>NPS Score</span></div>';
    echo '<div><strong data-ft-pulse-type>Pending</strong><span>Segment</span></div>';
    echo '</div></div>';
    echo '<div class="ft-pulse-right"><div data-ft-pulse-content></div></div>';
    echo '</div></div></div>';
}

function ftc_render_quizzes_examples_row(){
    echo '<div class="ftc-quizzes-examples-row">';
    ftc_render_ai_website_assessment_example();
    ftc_render_client_pulse_example();
    echo '</div>';
}

function ftc_dashboard_design_gallery_items(){
    return [
        ['file' => 'population-growth-bar-chart.png', 'alt' => 'Population Growth bar chart dashboard with country rankings and year-over-year totals'],
        ['file' => 'linkedin-company-pages.png', 'alt' => 'LinkedIn Company Pages overview dashboard with follower growth and engagement metrics'],
        ['file' => 'monthly-enrollments.png', 'alt' => 'Monthly Enrollments dashboard with enrollment trends and program breakdowns'],
        ['file' => 'usa-map-by-count.png', 'alt' => 'Interactive USA Map by Count dashboard with regional data visualization'],
    ];
}

function ftc_render_dashboard_design_gallery(){
    $items = ftc_dashboard_design_gallery_items();
    $images = '';
    foreach($items as $item){
        $path = FTC_PATH . 'assets/images/dashboard-design/' . $item['file'];
        if(!is_readable($path) && !file_exists($path)) continue;
        $url = FTC_URL . 'assets/images/dashboard-design/' . $item['file'];
        $images .= '<figure class="ftc-dashboard-design-gallery-item"><img src="'.esc_url($url).'" alt="'.esc_attr($item['alt']).'" loading="lazy" decoding="async" /></figure>';
    }
    if($images === '') return;
    echo '<div class="ftc-dashboard-design-gallery-example" aria-label="Dashboard design examples">';
    echo '<div class="ftc-dashboard-design-gallery">'.$images.'</div>';
    echo '</div>';
}

function ftc_journey_map_icon($name){
    $icons = [
        'energy' => '<path d="M9 1.5 4.5 9h3.5l-1 5.5L11.5 7H8l1-5.5z" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>',
        'aerospace' => '<path d="M1.5 8.5 6 7l8-5.5 2.5 1-2 3.5L8.5 11 6 14.5l-1-3.5-3.5-.5z" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>',
        'manufacturing' => '<path d="M2 13V6l3-2 3 2v2l3-1.5V4l3 2v9H2z" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/><path d="M6 8v5M9 6.5v6.5" stroke="currentColor" stroke-width="1.1"/>',
        'ceo' => '<circle cx="8" cy="5.5" r="2.2" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M3.5 13.5c.4-2.4 2.2-3.8 4.5-3.8s4.1 1.4 4.5 3.8" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'operations' => '<circle cx="8" cy="8" r="2.2" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M8 1.5v1.6M8 12.9v1.6M1.5 8h1.6M12.9 8h1.6M3.4 3.4l1.1 1.1M11.5 11.5l1.1 1.1M12.6 3.4l-1.1 1.1M4.5 11.5l-1.1 1.1" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'site-selector' => '<path d="M8 1.5C5.5 1.5 3.5 3.8 3.5 6.5 3.5 10 8 14.5 8 14.5s4.5-4.5 4.5-8C12.5 3.8 10.5 1.5 8 1.5z" fill="none" stroke="currentColor" stroke-width="1.1"/><circle cx="8" cy="6.5" r="1.5" fill="none" stroke="currentColor" stroke-width="1.1"/>',
        'google-ads' => '<circle cx="7" cy="7" r="4.5" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M10.5 10.5 13.5 13.5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'linkedin-ads' => '<rect x="2" y="2" width="12" height="12" rx="1.5" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M4.5 6.5v5M4.5 4.5v.5M7.5 11.5V8.8c0-1 .8-1.8 1.8-1.8s1.7.8 1.7 1.8v2.7" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>',
        'display-ads' => '<rect x="2" y="3" width="12" height="8.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M5.5 11.5h5" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'email-marketing' => '<rect x="2" y="4" width="12" height="8.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="m2 4.5 6 4.5 6-4.5" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>',
        'globe' => '<circle cx="8" cy="8" r="5.5" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M2.5 8h11M8 2.5c1.8 1.6 2.8 3.6 2.8 5.5S9.8 11.9 8 13.5c-1.8-1.6-2.8-3.6-2.8-5.5S6.2 4.1 8 2.5z" fill="none" stroke="currentColor" stroke-width="1.1"/>',
        'users' => '<circle cx="6" cy="5.5" r="1.8" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M2.5 13c.3-2 1.7-3.2 3.5-3.2s3.2 1.2 3.5 3.2" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/><circle cx="11.5" cy="6" r="1.5" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M9.5 13c.4-1.6 1.4-2.5 2.8-2.5" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'lead-capture' => '<rect x="3.5" y="2.5" width="9" height="11" rx="1" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M5.5 6h5M5.5 8.5h5M5.5 11h3" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/>',
        'crm' => '<ellipse cx="8" cy="4.5" rx="4.5" ry="1.8" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="M3.5 4.5v3c0 1 2 1.8 4.5 1.8s4.5-.8 4.5-1.8v-3M3.5 7.5v3c0 1 2 1.8 4.5 1.8s4.5-.8 4.5-1.8v-3" fill="none" stroke="currentColor" stroke-width="1.1"/>',
        'email' => '<rect x="2" y="4.5" width="12" height="7.5" rx="1" fill="none" stroke="currentColor" stroke-width="1.1"/><path d="m2 5 6 4 6-4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linejoin="round"/>',
        'retarget' => '<path d="M11.5 2.5A5.5 5.5 0 0 0 4 8" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/><path d="M4.5 2.5H4v4h4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/><path d="M4.5 13.5A5.5 5.5 0 0 0 12 8" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round"/><path d="M11.5 13.5h.5v-4h-4" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/>',
        'analytics' => '<path d="M2.5 13.5V8M6 13.5V5.5M9.5 13.5V7.5M13 13.5V3.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>',
    ];
    if(!isset($icons[$name])) return '';
    return '<svg class="ftc-journey-map-icon" viewBox="0 0 16 16" aria-hidden="true">'.$icons[$name].'</svg>';
}

function ftc_journey_map_node($icon, $label){
    return '<span class="ftc-journey-map-node">'.ftc_journey_map_icon($icon).'<span class="ftc-journey-map-label">'.$label.'</span></span>';
}

function ftc_render_customer_journey_map(){
    echo '<div class="ftc-journey-map-shell" aria-label="Customer journey map diagram">';
    echo '<div class="ftc-journey-map-scaler"><div class="ftc-journey-map">';
    echo '<h3 class="ftc-journey-map-title">Demand Generation Journey</h3>';

    echo '<div class="ftc-journey-map-section" style="left:37px;top:49px;">Industries</div>';
    echo '<div class="ftc-journey-map-section" style="left:185px;top:49px;">Personas</div>';
    echo '<div class="ftc-journey-map-section" style="left:334px;top:49px;">Marketing</div>';
    echo '<div class="ftc-journey-map-section" style="left:468px;top:49px;">Website Experience</div>';
    echo '<div class="ftc-journey-map-section" style="left:683px;top:49px;">Nurture</div>';

    echo '<div class="ftc-journey-map-circle" style="left:30px;top:89px;">'.ftc_journey_map_node('energy', 'Energy').'</div>';
    echo '<div class="ftc-journey-map-circle" style="left:30px;top:200px;">'.ftc_journey_map_node('aerospace', 'Aerospace').'</div>';
    echo '<div class="ftc-journey-map-circle" style="left:30px;top:312px;">'.ftc_journey_map_node('manufacturing', 'Manufacturing').'</div>';

    echo '<div class="ftc-journey-map-circle" style="left:178px;top:89px;">'.ftc_journey_map_node('ceo', 'CEO').'</div>';
    echo '<div class="ftc-journey-map-circle" style="left:178px;top:200px;">'.ftc_journey_map_node('operations', 'Operations').'</div>';
    echo '<div class="ftc-journey-map-circle" style="left:178px;top:312px;">'.ftc_journey_map_node('site-selector', 'Site Selector').'</div>';

    echo '<div class="ftc-journey-map-box" style="left:327px;top:89px;width:115px;">'.ftc_journey_map_node('google-ads', 'Google Ads').'</div>';
    echo '<div class="ftc-journey-map-box" style="left:327px;top:178px;width:115px;">'.ftc_journey_map_node('linkedin-ads', 'LinkedIn Ads').'</div>';
    echo '<div class="ftc-journey-map-box" style="left:327px;top:267px;width:115px;">'.ftc_journey_map_node('display-ads', 'Display Ads').'</div>';
    echo '<div class="ftc-journey-map-box" style="left:327px;top:356px;width:115px;">'.ftc_journey_map_node('email-marketing', 'Email Marketing').'</div>';

    echo '<div class="ftc-journey-map-browser-wrap" style="left:468px;top:89px;">';
    echo '<div class="ftc-journey-map-browser">';
    echo '<div class="ftc-journey-map-browser-bar">'.ftc_journey_map_icon('globe').'</div><div class="ftc-journey-map-browser-hero"></div>';
    echo '<div class="ftc-journey-map-browser-cards"><div></div><div></div><div></div></div>';
    echo '</div><div class="ftc-journey-map-browser-label">'.ftc_journey_map_icon('globe').'<span>Homepage</span></div></div>';

    echo '<div class="ftc-journey-map-browser-wrap" style="left:468px;top:230px;">';
    echo '<div class="ftc-journey-map-browser">';
    echo '<div class="ftc-journey-map-browser-bar">'.ftc_journey_map_icon('users').'</div><div class="ftc-journey-map-browser-hero is-compact"></div>';
    echo '<div class="ftc-journey-map-browser-cards"><div></div><div></div></div>';
    echo '</div><div class="ftc-journey-map-browser-label">'.ftc_journey_map_icon('users').'<span>Persona Pages</span></div></div>';

    echo '<div class="ftc-journey-map-circle is-lead" style="left:690px;top:208px;">'.ftc_journey_map_node('lead-capture', 'Lead<br>Capture').'</div>';
    echo '<div class="ftc-journey-map-box is-crm" style="left:809px;top:212px;">'.ftc_journey_map_node('crm', 'CRM').'</div>';
    echo '<div class="ftc-journey-map-circle is-nurture is-sm" style="left:683px;top:82px;">'.ftc_journey_map_node('email', 'Email').'</div>';
    echo '<div class="ftc-journey-map-circle is-nurture is-sm" style="left:764px;top:82px;">'.ftc_journey_map_node('retarget', 'Retarget').'</div>';
    echo '<div class="ftc-journey-map-analytics" style="left:720px;top:371px;">'.ftc_journey_map_icon('analytics').'<span class="ftc-journey-map-label">Analytics</span><span class="ftc-journey-map-label">&amp; Optimize</span></div>';

    echo '<svg class="ftc-journey-map-svg" viewBox="0 0 920 540" aria-hidden="true">';
    echo '<defs><marker id="ftc-journey-arrowhead" markerWidth="8" markerHeight="6" refX="8" refY="3" orient="auto"><polygon points="0 0, 8 3, 0 6" fill="currentColor"/></marker></defs>';
    echo '<path class="ftc-journey-map-arrow" d="M120 134 L178 134"/>';
    echo '<path class="ftc-journey-map-arrow" d="M120 245 L178 245"/>';
    echo '<path class="ftc-journey-map-arrow" d="M120 357 L178 357"/>';
    echo '<path class="ftc-journey-map-arrow" d="M223 134 L327 106"/>';
    echo '<path class="ftc-journey-map-arrow" d="M223 245 L327 195"/>';
    echo '<path class="ftc-journey-map-arrow" d="M223 357 L327 284"/>';
    echo '<path class="ftc-journey-map-arrow" d="M442 106 L468 146"/>';
    echo '<path class="ftc-journey-map-arrow" d="M442 195 L468 278"/>';
    echo '<path class="ftc-journey-map-arrow" d="M442 284 L468 278"/>';
    echo '<path class="ftc-journey-map-arrow" d="M609 146 L690 253"/>';
    echo '<path class="ftc-journey-map-arrow" d="M609 278 L690 253"/>';
    echo '<path class="ftc-journey-map-arrow" d="M780 253 L809 256"/>';
    echo '<path class="ftc-journey-map-arrow" d="M866 256 L798 116"/>';
    echo '<path class="ftc-journey-map-arrow" d="M866 256 L717 116"/>';
    echo '<path class="ftc-journey-map-loop" d="M774 425 C650 470, 310 440, 310 380 L310 106 L327 106"/>';
    echo '</svg>';

    echo '</div></div></div>';
}

function ftc_render_child_service_response($match, $settings=[], $prompt = ''){
    $service_id = absint($match['service_id'] ?? 0);
    $task = trim((string)($match['task'] ?? 'Service'));
    $prompt = trim((string)$prompt);
    $group = trim((string)($match['group'] ?? ''));
    $service_title = $match['service_title'] ?? ($service_id ? get_the_title($service_id) : 'Field Theory service');
    $siblings = array_values(array_filter((array)($match['tasks'] ?? [])));
    $related = array_values(array_slice(array_filter($siblings, function($item) use ($task){
        return ftc_normalize_prompt_text($item) !== ftc_normalize_prompt_text($task);
    }), 0, 4));
    $heading = 'Here is how we approach ' . $task . '.';
    $description = 'Part of ' . $service_title;

    $show_journey_map = ftc_is_customer_journey_mapping_task($task, $prompt);
    $show_dashboard_gallery = ftc_is_dashboard_design_task($task);
    $show_ai_assessment = ftc_is_quizzes_assessments_task($task, $prompt);
    $show_examples = $show_journey_map || $show_dashboard_gallery || $show_ai_assessment;
    $shell_classes = 'ftc-response-shell ftc-response-layout-child-service';
    if($show_examples) $shell_classes .= ' ftc-response-has-examples';
    echo '<div class="'.esc_attr($shell_classes).'" data-response-title="'.esc_attr($heading).'" data-ftc-response-prompt="'.esc_attr($task).'">';
    echo '<header class="ftc-response-header"><div class="ftc-kicker">'.esc_html($description).'</div><h2 class="ftc-answer-heading ftc-typewriter" data-text="'.esc_attr($heading).'">'.esc_html($heading).'</h2></header>';
    echo '<section class="ftc-response-content">';
    echo '<div class="ftc-child-service-response">';
    echo '<div class="ftc-child-service-answer"><span>'.esc_html($description).'</span>';
    ftc_render_child_service_logo($task, $group);
    echo '<h3>What we provide:</h3>';
    echo '<ul class="ftc-child-service-bullets">';
    foreach(ftc_child_service_points($task, $service_title, $group) as $point) echo '<li>'.esc_html($point).'</li>';
    echo '</ul>';
    echo '</div>';
    if($siblings){
        echo '<aside class="ftc-child-service-related"><h4>Related services</h4><div>';
        foreach($siblings as $sibling){
            echo '<button type="button" data-prompt="'.esc_attr($sibling).'"'.(ftc_normalize_prompt_text($sibling) === ftc_normalize_prompt_text($task) ? ' class="is-current"' : '').'>'.esc_html(ftc_service_task_display_label($sibling)).'</button>';
        }
        echo '</div></aside>';
    }
    echo '</div>';
    if($show_examples){
        ftc_render_child_service_examples_section_open($show_ai_assessment ? 'ftc-child-service-examples-box--quiz' : '');
        if($show_journey_map) ftc_render_customer_journey_map();
        if($show_dashboard_gallery) ftc_render_dashboard_design_gallery();
        if($show_ai_assessment) ftc_render_quizzes_examples_row();
        ftc_render_child_service_examples_section_close();
    }
    echo '<div class="ftc-response-actions ftc-child-service-actions"><div class="ftc-response-actions-left">';
    ftc_render_revert_action_button();
    echo '<button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button>';
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

    /* Canonical service detail layout: .ftc-service-detail-hero (copy | visual) + .ftc-child-grid. Intentional — do not revert to old stacked layouts. */
    $title = get_the_title($id);
    $visual_key = ftc_service_visual_key($title);
    $hero_image = ftc_service_detail_hero_image_url($id);
    $use_webgl = !$hero_image && ftc_service_uses_webgl_visual($visual_key, true);

    /* Build body copy: prefer post_content if it has visible text, otherwise use
       curated description or excerpt so the left column is never empty. */
    $raw_content = apply_filters('the_content', get_post_field('post_content', $id));
    $visible_text = trim(wp_strip_all_tags($raw_content));
    if (!$visible_text) {
        /* post_content is empty or Elementor-only (renders with zero height) */
        $raw_content = ftc_service_detail_body_html($id);
        if (!$raw_content) {
            $excerpt = get_the_excerpt($id);
            $raw_content = $excerpt ? '<p>' . esc_html($excerpt) . '</p>' : '';
        }
    }

    echo '<div class="ftc-service-detail"><div class="ftc-service-detail-hero"><div class="ftc-service-detail-copy"><div class="ftc-service-detail-body">'.wp_kses_post($raw_content).'</div>';
    if($visual_key === 'data'){
        ftc_render_data_vendor_logos_section();
    }
    echo '</div>';
    $visual_classes = 'ftc-service-detail-image ftc-service-detail-visual'.($use_webgl ? ' ftc-service-detail-webgl' : '');
    echo '<div class="'.esc_attr($visual_classes).'">';
    if($hero_image){
        echo '<img src="'.esc_url($hero_image).'" alt="" loading="lazy" decoding="async" class="ftc-service-detail-hero-img" />';
    } elseif($use_webgl){
        ftc_render_service_webgl_visual($title, true);
    }
    echo '</div>';
    if($visual_key === 'innovation' || $visual_key === 'ai'){
        ftc_render_ai_toolkit_section();
    }
    echo '</div><div class="ftc-child-grid">';
    $focus_key = ftc_normalize_prompt_text($focus_label);
    $cat_index = 0;
    foreach(ftc_service_task_groups($id) as $label=>$items){
        $cat_index++;
        $is_group_focus = $focus_key && ftc_normalize_prompt_text($label) === $focus_key;
        $article_classes = array_filter([
            'ftc-child-category-card',
            ftc_child_category_accent_class($cat_index),
            $is_group_focus ? 'is-focused-child-service' : '',
        ]);
        echo '<article class="'.esc_attr(implode(' ', $article_classes)).'" data-ftc-child-category="'.esc_attr(ftc_child_category_slug($label)).'"><h4>'.esc_html($label).'</h4><p>Practical capabilities we can plan, build, manage, measure, and improve.</p><ul>';
        foreach($items as $t){
            $is_focus = $focus_key && (ftc_normalize_prompt_text($t) === $focus_key || strpos(ftc_normalize_prompt_text($t), $focus_key) !== false);
            echo '<li'.($is_focus ? ' class="is-focused-child-service"' : '').'><button type="button" class="ftc-child-service-link" data-prompt="'.esc_attr($t).'"><span>'.esc_html(ftc_service_task_display_label($t)).'</span></button></li>';
        }
        echo '</ul></article>';
    }
    echo '</div><div class="ftc-response-actions ftc-service-detail-actions"><div class="ftc-response-actions-left">';
    ftc_render_revert_action_button();
    echo '<button class="ftc-back-button" type="button" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div></div>';
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
    $items=ftc_get_portfolio_sequence_items(6);
    if(!$items){ return; }
    echo '<div class="ftc-portfolio-panel ftc-portfolio-grid-panel">';
    echo '<section class="ftc-portfolio-section-one" data-ftc-section-one aria-label="Featured portfolio projects">';
    echo '<div class="ftc-services-category-grid ftc-portfolio-category-grid">';
    foreach($items as $item) ftc_render_portfolio_sequence_card($item, false);
    echo '</div>';
    echo '<div class="ftc-portfolio-view-all"><button type="button" class="ftc-blue-outline-btn" data-prompt="Show Me All Portfolios">View All Projects</button></div>';
    echo '</section></div>';
}

function ftc_render_portfolio_all_content(){
    $items=ftc_get_portfolio_sequence_items(24);
    if(!$items){ return; }
    echo '<div class="ftc-portfolio-panel ftc-portfolio-grid-panel">';
    echo '<section class="ftc-portfolio-all-content" aria-label="All portfolio projects"><div class="ftc-services-category-grid ftc-portfolio-category-grid">';
    foreach($items as $item) ftc_render_portfolio_sequence_card($item, false);
    echo '</div></section></div>';
}

function ftc_render_portfolio_grid($limit=6){
    $items=ftc_get_portfolio_sequence_items($limit);
    if(!$items){ return; }
    echo '<div class="ftc-services-category-grid ftc-portfolio-category-grid">';
    foreach($items as $item) ftc_render_portfolio_sequence_card($item, false);
    echo '</div>';
}

function ftc_render_portfolio_masonry($limit=-1){
    $count = ($limit > 0) ? $limit : 6;
    echo '<div class="ftc-portfolio-panel ftc-portfolio-grid-panel">';
    ftc_render_portfolio_grid($count);
    echo '</div>';
}
function ftc_is_placeholder_image_url($url){
    $url = strtolower((string)$url);
    if($url === '') return false;
    return strpos($url,'placeholder-gray') !== false
        || strpos($url,'placeholder-portfolio') !== false
        || strpos($url,'placeholder-service') !== false
        || strpos($url,'placehold.co') !== false
        || strpos($url,'temp image') !== false;
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
function ftc_portfolio_card_image_url($post_id){
    $allow_placeholder = get_post_meta($post_id,'_ftc_allow_placeholder_image',true) === '1';
    if(has_post_thumbnail($post_id)){
        $thumb = get_the_post_thumbnail_url($post_id,'large');
        if($thumb && ($allow_placeholder || !ftc_is_placeholder_image_url($thumb))) return $thumb;
    }
    $gallery = ftc_portfolio_gallery_image_urls($post_id,'large',$allow_placeholder);
    return $gallery[0] ?? '';
}
function ftc_render_portfolio_card_markup($id, $featured=false){
    $title = get_the_title($id);
    $industry = trim((string)get_post_meta($id, '_ftc_industry', true));
    $eyebrow = $industry !== '' ? strtoupper($industry) : 'PROJECT';
    $desc = get_the_excerpt($id) ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $id)), 28);
    $image = ftc_portfolio_card_image_url($id);
    if(!$image) $image = FTC_URL . 'assets/images/placeholder-gray-16x9.svg';
    ftc_render_portfolio_card_inner($title, $eyebrow, $desc, $image, 'data-ftc-project="'.esc_attr($id).'"', $featured);
}

function ftc_render_demo_portfolio_card_markup($demo, $featured=false){
    $eyebrow = !empty($demo['industry']) ? strtoupper((string)$demo['industry']) : 'PROJECT';
    $title = (string)($demo['title'] ?? 'Project');
    $desc = (string)($demo['description'] ?? '');
    $image = !empty($demo['image']) ? $demo['image'] : (FTC_URL . 'assets/images/placeholder-gray-16x9.svg');
    ftc_render_portfolio_card_inner($title, $eyebrow, $desc, $image, 'data-prompt="Show me your work!"', $featured);
}

function ftc_render_portfolio_card_inner($title, $eyebrow, $desc, $image, $button_attrs, $featured=false){
    $classes = 'ftc-work-card ftc-portfolio-card';
    if($featured) $classes .= ' ftc-work-featured';
    echo '<article class="'.esc_attr($classes).'"><button type="button" '.$button_attrs.'>';
    echo '<img src="'.esc_url($image).'" alt="'.esc_attr($title).'" loading="lazy" decoding="async" />';
    echo '<div class="ftc-work-info"><span>'.esc_html($eyebrow).'</span>';
    echo '<h3>'.esc_html($title).'</h3>';
    echo '<p>'.esc_html($desc).'</p>';
    echo '<em>View project &rarr;</em></div></button></article>';
}

function ftc_portfolio_card($id, $featured=false){
    ftc_render_portfolio_card_markup($id, $featured);
}

function ftc_demo_portfolio_card($demo, $featured=false){
    ftc_render_demo_portfolio_card_markup($demo, $featured);
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
function ftc_render_testimonials_panel(){
    $q = new WP_Query([
        'post_type'=>'ftc_testimonial',
        'post_status'=>'publish',
        'posts_per_page'=>6,
        'orderby'=>'menu_order title',
        'order'=>'ASC',
        'meta_query'=>[
            'relation'=>'OR',
            [
                'key'=>'_ftc_featured',
                'value'=>'1',
            ],
            [
                'key'=>'_ftc_featured',
                'compare'=>'NOT EXISTS',
            ],
        ],
    ]);
    echo '<div class="ftc-testimonial-grid">';
    if($q->have_posts()){
        while($q->have_posts()){
            $q->the_post();
            $role = get_post_meta(get_the_ID(),'_ftc_testimonial_role',true);
            $company = get_post_meta(get_the_ID(),'_ftc_testimonial_company',true);
            $caption = trim($role ?: get_the_title());
            if($company) $caption .= ' / '.$company;
            $quote = wp_strip_all_tags(get_the_content());
            if($quote === '') $quote = get_the_excerpt();
            echo '<figure><blockquote>'.esc_html($quote).'</blockquote><figcaption>'.esc_html($caption).'</figcaption></figure>';
        }
        wp_reset_postdata();
    } else {
        echo '<figure><blockquote>A clearer website, cleaner message, and a team that understood the business problem before touching the design.</blockquote><figcaption>Healthcare client</figcaption></figure>';
        echo '<figure><blockquote>Field Theory brought strategy, design, development, and analytics together without making the process feel heavy.</blockquote><figcaption>Nonprofit partner</figcaption></figure>';
        echo '<figure><blockquote>The reporting finally made sense. We could see what was working and what to do next.</blockquote><figcaption>Marketing director</figcaption></figure>';
    }
    echo '</div>';
}
function ftc_contact_card_buttons($items, $selected=''){
    foreach($items as $item){
        $is_selected = $selected && $item === $selected;
        echo '<button type="button" class="ftc-quiz-card'.($is_selected ? ' is-selected' : '').'" data-ftc-choice data-value="'.esc_attr($item).'" aria-pressed="'.($is_selected ? 'true' : 'false').'"><span>'.esc_html($item).'</span></button>';
    }
}

function ftc_phone_link_value($phone){
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if($digits === '') return '';
    if(strlen($digits) === 10) $digits = '1'.$digits;
    return '+'.$digits;
}

function ftc_render_contact_panel($settings, $opts = []){
    $quiz_only = !empty($opts['quiz_only']);
    $email = $settings['contact_email'] ?: 'jamie@fieldtheory.ai';
    $phone = trim((string)($settings['contact_phone'] ?? ''));
    if($phone === '') $phone = '(505) 456-3193';
    $phone_link = ftc_phone_link_value($phone);

    if($quiz_only){
        echo '<div class="ftc-contact-quiz ftc-go-time-closing-quiz" data-ftc-contact-quiz>';
    } else {
        echo '<div class="ftc-contact-onboarding ftc-two-column-response">';
        echo '<aside class="ftc-contact-aside" aria-label="Contact Field Theory Lab">';
        echo '<div class="ftc-contact-aside-card ftc-contact-aside-primary">';
        echo '<h3>Free Consultation</h3>';
        echo '<p>Talk with Jamie for a no-cost strategy call. We will make strategic recommendations and a proposal to complete the work—whether you work with us or not.</p>';
        $jamie_photo = esc_url(FTC_URL . 'assets/images/team-jamie.jpg');
        echo '<div class="ftc-contact-person ftc-contact-person-compact">';
        echo '<div class="ftc-contact-person-photo"><img src="'.$jamie_photo.'" alt="Jamie Rushad Gros" width="88" height="88" loading="lazy"></div>';
        echo '<div class="ftc-contact-person-copy">';
        echo '<h4 class="ftc-contact-person-name">Jamie Rushad Gros</h4>';
        echo '<p class="ftc-contact-aside-location">Founder, Field Theory Lab · Albuquerque, NM</p>';
        echo '</div></div>';
        echo '<div class="ftc-contact-direct-actions ftc-contact-direct-stack">';
        if($phone_link){
            echo '<a class="ftc-contact-direct ftc-contact-call" href="tel:'.esc_attr($phone_link).'">Call '.esc_html($phone).'</a>';
            echo '<a class="ftc-contact-direct ftc-contact-text" href="sms:'.esc_attr($phone_link).'">Text '.esc_html($phone).'</a>';
        }
        echo '<a class="ftc-contact-direct ftc-contact-email-link" href="mailto:'.esc_attr($email).'">Email '.esc_html($email).'</a>';
        echo '</div></div>';
        echo '</aside>';
        echo '<div class="ftc-contact-quiz" data-ftc-contact-quiz>';
        echo '<header class="ftc-contact-form-intro">';
        echo '<h2>Work With Us</h2>';
        echo '<p>What should we improve? The form is short—or reach out directly.</p>';
        echo '</header>';
    }
    echo '<div class="ftc-quiz-progress"><span data-ftc-quiz-progress-text>Step 1</span><div><i data-ftc-quiz-progress-bar></i></div></div>';
    echo '<div class="ftc-quiz-error" data-ftc-quiz-error role="alert" aria-live="polite"></div>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="services" data-multi><h4>What would you like to improve?</h4><p>Select everything that sounds relevant—we will tailor our reply.</p><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Website Development & Core Tech','Digital Marketing & Growth Strategy','Search & Discovery Optimization','Ecommerce & Conversion','Technology, Innovation and A.I.','Not Sure Yet']);
    echo '</div><button type="button" class="ftc-quiz-next" data-ftc-next>Continue</button></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step><h4>What should we know?</h4><div class="ftc-quiz-fields"><label>Company Name<input type="text" data-ftc-input="company" required autocomplete="organization"></label><label>Website URL<input type="url" data-ftc-input="website" placeholder="https://"></label></div><label class="ftc-quiz-textarea">Project notes<textarea data-ftc-input="notes" rows="4" placeholder="Goals, timing, problems to solve, or anything helpful."></textarea></label><button type="button" class="ftc-quiz-next" data-ftc-next>Continue</button></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="timeline"><h4>How soon are you looking to get started?</h4><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Just Exploring','Within 6 Months','Within 90 Days','Within 30 Days','Immediately']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="budget"><h4>What budget range best fits your project?</h4><p>To help us recommend the right solution...</p><div class="ftc-quiz-card-grid">';
    ftc_contact_card_buttons(['Under $5,000','$5,000-$15,000','$15,000-$50,000','$50,000+','Not Sure Yet']);
    echo '</div></section>';

    echo '<section class="ftc-quiz-step" data-ftc-quiz-step data-field="contactMethod"><h4>How should we reach you?</h4><div class="ftc-quiz-fields"><label>Name<input type="text" data-ftc-input="name" required autocomplete="name"></label><label>Email<input type="email" data-ftc-input="email" required autocomplete="email"></label><label>Phone<input type="tel" data-ftc-input="phone" autocomplete="tel"></label></div><p>Preferred contact method</p><div class="ftc-quiz-card-grid ftc-quiz-card-grid-small">';
    ftc_contact_card_buttons(['Email','Phone','Text Message'], 'Email');
    echo '</div><button type="button" class="ftc-quiz-next" data-ftc-next>Review</button></section>';

    echo '<section class="ftc-quiz-step ftc-quiz-complete" data-ftc-quiz-step><h4>Review your request.</h4><p>Here is what Field Theory will receive.</p><div class="ftc-quiz-summary" data-ftc-submission-summary></div><input type="text" data-ftc-hp tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;pointer-events:none" /><div class="ftc-quiz-actions"><button type="button" class="ftc-quiz-submit" data-ftc-submit-inquiry>Submit Proposal Request</button></div><div class="ftc-quiz-submit-status" data-ftc-submit-status aria-live="polite"></div><p class="ftc-quiz-privacy-note">By submitting, you agree that Field Theory Lab may store this inquiry to follow up on your request. <button type="button" data-prompt="Privacy Policy">View Privacy Policy</button></p></section>';
    echo '</div>';
    if(!$quiz_only) echo '</div>';
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
function ftc_is_youtube_embed_url($url){
    return $url && strpos((string)$url, 'youtube.com/embed') !== false;
}
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
    $video_url = get_post_meta($post_id,'_ftc_video_url',true);
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
        $cols = [[], [], []];
        foreach($imgs as $i => $u) $cols[$i % 3][] = $u;
        echo '<div class="ftc-project-gallery">';
        foreach($cols as $col){
            if(empty($col)) continue;
            echo '<div class="ftc-gallery-col">';
            foreach($col as $u){
                echo '<figure><img src="'.esc_url($u).'" alt="'.esc_attr(get_the_title($post_id)).'" loading="lazy"></figure>';
            }
            echo '</div>';
        }
        echo '</div>';
    }
    if($video_url && ftc_is_youtube_embed_url($video_url)){
        echo '<div class="ftc-project-video">';
        echo '<div class="ftc-video-responsive">';
        echo '<iframe src="'.esc_url($video_url).'" title="'.esc_attr(get_the_title($post_id)).' video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
        echo '</div>';
        echo '</div>';
    }
    echo '<div class="ftc-response-actions ftc-project-actions"><div class="ftc-response-actions-left">';
    ftc_render_revert_action_button();
    echo '<button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Show me your work!" data-prompt="Show me your work!">Back to Portfolio</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div>';
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
        echo '<div class="ftc-response-actions ftc-project-actions"><div class="ftc-response-actions-left">';
        ftc_render_revert_action_button();
        echo '<button type="button" class="ftc-back-button" data-ftc-reset-to-prompt="Show me your work!" data-prompt="Show me your work!">Back to Portfolio</button><button type="button" class="ftc-blue-outline-btn" data-ftc-reset-to-prompt="Our Services" data-prompt="Our Services">Back to Services</button></div><button class="ftc-green-btn ftc-request-proposal-action" type="button" data-prompt="Request a Proposal">Request a Proposal</button></div>';
        echo '</div>';
        wp_send_json_success(['html'=>ob_get_clean()]);
    }

    ftc_render_project_detail_markup($post_id);
    wp_send_json_success(['html'=>ob_get_clean()]);
}
add_action('wp_ajax_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
add_action('wp_ajax_nopriv_ftc_portfolio_detail','ftc_ajax_portfolio_detail');
