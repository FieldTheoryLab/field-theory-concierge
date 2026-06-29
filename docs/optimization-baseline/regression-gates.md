# No-Regression Release Gates

Run these checks before and after each optimization phase.

## 1) Baseline Snapshot

- `node tools/_baseline-concierge-audit.mjs`
- Confirm `docs/optimization-baseline/baseline-summary.json` updates.
- Confirm screenshots exist for:
  - `home-desktop.png`, `get-started-desktop.png`, `go-time-desktop.png`, `services-desktop.png`
  - `home-iphone14.png`, `get-started-iphone14.png`, `go-time-iphone14.png`, `services-iphone14.png`

## 2) UX / Visual Drift

- Run existing visual checks in `tools`:
  - `node tools/_verify-services-page.mjs`
  - `node tools/_verify-go-time.mjs`
  - `node tools/_verify-quizzes-pulse.mjs`
- Compare new output against prior captures in `docs`.

## 3) Functional Smoke Tests

- Home prompt to Services still opens service categories and 3D cards.
- Home prompt to Go Time still initializes spline rail and chapter transitions.
- Route pages still render correctly:
  - `/get-started/`
  - `/go-time/`
  - `/services/`
  - `/portfolio/`
- Proposal flow still submits successfully with valid name/email.

## 4) Security / Compliance Checks

- Proposal submissions reject obvious bot spam (filled honeypot).
- Rate limiting returns `429` under repeated abuse attempts.
- Utility scripts in `tools/*.php` cannot execute over web requests.
- Privacy policy prompt text reflects current inquiry data handling.

## 5) Performance Checks

- Compare `baseline-summary.json` before/after:
  - script resource count
  - script transfer bytes
  - DCL/load timings by route
- Ensure no route has degraded load behavior without a documented reason.
