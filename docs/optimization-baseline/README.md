# Concierge Baseline Audit

This folder stores baseline captures used to validate no-regression optimization work for the concierge plugin.

## Capture script

- Run: `node tools/_baseline-concierge-audit.mjs`
- Optional base URL override: `FTC_BASE_URL=http://example.local node tools/_baseline-concierge-audit.mjs`

## What gets captured

- Routes: `/`, `/get-started/`, `/go-time/`, `/services/`
- Viewports: desktop and iPhone 14
- Outputs:
  - route screenshots (`*.png`)
  - aggregate metrics snapshot (`baseline-summary.json`)

## Regression usage

1. Run this script before optimization changes.
2. Run it again after each phase.
3. Compare:
   - resource counts and bytes
   - timing (DCL, load, response start)
   - visual screenshots for drift
