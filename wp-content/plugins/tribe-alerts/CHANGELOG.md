# Changelog

All notable changes to this project are documented in this file.

## [1.8.0] - 2026-08-10

### Breaking

- Requires PHP 8.0+ (was 7.4+)
- Upgrades `moderntribe/tribe-libs` to `^5.0` (PHP-DI: `useAnnotations` → `useAttributes`)

### Fixed

- Hide alerts that are not published
- Resolve Composer security audit by adding `enshrined/svg-sanitize` and dropping the previous audit ignore
- PHP 8.4 compatibility patches

### Changed

- Bootstrap plugin on early `init` with `load_plugin_textdomain` (WordPress 6.7+ i18n rules)
- Bump runtime dependencies (`extended-cpts`, `league/plates`, and related packages)

### Development

- Add Lando-based local environment; remove embedded `public/` WordPress tree
- Update CI (GitHub Actions, WordPress install for tests, auth for private packages)
- Refresh README and ACF Pro Composer setup (WPE Composer mirror)

## [1.7.1] - 2022-10-12

### Fixed

- Format alert output without double-encoding existing HTML entities
