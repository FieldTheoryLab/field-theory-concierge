<?php if (!defined('ABSPATH')) exit; $s = ftc_get_settings(); ?>
<div class="ftc-app" data-ftc-app data-theme="dark" style="--ftc-bg-url:url('<?php echo esc_url($s['background_image']); ?>')">
  <div class="ftc-bg" aria-hidden="true"></div>
  <header class="ftc-header" data-ftc-header>
    <button class="ftc-logo-btn" type="button" data-ftc-reset aria-label="Reset Field Theory Concierge">
      <img class="ftc-logo ftc-logo-full" src="<?php echo esc_url($s['dark_logo']); ?>" alt="Field Theory Lab">
      <img class="ftc-logo-icon" src="<?php echo esc_url($s['icon_logo']); ?>" alt="Field Theory Lab icon">
    </button>
    <div class="ftc-header-actions">
      <button class="ftc-hire-btn" type="button" data-prompt="Hire Our Team">Hire Our Team</button>
      <button class="ftc-action ftc-theme-action" type="button" data-ftc-theme aria-label="Toggle light or dark mode">☾</button>
      <button class="ftc-action ftc-menu-action" type="button" data-ftc-menu aria-label="Explore">+</button>
    </div>
  </header>
  <main class="ftc-main">
    <section class="ftc-stage ftc-stage-intro is-visible" data-ftc-intro>
      <div class="ftc-intro-content">
        <h1 data-ftc-intro-heading><?php echo esc_html($s['tagline'] ?? ''); ?></h1>
        <p data-ftc-intro-body>We're a creative technology agency, helping organizations with website, digital marketing, data analysis and A.I.</p>
        <div class="ftc-intro-question"><?php echo esc_html($s['name_prompt'] ?? 'How may we help you today?'); ?></div>
      </div>
    </section>
    <section class="ftc-stage ftc-stage-chat" data-ftc-chat><div class="ftc-chat-stream" data-ftc-stream></div></section>
  </main>
  <div class="ftc-prompt-dock" data-ftc-prompt-dock>
    <button class="ftc-help-dot" type="button" data-prompt="FAQ" aria-label="Open FAQs">?</button>
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
    <form class="ftc-chat-form ftc-bottom-search" data-ftc-chat-form>
      <div class="ftc-input-shell"><input type="text" data-ftc-chat-input placeholder="<?php echo esc_attr($s['input_placeholder']); ?>" autocomplete="off"><button class="ftc-clear" type="button" data-ftc-clear aria-label="Clear conversation">×</button><button class="ftc-send" type="submit" aria-label="Send">CHAT</button></div>
    </form>
    <div class="ftc-site-byline">Field Theory Lab, Albuquerque, NM. All Rights Reserved. <button type="button" data-prompt="Privacy Policy">Privacy Policy</button></div>
  </div>
  <div class="ftc-modal" data-ftc-modal aria-hidden="true"><button class="ftc-modal-bg" type="button" data-ftc-close aria-label="Close"></button><aside class="ftc-modal-panel"><button class="ftc-modal-close" type="button" data-ftc-close aria-label="Close">×</button><h2>Explore Field Theory</h2><div data-ftc-menu-content><div class="ftc-loader"></div></div></aside></div>
</div>
