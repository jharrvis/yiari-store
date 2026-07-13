# Repository Guidelines

## Operating Standard
This plugin handles donations, payments, shipping, donor data, and fulfillment. Every change must optimize for transaction safety first, operational traceability second, and feature speed third. If a tradeoff appears, prefer the safer and more auditable path.

## Project Structure & Module Organization
- `yiari-donasi-kukang.php` is the current bootstrap entry.
- `includes/` contains loader and lifecycle code.
- `modules/` contains runtime business logic. Legacy modules remain at the root of `modules/` during transition. New architecture should go into:
  - `modules/catalog/`
  - `modules/orders/`
  - `modules/payments/`
  - `modules/shipping/`
  - `modules/notifications/`
  - `modules/legacy/`
- `helpers/` is for small stateless helpers only.
- `css/`, `js/`, `admin/`, `public/`, and `widgets/` hold UI assets and presentation code.
- `plan/` stores active implementation plans. `docs/` stores reports, notes, and audits.

## Development Priorities
1. Keep payment and fulfillment flows correct, idempotent, and observable.
2. Preserve a professional donor ledger: order values, donation intent, payment state, shipment state, and notification state must be traceable.
3. Follow WordPress standards for hooks, sanitization, escaping, capability checks, nonce validation, and database access.
4. Update `CHANGELOG.md` for every material code, schema, workflow, or documentation change.
5. Keep Git history clean and push through GitHub regularly.

## Build, Test, and Validation
- `php -l path/to/file.php` for targeted syntax checks.
- `find . -name '*.php' -not -path './vendor/*' -not -path './midtrans-php-master/*' -exec php -l {} \\;` for broad syntax validation.
- `test_form.php` and `test_functionality.php` must be kept runnable in WordPress context for manual regression checks.
- Before merging payment, shipping, or order changes, validate:
  - checkout creation
  - Midtrans callback handling
  - order idempotency
  - shipment creation rules
  - email/certificate triggers

## Security Rules
- Never commit live secrets, API keys, database credentials, or production payload dumps.
- Sanitize all request input with WordPress helpers and escape all output.
- Verify nonces and capabilities on every admin or AJAX write action.
- Treat Midtrans and KiriminAja callbacks as untrusted input; validate signatures or callback authenticity where supported.
- Make external side effects idempotent. A repeated callback must not duplicate payment records, shipments, emails, or certificates.

## Changelog & Version Control
- Use `CHANGELOG.md` with Keep a Changelog structure.
- Add entries under `Unreleased` while work is in progress.
- Commit subjects should be short and imperative, for example `Add shipment state log table`.
- Push changes to GitHub after each meaningful checkpoint, especially plan updates, schema work, and transaction-flow changes.

## Professional Workflow
- Backup before schema or flow changes.
- Write migration paths before deleting legacy data structures.
- Prefer normalized tables for orders, order items, shipments, and audit logs.
- If requirements are ambiguous, choose the model that improves reporting, reconciliation, and rollback safety.
