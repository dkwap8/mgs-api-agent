# Final Frontend and UI/UX Release Audit

## Release decision

**Status: BLOCKED — do not publish the production archive yet.**

The source-level UI/UX and business-context audit has been completed as far as the current environment permits. The release remains blocked until dependency installation, the production Vite build, Laravel runtime tests, database migrations, and browser-based responsive testing all pass.

## Source changes completed in this audit

- Added global production UI primitives for focus states, form visibility, browser autofill, disabled controls, mobile font sizing, responsive tables, modal viewport constraints, skeleton loaders, empty states, reduced motion, forced-colour support, and consistent interaction transitions.
- Added a professional reset-password screen with floating labels, password visibility controls, strength feedback, Caps Lock detection, validation feedback, loading state, autocomplete metadata, dark mode, responsive layout, and the existing central verification modal.
- Registered the reset-password route.
- Corrected the authenticated profile response so it no longer loads a merchant-level wallet or merchant-wide API-key collection. It now exposes only the active business wallet and API keys filtered by the active business.
- Revalidated all Vue script blocks with Node syntax checking.
- Revalidated all PHP application, route, configuration, and migration files with `php -l`.

## Business isolation checks

The frontend request layer attaches the selected business through `X-Business-ID`. The business switch service clears business-scoped local and session state before storing the newly selected business and dispatches business-context events.

The authenticated profile endpoint was corrected during this audit because it still attempted to expose merchant-level wallet/API-key relationships.

Full financial isolation is not considered runtime-verified until migrations and end-to-end tests are executed against a real database with at least two businesses under the same merchant.

## Static verification results

- 291 PHP files passed syntax validation.
- 36 Vue script blocks passed Node syntax validation.
- No native browser `alert()` or `window.confirm()` usage remains outside the central verification-modal service.
- The reset-password frontend route and component pass JavaScript syntax validation.

## Failed release gates

### Frontend dependencies

`npm ci` failed because the configured package gateway returned HTTP 503 responses for package tarballs. An offline installation also failed because required packages were not cached.

### Production build

`npm run build` could not run because `vite` was not installed after the failed dependency installation.

### Laravel runtime tests

The `vendor` directory is absent, so Artisan and PHPUnit cannot start. Composer dependencies must be installed before runtime tests can run.

## Mandatory commands before release

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan test
npm ci
npm run build
```

## Mandatory browser matrix

Verify the compiled application in:

- Current Chrome and Edge on Windows
- Current Firefox
- Current Safari on macOS/iOS
- Chrome on Android

Test at minimum:

- 320px, 375px, 390px and 430px mobile widths
- 768px tablet width
- 1024px, 1280px, 1440px and wider desktop layouts
- Light mode and dark mode
- Keyboard-only navigation
- Browser autofill
- 200% zoom
- Reduced-motion preference

## Multi-business runtime scenario

Create Merchant M with Business A and Business B, then verify:

1. Each business has a separate wallet row and currency-scoped unique key.
2. Crediting A does not change B.
3. Reserving, capturing, or releasing A funds does not change B.
4. Transactions, collections, payouts, ledger entries, statistics, charts, reports, API keys, settlement accounts, notifications, and dashboard data are distinct.
5. API keys authenticate only their assigned business.
6. Settlement accounts cannot be listed, selected, updated, or used by another business.
7. Switching A → B → A clears stale frontend state and reloads all financial endpoints.
8. Attempts to pass another owned or unowned business ID return the correct authorization failure and never leak data.

## Final release condition

Prepare the production ZIP only after every command and browser/runtime scenario above passes. Record the successful command output, migration status, test totals, build manifest, and release checksum in the final engineering change log.

## Pending Deployment Verification

The production npm dependency installation and Vite build were not completed in the source-packaging environment because external package registry access was unavailable. The deployment team must run `npm ci` and `npm run build`, then perform the documented browser and responsive-device checks.
