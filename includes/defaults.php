<?php
if (!defined('ABSPATH')) exit;

function ftc_default_settings(){
    return [
        'dark_logo' => 'https://fieldtheory.ai/wp-content/uploads/2026/06/FieldTheoryLogo_White_shirt.svg',
        'light_logo' => 'https://fieldtheory.ai/wp-content/uploads/2026/06/FieldTheoryLab_ColorStacked.svg',
        'icon_logo' => 'https://fieldtheory.ai/wp-content/uploads/2026/06/FTLIcon.svg',
        'tagline' => 'Turn Data Into Growth',
        'descriptor' => 'Web Technology • Analytics • AI Automation • Digital Marketing',
        'name_prompt' => 'What should I call you?',
        'input_placeholder' => 'Ask Field Theory anything...',
        'demo_video_url' => FTC_URL . 'assets/video/MobileDesign_FTL_2026.mp4',
        'contact_email' => 'hello@fieldtheory.ai',
        'contact_phone' => '',
        'contact_url' => 'https://fieldtheory.ai/contact/',
        'calendly_url' => '',
    ];
}
function ftc_get_settings(){ return wp_parse_args((array)get_option('ftc_settings', []), ftc_default_settings()); }

function ftc_default_responses(){
    return [
        'about' => [
            'title' => 'About Field Theory',
            'html' => '<p>Field Theory Lab is a web technology and digital marketing company based in Albuquerque, New Mexico. We help organizations make sense of complex digital systems and turn data into growth.</p><div class="ftc-feature-grid"><div><strong>Strategy</strong><span>Clear digital roadmaps.</span></div><div><strong>Technology</strong><span>Websites, tools, and integrations.</span></div><div><strong>Analytics</strong><span>Better visibility into performance.</span></div><div><strong>AI</strong><span>Practical automation and insight systems.</span></div></div>',
            'layout' => 'services',
            'followups' => ['Show me your work','What services do you offer?','Can you help with analytics?']
        ],
        'portfolio' => [
            'title' => 'Our Work',
            'html' => '<p>Absolutely. Here are examples of the kind of visual, strategic, and technical work Field Theory creates for organizations across education, healthcare, public sector, nonprofits, utilities, and growth-focused brands.</p><p>These projects represent website design, UX, digital strategy, analytics, and complex content systems.</p>',
            'layout' => 'portfolio',
            'followups' => ['Do you build websites?','Can you help my company?','How can we work together?']
        ],
        'services' => [
            'title' => 'How We Help',
            'html' => '<p><strong>We help businesses connect their website, marketing, analytics, and digital tools into one clearer growth system.</strong></p><p>Most clients come to us because something feels disconnected: the site is not explaining the business well, marketing is not measurable enough, analytics are messy, or AI feels interesting but hard to turn into something practical.</p><p>Field Theory brings strategy, UX, development, analytics, content, SEO, and automation together so your digital presence becomes easier to understand, easier to improve, and easier to measure.</p><div class="ftc-feature-grid"><div><strong>Clarify</strong><span>Audit the website, analytics, marketing, and customer journey.</span></div><div><strong>Build</strong><span>Create better websites, landing pages, dashboards, and digital tools.</span></div><div><strong>Measure</strong><span>Set up GA4, reporting, KPIs, and decision-ready dashboards.</span></div><div><strong>Grow</strong><span>Improve visibility, conversion, campaigns, and AI-supported workflows.</span></div></div>',
            'layout' => 'services',
            'followups' => ['Show me your work','Tell me about AI automation','Help me understand my data']
        ],
        'websites' => [
            'title' => 'Websites & UX',
            'html' => '<p>Yes. We design and build websites that help organizations communicate clearly, improve user experience, and drive measurable outcomes.</p><ul><li>UX strategy and information architecture</li><li>WordPress, Drupal, and custom frontend development</li><li>Accessibility, performance, and conversion optimization</li><li>Analytics and tracking built in from the start</li></ul>',
            'layout' => 'portfolio',
            'followups' => ['Show me your work','What is your web process?','Can you improve our current site?']
        ],
        'analytics' => [
            'title' => 'Analytics & Data',
            'html' => '<p>We help organizations understand what their digital data is actually saying. That can include analytics audits, dashboards, campaign reporting, conversion tracking, and decision-ready executive summaries.</p><ul><li>GA4 and tag management</li><li>Looker Studio dashboards</li><li>Campaign and conversion reporting</li><li>Data storytelling for teams and leadership</li></ul>',
            'layout' => 'services',
            'followups' => ['What dashboards can you build?','How do you measure marketing?','Tell me about AI']
        ],
        'ai' => [
            'title' => 'AI Automation',
            'html' => '<p>We offer AI services, implementations, and automation strategy. We also use AI as part of our analytics and digital strategy workflow, but AI is not the whole business. It is one of the tools we use to help people work smarter.</p><ul><li>AI assistants and internal knowledge systems</li><li>Workflow automation and reporting support</li><li>Lead qualification and marketing operations</li><li>Practical adoption planning for teams</li></ul>',
            'layout' => 'services',
            'followups' => ['Can AI help my business?','Show me your work','How can we work together?']
        ],
        'marketing' => [
            'title' => 'Digital Marketing',
            'html' => '<p>We help brands connect strategy, content, search, campaigns, measurement, and customer experience. The goal is not more activity. The goal is clearer decisions and measurable growth.</p><ul><li>SEO and AI visibility</li><li>Content strategy and campaign planning</li><li>Conversion optimization</li><li>Reporting and marketing analytics</li></ul>',
            'layout' => 'services',
            'followups' => ['SEO + AI visibility?','Help me understand my data','Show me your work']
        ],
        'contact' => [
            'title' => 'Contact Field Theory',
            'html' => '<p>Ready to talk? Tell us what you are trying to solve, and we will help you figure out the clearest path forward.</p>',
            'layout' => 'contact',
            'followups' => ['Show me your work','What services do you offer?','Tell me about Field Theory']
        ],
        'fallback' => [
            'title' => 'Good Question',
            'html' => '<p>I can help explain Field Theory Lab, our services, our work, digital marketing, analytics, AI automation, web technology, and how to contact the team.</p><p>Try asking something like <em>Do you build websites?</em>, <em>Show me your work</em>, or <em>Can you help with analytics?</em></p>',
            'layout' => 'none',
            'followups' => ['Tell me about your company','Show me your work','How can I work with Field Theory?']
        ],
    ];
}
function ftc_get_responses(){ return wp_parse_args((array)get_option('ftc_responses', []), ftc_default_responses()); }

