# Pattern Sync

Sync registered block patterns between WordPress installations via REST API and Application Passwords.

## Requirements

- PHP 8.2+
- WordPress 5.6+ (Application Passwords)
- Composer (for installation)

## Installation

1. Install dependencies: `composer install` in the plugin directory.
2. Activate the plugin in WordPress.
3. Go to **Tools → Pattern Sync** to add a connection and sync patterns.

## Usage

1. **Add a connection**: Enter a connection name, the remote site URL, WordPress username, and an Application Password (created under Users → Profile → Application Passwords on the remote site).
2. **Test connection**: Use the "Test connection" button to verify the remote site has Pattern Sync and accepts your credentials.
3. **Sync patterns**: Click "Sync with this site" on a connection. Select patterns from the remote or local list and use "Pull selected to this site" or "Push selected to remote".
4. **Sync Log**: View recent sync runs under "Sync Log".

## REST API

The plugin registers `pattern-sync/v1` REST routes (authenticated via Application Passwords):

- `GET /wp-json/pattern-sync/v1/patterns` – List registered block patterns
- `GET /wp-json/pattern-sync/v1/pattern-categories` – List pattern categories
- `POST /wp-json/pattern-sync/v1/patterns` – Store a pattern (used when another site pushes to this one)

## License

GPL-2.0-or-later
