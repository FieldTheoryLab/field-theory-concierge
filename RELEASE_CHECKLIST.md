# Field Theory Concierge Release Checklist

## 2.8.0 QA Checklist

This release is focused on clean 2.8 packaging, admin stability, and Flywheel upload testing.

### Must stay locked
- Search remains at the bottom.
- Quick prompt chips remain above the search.
- Footer byline remains below the search.
- Grid background remains.
- Typing animation remains.

### Fixed in this pass
- Response CPT admin menu slug matches the plugin menu.
- Version migration no longer overwrites edited response copy on every version bump.
- Known legacy HTTP demo video default is migrated to the bundled local video.
- Intro heading and prompt use editable settings.
- Public AJAX project and service detail endpoints require published posts.
- Portfolio and service meta saves unslash submitted values before sanitizing.
- Duplicate portfolio detail AJAX hook registration removed.
- Legacy response-options admin screen removed.
- Portfolio projects get 3-5 temporary placeholder gallery images.
- Service items get temporary placeholder service images and featured images.
- Version metadata now points to 2.8.0.

### Before shipping
- Test first visit: intro types, then input gets focus.
- Test chips: no user bubble is cut off.
- Test response page: headline is left aligned.
- Test homepage: video is visible.
- Test the Field Theory Concierge admin menu and Concierge Responses list.
- Test one portfolio detail and one service detail from the public shortcode.