function ftc_get_demo_portfolio(){
    $base = FTC_URL . 'assets/images/';
    return [
        ['title'=>'The Education Plan','industry'=>'Education','description'=>'A clear digital experience for helping families understand and plan for education savings.','image'=>$base.'LogoTEP.jpg','tags'=>['Web','UX','Education']],
        ['title'=>'BeWell NM','industry'=>'Healthcare','description'=>'Mobile-focused health insurance marketplace experience and campaign support.','image'=>$base.'BeWellNM_Mobile.jpg','tags'=>['Healthcare','Mobile','UX']],
        ['title'=>'Let’s Plant','industry'=>'Environment','description'=>'A friendly, action-oriented mobile experience for environmental engagement.','image'=>$base.'LetsPlantMobile.jpg','tags'=>['Mobile','Campaign']],
        ['title'=>'Aztec Mechanical','industry'=>'B2B','description'=>'Website presentation for a technical services company with a strong industrial identity.','image'=>$base.'AztecMechanical_Website.png','tags'=>['Web','B2B']],
        ['title'=>'MySchoolsABQ','industry'=>'Education','description'=>'A public-facing education platform designed to make school choice information easier to understand.','image'=>$base.'MySchoolsAQBDesktop.jpg','tags'=>['Education','Data','UX']],
        ['title'=>'NMEDD','industry'=>'Government','description'=>'Economic development website experience supporting statewide business growth and navigation.','image'=>$base.'NMEDD_Website.jpg','tags'=>['Government','Web']],
        ['title'=>'Amy Biehl High School','industry'=>'Education','description'=>'School website design system with responsive layouts and clear student-centered content.','image'=>$base.'AmyBiehlHighMockups.jpg','tags'=>['Education','Web']],
        ['title'=>'Heading Home','industry'=>'Nonprofit','description'=>'Website and messaging support for a nonprofit focused on homelessness and community services.','image'=>$base.'HeadingHome.jpg','tags'=>['Nonprofit','Web']],
        ['title'=>'PNM','industry'=>'Utilities','description'=>'Utility-focused digital experience and content presentation work.','image'=>$base.'PNM_Website3.jpg','tags'=>['Utility','Web']],
        ['title'=>'OMNI CRE','industry'=>'Real Estate','description'=>'Commercial real estate digital presentation with strong visual identity and property-focused UX.','image'=>$base.'OMNICRE_Desktop_Mockup.jpg','tags'=>['Real Estate','Web']],
    ];
}
