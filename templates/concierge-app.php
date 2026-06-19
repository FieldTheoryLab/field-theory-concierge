<?php
if (!defined('ABSPATH')) exit;
$s = ftc_get_settings();
$route = function_exists('ftc_get_route_data') ? ftc_get_route_data() : null;
$route_html = $route && function_exists('ftc_render_route_initial_response') ? ftc_render_route_initial_response($route) : '';
$is_route = $route && $route_html;
$route_label = $route['prompt'] ?? ($route['title'] ?? 'Field Theory response');
$route_title = $route['title'] ?? 'Field Theory Lab';
$input_id = 'ftc-chat-input-' . wp_unique_id();
?>
<a class="ftc-skip-link" href="#ftc-main-stage">Skip to content</a>
<div class="ftc-app<?php echo $is_route ? ' is-chat ftc-route-app' : ''; ?>" data-ftc-app data-theme="dark">
  <div class="ftc-bg" aria-hidden="true"></div>
  <header class="ftc-header" data-ftc-header>
    <button class="ftc-logo-btn" type="button" data-ftc-reset aria-label="Reset Field Theory Concierge">
      <img class="ftc-logo ftc-logo-full" src="<?php echo esc_url($s['dark_logo']); ?>" alt="Field Theory Lab">
      <img class="ftc-logo-icon" src="<?php echo esc_url($s['icon_logo']); ?>" alt="Field Theory Lab icon">
    </button>
    <div class="ftc-header-actions">
      <button class="ftc-hire-btn" type="button" data-prompt="Request a Proposal">Hire Our Team</button>
      <button class="ftc-action ftc-menu-action" type="button" data-ftc-menu aria-label="Explore" aria-haspopup="dialog" aria-controls="ftc-main-menu">+</button>
    </div>
  </header>
  <main class="ftc-main" id="ftc-main-stage">
    <?php if($is_route): ?>
      <h1 class="ftc-sr-only"><?php echo esc_html($route_title); ?></h1>
    <?php endif; ?>
    <section class="ftc-stage ftc-stage-intro<?php echo $is_route ? '' : ' is-visible'; ?>" data-ftc-intro aria-label="Field Theory introduction">
      <div class="ftc-intro-content">
        <h1 data-ftc-intro-heading><?php echo esc_html($s['tagline'] ?? ''); ?></h1>
        <p data-ftc-intro-body>We're a creative technology agency, helping organizations with website, digital marketing, data analysis and A.I.</p>
        <div class="ftc-intro-question"><?php echo esc_html($s['name_prompt'] ?? 'How may we help you today?'); ?></div>
        <div class="ftc-dock-prompts" aria-label="Quick questions">
          <button type="button" data-prompt="Get Started">Get Started.</button>
          <button type="button" data-prompt="Tell me about your company">Tell me about your company</button>
          <button type="button" data-prompt="Show me your work!">Show me your work!</button>
          <button type="button" data-prompt="How can you help my company?">How can you help my company?</button>
          <button type="button" data-prompt="UX + Web development?">UX + Web development?</button>
          <button type="button" data-prompt="Web Design and Digital Marketing">Web Design and Digital Marketing</button>
          <button type="button" data-prompt="How can I work with Field Theory?">How can I work with Field Theory?</button>
          <button type="button" data-prompt="Help me understand my website and marketing data">Help me understand my website and marketing data</button>
          <button type="button" data-prompt="A.I. and SEO">A.I. and SEO</button>
        </div>
      </div>
    </section>
    <section class="ftc-stage ftc-stage-chat<?php echo $is_route ? ' is-visible' : ''; ?>" data-ftc-chat aria-label="Field Theory response">
      <div class="ftc-chat-stream" data-ftc-stream tabindex="-1" aria-live="polite" aria-relevant="additions text" aria-busy="false">
        <?php if($is_route): ?>
          <article class="ftc-message ftc-assistant ftc-server-rendered has-arrived" data-ftc-prompt-label="<?php echo esc_attr($route_label); ?>">
            <div class="ftc-card"><?php echo $route_html; ?></div>
          </article>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <div class="ftc-prompt-dock" data-ftc-prompt-dock>
    <button class="ftc-help-dot" type="button" data-ftc-help-menu aria-label="Open helpful prompts" aria-haspopup="dialog" aria-controls="ftc-help-menu">?</button>
    <form class="ftc-chat-form ftc-bottom-search" data-ftc-chat-form role="search">
      <div class="ftc-input-shell"><label class="ftc-sr-only" for="<?php echo esc_attr($input_id); ?>">Ask Field Theory Lab</label><input id="<?php echo esc_attr($input_id); ?>" type="text" data-ftc-chat-input placeholder="<?php echo esc_attr($s['input_placeholder']); ?>" autocomplete="off" aria-label="Ask Field Theory Lab"><button class="ftc-clear" type="button" data-ftc-clear aria-label="Clear conversation">&times;</button><button class="ftc-send" type="submit" aria-label="Send message">CHAT</button></div>
    </form>
    <div class="ftc-site-byline">Field Theory Lab, Albuquerque, NM. All Rights Reserved. <button type="button" data-prompt="Privacy Policy">Privacy Policy</button></div>
  </div>
  <div class="ftc-modal" id="ftc-main-menu" data-ftc-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ftc-menu-title"><button class="ftc-modal-bg" type="button" data-ftc-close aria-label="Close menu"></button><aside class="ftc-modal-panel"><button class="ftc-modal-close" type="button" data-ftc-close aria-label="Close menu">&times;</button><h2 id="ftc-menu-title">Explore Field Theory</h2><div data-ftc-menu-content><div class="ftc-loader" role="status" aria-label="Loading menu"></div></div></aside></div>
  <div class="ftc-help-modal" id="ftc-help-menu" data-ftc-help-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ftc-help-title">
    <button class="ftc-modal-bg" type="button" data-ftc-help-close aria-label="Close"></button>
    <aside class="ftc-modal-panel ftc-help-panel">
      <button class="ftc-modal-close" type="button" data-ftc-help-close aria-label="Close">&times;</button>
      <h2 id="ftc-help-title">Helpful Prompts</h2>
      <p>Try a question, topic, or shortcut.</p>
      <div class="ftc-help-cloud" aria-label="Helpful prompt shortcuts">
        <?php foreach([
          'Get Started',
          'Show me your work!',
          'Show Me All Portfolios',
          'Our Services',
          'Website Development & Core Tech',
          'Digital Marketing & Growth Strategy',
          'Search & Discovery Optimization',
          'Data, Analysis & Visualization',
          'Technology, Innovation and A.I.',
          'Ecommerce & Conversion',
          'SEO / AEO',
          'AI & Automation',
          'Analytics & Dashboards',
          'WordPress',
          'Drupal',
          'Hosting & Maintenance',
          'ADA Accessibility',
          'Can you improve our current site?',
          'How long does a website take?',
          'What budget should I plan for?',
          'Do you work with nonprofits?',
          'Do you work with government?',
          'Do you build ecommerce?',
          'How do you measure marketing?',
          'Testimonials',
          'Request a Proposal',
        ] as $prompt): ?>
          <button type="button" data-prompt="<?php echo esc_attr($prompt); ?>"><?php echo esc_html($prompt); ?></button>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div>
