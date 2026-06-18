#!/bin/bash
# Simple bash script to check the current version of WordPress and update it if necessary.

set -eu
set -o pipefail

# In Dokku environments, WP-CLI isn't pre-installed — install it if missing.
if [[ -n "${HEROKUISH_VERSION:-}" ]] && [[ "${DOMAIN_CURRENT_SITE:-}" == *"d1.moderntribe.qa" ]]; then
    if command -v wp &> /dev/null; then
        echo "WP-CLI already installed. Skipping install."
    else
        echo "Installing WP-CLI for Dokku..."
        mkdir -p "${HOME}/.heroku/wp/bin/"
        curl --retry 2 --silent --max-time 60 --location https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o "${HOME}/.heroku/wp/bin/wp"
        chmod +x "${HOME}/.heroku/wp/bin/wp"
        export PATH=$PATH:$HOME/.heroku/wp/bin
        which wp
        echo "WP-CLI installed."
    fi
fi

CURRENT_VERSION=$(wp core version)
REQUESTED_VERSION=$(composer config extra.wordpress-version)

if [ "$CURRENT_VERSION" == "$REQUESTED_VERSION" ]; then
    echo "WordPress is already at version $REQUESTED_VERSION. Skipping install."
    exit 0
fi

echo "Updating WordPress to version $REQUESTED_VERSION..."
wp core download --version=$REQUESTED_VERSION --skip-content --force

exit 0
