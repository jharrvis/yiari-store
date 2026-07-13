---
name: yiari-wordpress-plugin-dev
description: Use when working on this repository’s WordPress donation plugin, especially for payment, shipping, donor/order data, schema changes, auditability, changelog discipline, and security-sensitive checkout or fulfillment flows.
---

# YIARI WordPress Plugin Development

Use this skill for any implementation, refactor, bugfix, or review work in this repository.

## Core Priorities
1. Protect transaction correctness before adding features.
2. Preserve auditable donor and order records.
3. Follow WordPress coding and security practices strictly.
4. Keep every material change reflected in `CHANGELOG.md`.
5. Prefer GitHub-backed checkpoints after meaningful milestones.

## Required Workflow
1. Read the active plan files in `plan/` and the latest audit note in `docs/notes/` before changing architecture or data flow.
2. Inspect the relevant runtime modules before editing. Do not assume legacy and new tables are interchangeable.
3. For payment, shipping, or order logic:
   - identify the exact write path
   - identify callbacks and retries
   - define idempotency behavior before patching code
4. For schema changes:
   - design new tables first
   - keep migration and rollback notes
   - avoid destructive drops until data migration is validated
5. After changes:
   - run syntax validation on touched PHP files
   - update `CHANGELOG.md`
   - summarize operational impact

## Transaction Safety Rules
- Payment status changes must be idempotent.
- Shipment creation must happen only from the intended paid state.
- Notifications must not duplicate on callback replay.
- Store enough state to reconcile: order status, payment status, shipment status, and timestamps.

## WordPress Security Rules
- Sanitize input on entry and escape output on render.
- Enforce nonce and capability checks on admin and AJAX writes.
- Use `$wpdb` safely and avoid building unsafe SQL from request values.
- Never commit secrets, live credentials, or raw sensitive dumps.

## Reporting and Audit Rules
- Donor intent, donation motivation, payment result, and shipment result must remain queryable.
- Prefer normalized `orders`, `order_items`, `shipments`, and status-log tables over hardcoded per-product quantity columns.
- If a feature weakens reporting clarity, redesign it before implementation.

## Repository Conventions
- Put architecture plans in `plan/`.
- Put audits and implementation notes in `docs/notes/`.
- Put new domain code in the target module folders under `modules/`.
- Treat root-level legacy modules as transitional unless explicitly modernized.
