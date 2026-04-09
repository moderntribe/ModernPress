/**
 * Prettier Configuration
 *
 * Extends WordPress Prettier configuration with project-specific customizations.
 */

const config = require( '@wordpress/prettier-config' );

/**
 * Add .pcss to CSS file overrides.
 *
 * The WordPress config only applies CSS formatting rules (singleQuote: false,
 * parenSpacing: false) to *.{css,sass,scss}. Without this, PostCSS files are
 * formatted with JavaScript rules, resulting in single quotes and unwanted
 * spacing like var( --spacer-40 ) instead of var(--spacer-40).
 */
config.overrides[ 0 ].files = '*.{css,sass,scss,pcss}';

module.exports = config;
