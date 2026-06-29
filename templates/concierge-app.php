<?php
if (!defined('ABSPATH')) exit;
$s = ftc_get_settings();
$route = function_exists('ftc_get_route_data') ? ftc_get_route_data() : null;
$route_html = $route && function_exists('ftc_render_route_initial_response') ? ftc_render_route_initial_response($route) : '';
$is_route = $route && $route_html;
$is_go_time_route = $is_route && (($route['prompt'] ?? '') === 'Go Time');
$is_get_started_route = $is_route && (($route['prompt'] ?? '') === 'Get Started');
$route_label = $route['prompt'] ?? ($route['title'] ?? 'Field Theory response');
$route_title = $route['title'] ?? 'Field Theory Lab';
$input_id = 'ftc-chat-input-' . wp_unique_id();
?>
<a class="ftc-skip-link" href="#ftc-main-stage">Skip to content</a>
<div class="ftc-app<?php echo $is_route ? ' is-chat ftc-route-app' : ''; ?><?php echo $is_go_time_route ? ' ftc-app-is-go-time' : ''; ?>" data-ftc-app data-theme="dark" data-ftc-bg-tone="<?php echo $is_route ? 'default' : 'intro'; ?>"<?php echo $is_go_time_route ? ' data-ftc-route="go-time"' : ''; ?><?php echo $is_get_started_route ? ' data-ftc-route="get-started"' : ''; ?>>
  <div class="ftc-bg" aria-hidden="true"></div>
  <header class="ftc-header" data-ftc-header>
    <a class="ftc-logo-btn" href="/" data-ftc-reset aria-label="Go to Field Theory Lab homepage">
      <img class="ftc-logo ftc-logo-full" src="<?php echo esc_url($s['dark_logo']); ?>" alt="Field Theory Lab">
      <img class="ftc-logo-icon" src="<?php echo esc_url($s['icon_logo']); ?>" alt="Field Theory Lab icon">
    </a>
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
        <h1 data-ftc-intro-heading class="ftc-intro-heading-fade"><?php echo esc_html($s['tagline'] ?? ''); ?></h1>
        <p data-ftc-intro-body><?php echo esc_html($s['intro_body'] ?? ''); ?></p>
        <div class="ftc-intro-question"><?php echo esc_html($s['name_prompt'] ?? 'How may we help you today?'); ?></div>
        <div class="ftc-dock-prompts" aria-label="Quick questions">
          <?php
          $home_prompts = array_values(array_unique([
            'Get Started.',
            'Tell me about your company',
            'Show me your work!',
            'What services do you provide?',
            'Website Development & Core Tech',
            'Digital Marketing & Growth Strategy',
            'Search & Discovery Optimization',
            'Data, Analysis & Visualization',
            'Technology, Innovation and A.I.',
            'Ecommerce & Conversion',
            'SEO / AEO',
            'AI & Automation',
            'ADA Accessibility',
            'I need a website',
            'Do you work in Santa Fe',
            'Can you help with AI search',
            'My website is not generating leads',
            'Request a Proposal',
          ]));
          foreach($home_prompts as $prompt):
          ?>
            <?php if($prompt === 'Get Started.'): ?>
              <button type="button" data-redirect="/get-started/"><?php echo esc_html($prompt); ?></button>
            <?php elseif($prompt === 'What services do you provide?'): ?>
              <button type="button" data-redirect="/services/"><?php echo esc_html($prompt); ?></button>
            <?php else: ?>
              <button type="button" data-prompt="<?php echo esc_attr($prompt); ?>"><?php echo esc_html($prompt); ?></button>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <section class="ftc-stage ftc-stage-chat<?php echo $is_route ? ' is-visible' : ''; ?>" data-ftc-chat aria-label="Field Theory response">
      <div class="ftc-chat-stream" data-ftc-stream tabindex="-1" aria-live="polite" aria-relevant="additions text" aria-busy="false">
        <?php if($is_route): ?>
          <?php if($is_go_time_route): ?>
          <article class="ftc-message ftc-assistant ftc-server-rendered has-arrived has-layout-go-time ftc-go-time-fullpage" data-ftc-prompt-label="<?php echo esc_attr($route_label); ?>">
            <?php echo $route_html; ?>
          </article>
          <?php else: ?>
          <article class="ftc-message ftc-assistant ftc-server-rendered has-arrived<?php echo (($route['type'] ?? '') === 'service') ? ' has-layout-service-detail' : ''; ?><?php echo $is_go_time_route ? ' has-layout-go-time' : ''; ?>" data-ftc-prompt-label="<?php echo esc_attr($route_label); ?>">
            <div class="ftc-card"><?php echo $route_html; ?></div>
          </article>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>
  <div class="ftc-prompt-dock" data-ftc-prompt-dock>
    <div class="ftc-dock-controls">
      <div class="ftc-dock-icons">
        <button class="ftc-help-dot" type="button" data-ftc-help-menu aria-label="Open helpful prompts" aria-haspopup="dialog" aria-controls="ftc-help-menu">?</button>
        <button class="ftc-search-dot" type="button" data-ftc-search-toggle aria-label="Open search" aria-expanded="false" aria-controls="ftc-dock-search">
          <svg class="ftc-search-dot-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.5"></circle><path d="M20 20l-4.5-4.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"></path></svg>
          <span class="ftc-search-dot-close" aria-hidden="true">&times;</span>
        </button>
      </div>
      <form class="ftc-chat-form ftc-bottom-search" id="ftc-dock-search" data-ftc-chat-form role="search">
        <div class="ftc-input-shell"><label class="ftc-sr-only" for="<?php echo esc_attr($input_id); ?>">Ask Field Theory Lab</label><button class="ftc-search-close" type="button" data-ftc-search-close aria-label="Close search">&times;</button><input id="<?php echo esc_attr($input_id); ?>" type="text" data-ftc-chat-input placeholder="<?php echo esc_attr($s['input_placeholder']); ?>" autocomplete="off" aria-label="Ask Field Theory Lab"><button class="ftc-clear" type="button" data-ftc-clear aria-label="Clear conversation">&times;</button><button class="ftc-send" type="submit" aria-label="Send message">CHAT</button></div>
      </form>
    </div>
    <div class="ftc-site-byline">Field Theory Lab, Albuquerque, NM. All Rights Reserved. <button type="button" data-prompt="Privacy Policy">Privacy Policy</button></div>
  </div>
  <div class="ftc-modal" id="ftc-main-menu" data-ftc-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ftc-menu-title"><button class="ftc-modal-bg" type="button" data-ftc-close aria-label="Close menu"></button><aside class="ftc-modal-panel"><button class="ftc-modal-close" type="button" data-ftc-close aria-label="Close menu">&times;</button><h2 id="ftc-menu-title" class="ftc-brand-headline"><span class="ftc-menu-title-line">Explore</span><span class="ftc-menu-title-line">Field Theory</span></h2><div data-ftc-menu-content><div class="ftc-loader" role="status" aria-label="Loading menu"></div></div></aside></div>
  <div class="ftc-help-modal" id="ftc-help-menu" data-ftc-help-modal aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ftc-help-title">
    <button class="ftc-modal-bg" type="button" data-ftc-help-close aria-label="Close"></button>
    <aside class="ftc-modal-panel ftc-help-panel">
      <button class="ftc-modal-close" type="button" data-ftc-help-close aria-label="Close">&times;</button>
      <h2 id="ftc-help-title">Helpful Prompts</h2>
      <p>Try a question, topic, or shortcut.</p>
      <div class="ftc-help-cloud" aria-label="Helpful prompt shortcuts">
        <?php foreach(array_values(array_unique([
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
          'Testimonials',
          'Request a Proposal',
        ])) as $prompt): ?>
          <button type="button" data-prompt="<?php echo esc_attr($prompt); ?>"><?php echo esc_html($prompt); ?></button>
        <?php endforeach; ?>
      </div>
    </aside>
  </div>
</div>
