<?php if (!defined('ABSPATH')) exit; $s = ftc_get_settings(); ?>
<div class="ftc-app" data-ftc-app data-theme="dark">
  <div class="ftc-bg" aria-hidden="true"></div>
  <header class="ftc-header" data-ftc-header>
    <button class="ftc-logo-btn" type="button" data-ftc-reset aria-label="Reset conversation">
      <img class="ftc-logo ftc-logo-dark" src="<?php echo esc_url($s['dark_logo']); ?>" alt="Field Theory Lab">
      <img class="ftc-logo ftc-logo-light" src="<?php echo esc_url($s['light_logo']); ?>" alt="Field Theory Lab">
      <img class="ftc-logo-icon" src="<?php echo esc_url($s['icon_logo']); ?>" alt="Field Theory Lab icon">
    </button>
    <div class="ftc-header-actions">
      <button class="ftc-action" type="button" data-ftc-theme aria-label="Toggle light or dark mode">☾</button>
      <button class="ftc-action" type="button" data-ftc-menu aria-label="Explore">+</button>
    </div>
  </header>

  <main class="ftc-main">
    <section class="ftc-stage ftc-stage-intro is-visible" data-ftc-intro>
      <div class="ftc-intro-copy">
        <h1><?php echo esc_html($s['tagline']); ?></h1>
        <p><?php echo esc_html($s['descriptor']); ?></p>
      </div>
      <form class="ftc-entry-form" data-ftc-name-form>
        <label><?php echo esc_html($s['name_prompt']); ?></label>
        <div class="ftc-input-shell ftc-entry-shell">
          <input type="text" data-ftc-name-input placeholder="Type your name..." autocomplete="name" required>
          <button type="submit">ENTER</button>
        </div>
      </form>
    </section>

    <section class="ftc-stage ftc-stage-chat" data-ftc-chat>
      <div class="ftc-chat-stream" data-ftc-stream></div>
    </section>
  </main>

  <div class="ftc-prompt-dock" data-ftc-prompt-dock>
    <div class="ftc-main-menu">
      <button type="button" data-prompt="Show me your work">Portfolio</button>
      <button type="button" data-prompt="What services do you offer?">Services</button>
      <button type="button" data-prompt="Tell me about your company">About</button>
      <button type="button" data-prompt="How can I work with Field Theory?">Contact</button>
    </div>
    <form class="ftc-chat-form" data-ftc-chat-form>
      <div class="ftc-input-shell">
        <input type="text" data-ftc-chat-input placeholder="<?php echo esc_attr($s['input_placeholder']); ?>" autocomplete="off">
        <button class="ftc-clear" type="button" data-ftc-clear aria-label="Clear conversation">×</button>
        <button class="ftc-send" type="submit" aria-label="Send">↑</button>
      </div>
    </form>
  </div>

  <div class="ftc-modal" data-ftc-modal aria-hidden="true">
    <button class="ftc-modal-bg" type="button" data-ftc-close aria-label="Close"></button>
    <aside class="ftc-modal-panel">
      <button class="ftc-modal-close" type="button" data-ftc-close>×</button>
      <h2>Explore Field Theory</h2>
      <div data-ftc-menu-content><div class="ftc-loader"></div></div>
    </aside>
  </div>
</div>
