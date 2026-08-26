# Commands – Agent Reference

## Environment

All commands assume you are in the repo root. The local environment runs under **Lando**. Lando must be running before running PHP or WP-CLI commands, or when trying work on or view the local environment. When the local environment is needed, first check if it is running using `lando info`. If not, start it with `lando start`.

## PHP / Composer

```bash
lando composer install               # install dependencies
lando composer require <package>     # add a package (ask before doing this)
lando composer phpcs                 # lint PHP (ModernTribe ruleset)
lando composer phpcbf                # auto-fix PHP lint errors
lando composer phpstan               # static analysis (level 5)
```

> Bare `composer <cmd>` also works if Composer is installed on the host; prefer `lando composer` to keep the PHP version consistent.

For PHP work, treat both `phpcs` and `phpstan` as done-gates (scoped runs OK for narrow
edits). See `docs/ai/php.md` for coding standards and the review checklist.

## JS / CSS (npm)

```bash
npm run dev          # watch mode — recompiles on file changes
npm run build        # development build (unminified)
npm run dist         # production build (minified, hashed)
npm run lint         # format + lint JS + CSS (includes auto-fix)
npm run lint:server  # lint only, no auto-fix (safe for CI)
npm run format       # format JS/CSS with Prettier
```

Build output goes to `wp-content/themes/core/dist/`. Never edit files in `dist/` directly.

## WP-CLI

**Always use `lando wp` — never bare `wp`.**

```bash
lando wp plugin list
lando wp post list --post_type=page
lando wp cache flush
lando wp rewrite flush
lando wp cron event run --due-now
```

## Lando

```bash
lando start              # start the local environment
lando stop               # stop (containers off, data preserved)
lando rebuild            # rebuild containers (after .lando.yml changes)
lando db-import <file>   # import a .sql database dump
lando db-export          # export the current database
lando ssh                # open a shell inside the PHP container
lando info               # show service URLs and ports
```

## Tests

PHP tests run via [Slic](https://github.com/stellarwp/slic) (WP-Browser + Codeception in a Docker container). See `docs/php-tests.md` for full setup.

```bash
slic here                    # from the project root, tell Slic to use this folder
slic use site                # test the full site, not a single plugin/theme
slic run wpunit               # run the wpunit suite
slic run unit                 # run the unit suite
slic run functional           # run the functional suite
slic run acceptance           # run the acceptance suite
```
