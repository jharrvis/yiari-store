# Repository Guidelines

## Project Structure & Module Organization
This repository is a WordPress plugin for YIARI donations and Midtrans payments. The bootstrap file is `yiari-donasi-kukang.php`. Core runtime logic lives in `modules/` with `YIARI_*` classes such as `class-yiari-payment-manager.php`, `class-yiari-form-manager.php`, and `class-yiari-public-module.php`. Shared bootstrap and lifecycle code lives in `includes/`, helper functions and AJAX handlers live in `helpers/`, browser assets are in `css/` and `js/`, and optional UI pieces are under `admin/`, `public/`, and `widgets/`. Third-party code is vendored in `vendor/` and `midtrans-php-master/`; avoid editing those directly.

## Build, Test, and Development Commands
There is no root build pipeline. Typical checks are manual and WordPress-based:

- `php -l yiari-donasi-kukang.php` checks syntax for a changed file.
- `find . -name '*.php' -not -path './vendor/*' -not -path './midtrans-php-master/*' -exec php -l {} \\;` runs a broad syntax pass.
- Open `/wp-content/plugins/yiari_donasi_midtrans/test_form.php` in a local WordPress install to exercise the shortcode UI.
- Open `/wp-content/plugins/yiari_donasi_midtrans/test_functionality.php` inside WordPress to verify shortcodes, tables, AJAX hooks, and currency helpers.
- `php midtrans-php-master/vendor/bin/phpunit -c midtrans-php-master/phpunit.xml` is only for the bundled Midtrans library, not the plugin itself.

## Coding Style & Naming Conventions
Follow the existing PHP style: 4-space indentation, brace-on-same-line for classes and methods, and WordPress-style arrays like `array(...)`. Plugin classes use the `YIARI_` prefix and map to `class-yiari-*.php` filenames. Keep new helpers in `helpers/`, not the bootstrap file. Match existing asset naming such as `donation-form.js` and `donation-form.css`.

## Testing Guidelines
Add or update focused PHP test pages when behavior changes. Name repo-level checks `test_*.php` and keep them runnable in a WordPress context. Validate both Indonesian and English flows, IDR/USD handling, Midtrans callbacks, and any table or shortcode changes.

## Commit & Pull Request Guidelines
`master` currently has no commits, so there is no established history to mirror. Use short imperative commit subjects such as `Fix USD conversion rounding` or `Add admin exchange rate check`. PRs should include a summary, affected shortcodes or admin screens, setup or migration notes, and screenshots for UI changes. Call out any config dependencies on Midtrans, Biteship, or exchange-rate services.

## Security & Configuration Tips
Do not commit live API keys or WordPress secrets. Keep environment-specific debugging in local-only files, and sanitize all AJAX inputs using WordPress helpers before touching payment, donor, or shipping data.
