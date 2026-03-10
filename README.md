# Moose

Moose is a WordPress project starter framework. It is a collection of modular tools, configurations, and best practices
for enterprise WordPress design and development. It is designed to be a modern, flexible, and developer-friendly
starting point for WordPress projects. Features include a core plugin, core theme, technical framework, and the
requisite developer tooling to maintain a secure, consistent codebase across teams and projects. Moose is lovingly
maintained by the folks at [Modern Tribe](https://tri.be).

## Requirements

* [Git](https://git-scm.com/)
* [Composer](https://getcomposer.org/)
* [Node & NPM](https://nodejs.org/)
    * [NVM](https://github.com/nvm-sh/nvm) is recommended for managing multiple versions of node on the same
      workstation.
* [Lando](https://lando.dev/) (Optional) Provides a consistent local development environment for all team members.
* [1Password CLI](https://developer.1password.com/docs/cli/) (Optional) Automates the creation of composer's `auth.json`
  file so that paid 3rd-party plugins like Advanced Custom Fields Pro and Gravity Forms can be installed via composer.

> [!TIP]
> This starter is designed to allow developers the freedom to use any local development tooling that works best for
> them. The following instructions assume the use of Lando, but any local development platform should work as long as it
> provides a basic LAMP or LEMP stack and uses the correct version of PHP as defined in `composer.json`.

## Getting Started

1. Clone the repository
2. Run `composer create-auth` to create the `auth.json` file. (Assumes you are using the
   [1Password CLI](#1password-cli). See the [Composer Docs](./docs/composer.md#creating-an-authjson-file) for manual
   instructions.)
3. Run `lando start` to create the local environment.

That should be it! After Lando starts the first time, it will automatically create the necessary local config files for
the project. Additionally, each time Lando starts it will automatically run `composer install` and
`npm install && npm run build` to make sure all the project dependencies are installed and the theme assets have been
built.

## Documentation

### Lando

Lando is the preferred local development environment for this project. It provides a consistent environment for all team
members to use and provides a number of helpful features. Below are a number of Lando commands to know:

* `lando start` - Starts the local development environment.
* `lando stop` - Stops the local development environment.
* `lando poweroff` - Completely shuts down all running Lando services.
* `lando composer <command>` - Runs a composer command within the project container.
* `lando wp <command>` - Runs a WP-CLI command within the project container.
* `lando npm <command>` - Runs an npm command within the node container. Useful for rebuilding the theme assets.
* `lando db-export` - Exports the project database to a file in the project root.
* `lando db-import <filename.sql>` - Imports a database file into the project database. This file must be located within
  the project directory. It can be either an archived (`.zip`) or unarchived SQL (`.sql`) file.
* `lando rebuild` - Rebuilds the project containers. This is useful if you need to update the PHP version or there have
  been other changes to the project's Lando configuration. This is a non-destructive action and will not delete any
  data.
* `lando destroy` - Destroys the local development environment. *WARNING:* This is a destructive action and will delete
  the existing data within the project database and completely remove all the project containers. It will not delete the
  project files on your local machine.
* `lando xdebug-on` - Enables Xdebug in the project container (Xdebug is disabled by default).
* `lando xdebug-off` - Disables Xdebug in the project container (Xdebug is disabled by default).

For further documentation on Lando, please visit the [Lando Docs](https://docs.lando.dev/).

### Composer

Composer is configured to manage PHP dependencies. There are also a number of composer scripts set up to assist with
day-to-day PHP development. You can learn more about the available scripts and how to use them in the
[Composer Docs](./docs/composer.md).

#### Updating WordPress

To adjust the installed version of WordPress, run `composer config extra.wordpress-version <new-version>` and then
`composer install-wordpress`.

### NPM Packages, Scripts & Building Frontend Assets

NPM is used for managing frontend dependencies and npm scripts for managing the frontend assets. Learn more about the
available scripts and how to use them in the [NPM Docs](./docs/npm.md).

### 1Password CLI

The 1Password CLI can be used to automate the creation of the `auth.json` file for composer. This file is used to store
credentials used by composer to install paid plugins like Advanced Custom Fields Pro and Gravity Forms. See the
[1Password CLI Docs](./docs/1password-cli.md) for further details.

### GitHub Actions

We use GitHub Action as a CI for deployments, testing and many other features. Take a look at the
[GitHub Action Docs](./docs/actions.md) to learn more about each action.

### Additional Documentation

Specific features and functionality may have additional documentation in the [./docs](./docs) folder.

* [Composer](./docs/composer.md)
* [NPM Packages, Scripts & Building Assets](./docs/npm.md)
* [1Password CLI](./docs/1password-cli.md)
* [GitHub Actions](./docs/actions.md)
* [PHP Tests](./docs/php-tests.md)
* [WordPress Multisite](./docs/multisite.md)
* [Create Block Script Templates](./docs/block-templates.md)
* [Create WP Controls Script](./docs/create-wp-controls-script.md)
* [Supported Block Features](./docs/supported-block-features.md)

## Modern Tribe

[![Modern Tribe](https://moderntribeagency.com/wp-content/uploads/2025/06/github-banner.webp)](https://moderntribeagency.com/contact/)
