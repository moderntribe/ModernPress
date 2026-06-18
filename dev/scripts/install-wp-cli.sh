#!/bin/bash

# Simple bash script to check if WP-CLI is installed and install it if necessary.
# This script is designed only to be run in our Dokku environment and no where else.
# All major hosts and our local Lando environment should have WP-CLI installed by default.
# The Heroku PHP buildpack installed PHP, extensions, and immediately runs `composer install`.
# The ModernPress composer requires WP-CLI to check for and install WordPress if needed, but we don't want to rely on
# the WP-CLI bundle as it requires outdated packages and throws errors during use.
# Composer-installing WP-CLI also overrides any host-installed versions of the same.

set -eu           # fail fast
set -o pipefail   # don't ignore exit codes when piping output
# set -x          # enable debugging

if [[ -z "${HEROKUISH_VERSION:-}" ]] || [[ "${DOMAIN_CURRENT_SITE:-}" != *"d1.moderntribe.qa" ]]; then
    echo "-----> Not a Modern Tribe Dokku environment. Skipping WP-CLI install."
    exit 0;
fi

if command -v wp &> /dev/null; then
    echo "-----> WP-CLI already installed. Skipping."
    exit 0;
fi

echo "-----> Installing WP-CLI for Dokku..."

function setup_profile() {
    echo "-----> Setup Binary path"
    mkdir -p "${HOME}/.profile.d"
}

setup_profile

function install_wp_cli() {
    cd "${HOME}"
    echo "Build Directory: ${HOME}"
    mkdir -p "${HOME}/.heroku/wp/bin/"

    echo "-----> Install WP-CLI"
    curl --retry 2 --silent --max-time 60 --location https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o "${HOME}/.heroku/wp/bin/wp"
    chmod +x "$HOME/.heroku/wp/bin/wp"
    "$HOME/.heroku/wp/bin/wp" --info
    export PATH=$PATH:$HOME/.heroku/wp/bin
    echo $PATH
    # echo 'export PATH=$PATH:$HOME/.heroku/wp/bin' > "${HOME}/.profile.d/wp-cli.sh"
}

install_wp_cli

echo "----->"
echo "----->"
echo "-----> WP-CLI Installed."

exit 0;
