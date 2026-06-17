# Field Theory Concierge Release Checklist

## 2.6.10 QA Hotfix

This release is intentionally limited to regressions called out in review screenshots.

### Must stay locked
- Search remains at the bottom.
- Quick prompt chips remain above the search.
- Footer byline remains below the search.
- Grid background remains.
- Typing animation remains.

### Fixed in this pass
- Intro/onboarding renders one centered logo, not duplicate logos.
- Intro copy is smaller and calmer.
- Name input is flat: no pill, no shadow, no bordered input box.
- Typing cursor is removed before the input receives focus, preventing the double-cursor effect.
- Name/onboarding completion is stored in localStorage and cookie fallback.
- Returning visitors skip onboarding and go directly to the homepage/chat content.
- Logo reset no longer clears the visitor name or onboarding state.
- Response titles are left aligned.
- Response headlines are constrained to roughly 38–40px desktop with tighter line-height.
- Response containers use a wider app-style canvas.
- User chat bubbles and prompt chips no longer clip on the right edge.
- Homepage video is preserved and styled as a visible media block.

### Before shipping
- Test first visit: intro types, then input gets focus.
- Test Set: name saves and homepage appears.
- Refresh page: onboarding does not appear again.
- Test Skip: homepage appears and refresh does not show onboarding again.
- Test chips: no user bubble is cut off.
- Test response page: headline is left aligned.
- Test homepage: video is visible.
