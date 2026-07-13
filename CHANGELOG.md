# Changelog

All notable changes to YIARI Donasi Kukang Plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Repository operating standard in `AGENTS.md` covering transaction safety, auditability, WordPress security, changelog discipline, and GitHub workflow.
- Repository-local skill `.codex/skills/yiari-wordpress-plugin-dev/SKILL.md` for consistent plugin development practices in this project.
- Legacy data migrator for copying `kukang_dolls_new` and `kukang_transactions_new` into the normalized YIARI tables without deleting old data.
- Product repository and order service for normalized-table-first catalog reads and dual-write order persistence.
- Next-generation schema migrator for normalized `products`, `orders`, `order_items`, `shipments`, and `order_status_logs` tables without removing legacy tables yet.
- Midtrans callback synchronization into normalized order payment and fulfillment states.

### Changed
- Updated implementation plans to support book purchase plus donated-book flow and donation motivation capture.
- Elevated repository guidance to require changelog updates for all material code, schema, workflow, and documentation changes.
- Payment item generation now reads products through the normalized-table-first catalog layer instead of directly from the legacy dolls table.

## [3.1.1] - 2025-09-26

### Fixed
- **CRITICAL**: Fixed fatal error during plugin activation due to missing helper files
- **CRITICAL**: Resolved WordPress cron job conflicts causing "Undefined array key" errors
- Fixed autoloader issues in modular architecture
- Added proper error handling for missing dependencies

### Added
- `helpers/functions.php` - Utility functions for currency conversion, formatting, and validation
- `helpers/ajax-handlers.php` - AJAX request handlers for donation tracking and form processing
- `includes/class-yiari-plugin-deactivator.php` - Proper cleanup on plugin deactivation
- File existence checks in plugin loader to prevent fatal errors
- Cron job cleanup mechanism to prevent conflicts
- Enhanced error logging for debugging

### Changed
- Renamed cron hook from `update_exchange_rates` to `yiari_update_exchange_rates` to prevent conflicts
- Improved plugin loader with better error handling
- Enhanced modular structure with proper dependency checks

### Technical Details
- Fixed missing helper files that caused `require_once` fatal errors
- Resolved cron job registration conflicts between modules
- Added deactivation hooks for proper cleanup
- Improved autoloader with fallback mechanisms
- Added namespace prefixes to prevent hook conflicts

### Developer Notes
- Plugin now uses clean modular architecture
- All syntax errors resolved and validated
- Proper error handling implemented throughout
- Cron jobs use unique namespace (`yiari_*`)
- Deactivation cleanup includes cron events and transients

---

## [3.1.0] - Previous Version

### Added
- Multi-language support (Indonesian/English)
- Multi-currency support (IDR/USD)
- Dynamic exchange rate system with API integration
- Modular plugin architecture
- Enhanced admin interface
- Biteship shipping integration
- Improved payment processing with Midtrans

### Features
- Donation form with real-time currency conversion
- Automatic exchange rate updates
- Comprehensive admin dashboard
- Order tracking system
- Export functionality with PHPSpreadsheet
- Enhanced security and validation

---

## Migration Guide

### From v3.1.0 to v3.1.1
1. **Backup** your current plugin files
2. **Deactivate** the plugin in WordPress admin
3. **Upload** the new version files
4. **Activate** the plugin - it should now work without fatal errors
5. **Test** donation forms and admin functionality
6. **Check** WordPress error logs for any remaining issues

### Important Notes
- This is a maintenance release focusing on stability
- No database changes required
- All previous functionality preserved
- Cron jobs will be automatically cleaned and re-scheduled

---

## Support

If you encounter any issues after updating:
1. Check WordPress error logs
2. Verify all plugin files are uploaded correctly
3. Ensure PHP version compatibility (7.4+)
4. Test with WordPress debug mode enabled

For technical support, contact Julian H - MCIMEDIA.
