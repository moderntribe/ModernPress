# Tribe Alerts

Displays a customizable banner on the screen and remembers when users have dismissed it.

---

Display custom banner alerts on your website.

---

## Requirements

- [Lando](https://lando.dev/) (Docker-backed local stack)
- PHP 7.4+ (satisfied inside Lando; match `composer.json` platform when running Composer on the host)
- Advanced Custom Fields Pro (installed via Composer; see [Composer authentication](#composer-authentication-acf--private-packages))
- For assets: [nvm](https://github.com/nvm-sh/nvm) or [fnm](https://github.com/Schniz/fnm), Node (see [`.nvmrc`](.nvmrc)), Yarn 1.22+, npm 8.3+

---

## Run locally (Lando)

### 1. Environment file for WordPress / database

Copy the example env file Lando loads:

```bash
cp dev/lando/.env.example dev/lando/.env
```

Edit `dev/lando/.env` if needed. **`WP_HOME` and `WP_SITEURL` must match the hostname Lando exposes** (see `proxy` → `appserver_nginx` in [`.lando.yml`](.lando.yml); currently `https://tribe-alert.lndo.site`).

### 2. Composer authentication (ACF & private packages)

`composer.json` pulls **ACF Pro** from `connect.advancedcustomfields.com` and **nickford/acf-swatch** from GitHub over SSH. Composer reads **`auth.json` in the project root** for HTTP Basic auth. That file is listed in `.gitignore` and must not be committed.

**Option A — from the sample (Modern Tribe / 1Password)**

[`auth-sample.json`](auth-sample.json) is a template that expects [1Password CLI](https://developer.1password.com/docs/cli/) secret references. From the repo root:

```bash
cp auth-sample.json auth.json
op inject -i auth-sample.json -o auth.json
```

Adjust vault/item names via environment variables if your team uses different 1Password paths (see placeholders in `auth-sample.json`).

**Option B — manual `auth.json`**

Create `auth.json` in the project root with your ACF subscription credentials (from your ACF account: license key and the signed download URL):

```json
{
  "http-basic": {
    "connect.advancedcustomfields.com": {
      "username": "<your-acf-license-key>",
      "password": "<your-acf-download-url-or-token-as-documented-by-acf>"
    }
  }
}
```

Use the exact username/password pair ACF documents for Composer.

**GitHub (private VCS package)**

For `git@github.com:nickforddesign/acf-swatch.git`, ensure your machine (or the Lando container) can use that SSH key. Typical approaches:

- Load your SSH agent on the host before `lando start`, or  
- Add a deploy key / personal key inside the appserver and run `composer install` there after `lando ssh`.

If Composer fails on `acf-swatch`, fix SSH access to GitHub first.

### 3. Start Lando

From the repository root:

```bash
lando start
```

On start, Lando runs `composer install` and [`dev/lando/install-wp.sh`](dev/lando/install-wp.sh) (WordPress download + `wp core install` when `wp-config.php` is missing).

### 4. Open the site

Use the URL from `.lando.yml` proxy settings (e.g. **https://tribe-alert.lndo.site**). MailHog is available per your Lando tooling (`lando info`).

### 5. Activate the plugin

In **WP Admin → Plugins**, activate **Tribe Alerts** (and other required plugins such as ACF Pro if not auto-activated).

### Composer and `vendor`

- You may run **`composer install`** on the **host** (with `auth.json` present) or rely on **`lando composer install`** / the `post-start` hook.  
- If `vendor` looks empty on the host while dependencies work in the container, check Lando file-sharing / `excludes` in `.lando.yml`.

---

## Front end

Front-end builds use [Laravel Mix](https://laravel-mix.com/).

### Building

```bash
nvm use
```

```bash
yarn install
```

### Usage

Build for development:

```bash
yarn dev
```

Watch for file changes:

```bash
yarn watch
```

Poll for file changes:

```bash
yarn watch-poll
```

Watch with hot module replacement:

```bash
yarn hot
```

Build for production:

```bash
yarn production
```

Run the Mix CLI directly (same underlying binary the scripts use):

```bash
npx mix
```

See more options: `npx mix --help`

### Pull requests / building

Run **`yarn prod`** before submitting a PR so [`resources/dist`](resources/dist) contains the latest production assets.

---

## Installing this plugin

Every published [release](https://github.com/moderntribe/tribe-alerts/releases) creates a `tribe-alerts.zip` (built, vendor-scoped plugin) shortly after the release is published. To install manually, download the zip from a release and extract it into your WordPress `wp-content/plugins` directory.

### Composer (consumer projects)

The best way to include the release zip is with [ffraenz/private-composer-installer](https://github.com/ffraenz/private-composer-installer).

Add a custom repository to your project’s `repositories` in `composer.json`:

```json
  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "moderntribe/tribe-alerts",
        "version": "1.1.0",
        "type": "wordpress-plugin",
        "dist": {
          "type": "zip",
          "url": "https://github.com/moderntribe/tribe-alerts/releases/download/{%VERSION}/tribe-alerts.zip"
        },
        "require": {
          "ffraenz/private-composer-installer": "^5.0"
        }
      }
    }
  ]
```

> **Note:** Bump the version above and run `composer update` to upgrade the plugin later.

Add the package to `require`:

```json
  "require": {
    "moderntribe/tribe-alerts": "*"
  }
```

Point Composer installers at your WordPress layout (adjust paths for your project):

```json
  "extra": {
    "wordpress-install-dir": "wp",
    "installer-paths": {
      "wp-content/mu-plugins/{$name}": [
        "type:wordpress-muplugin"
      ],
      "wp-content/plugins/{$name}": [
        "type:wordpress-plugin"
      ],
      "wp-content/themes/{$name}": [
        "type:wordpress-theme"
      ]
    }
  }
```

Allow the plugins in `config`:

```json
    "allow-plugins": {
      "composer/installers": true,
      "ffraenz/private-composer-installer": true
    }
```

Install:

```bash
composer update
```

---

## Displaying an alert

The banner is output on the `wp_footer` hook by default. To render it yourself:

```php
<?php if ( function_exists( '\Tribe\Alert\tribe_alert' ) && function_exists( '\Tribe\Alert\render_alert' ) ) {
    \Tribe\Alert\render_alert();
} ?>
```

Disable automatic `wp_footer` output in `wp-config.php`:

```php
define( 'TRIBE_ALERTS_AUTOMATIC_OUTPUT', false );
```

---

## Customize the alert view markup

Filter the view directory, for example:

```php
add_filter( 'tribe/alerts/view_directory', static fn ( string $directory ) => get_stylesheet_directory() . '/components/alerts', 10, 1 );
```

Copy [`resources/views/alert.php`](resources/views/alert.php) into that folder and customize.

---

## ACF swatch field options

Color options are off by default. Enable in `wp-config.php`:

```php
define( 'TRIBE_ALERTS_COLOR_OPTIONS', true );
```

Filter swatch colors:

```php
add_filter( 'tribe/alerts/color_options', static fn ( array $options ) => [
    '#880ED4' => [
        'name'  => esc_html__( 'Purple', 'tribe-alerts' ),
        'class' => 'purple-mono',
    ],
    '#8155BA' => [
        'name'  => esc_html__( 'Violet', 'tribe-alerts' ),
        'class' => 'violet',
    ],
    '#323E42' => [
        'name'  => esc_html__( 'Charcoal', 'tribe-alerts' ),
        'class' => 'charcoal',
    ],
], 10, 1 );
```

Default CSS class prefix is `tribe-alerts__theme` → `tribe-alerts__theme-$name`. Filter the prefix:

```php
add_filter( 'tribe/alerts/color_options/css_class_prefix', static fn ( string $prefix ) => 'new-prefix', 10, 1 );
```

---

## CI note

GitHub Actions workflows under [`.github/workflows/`](.github/workflows/) may still reference the old `dev/docker` stack. If those files were removed in your branch, update CI to match Lando or another test runner before relying on green builds.

---

## Credits

- Based on [Spatie Skeleton](https://github.com/spatie/package-skeleton-php)

---

## License

GNU General Public License GPLv2 (or later). See [LICENSE.md](LICENSE.md).

---

## Modern Tribe

[![Modern Tribe](https://moderntribe-common.s3.us-west-2.amazonaws.com/marketing/ModernTribe-Banner.png)](https://moderntribeagency.com/contact/)
