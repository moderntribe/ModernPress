#!/bin/bash
# Simple bash script to check the current version of WordPress and update it if necessary.

set -eu
set -o pipefail

CURRENT_VERSION=$(wp core version 2>/dev/null || echo "")
REQUESTED_VERSION=$(composer config extra.wordpress-version)

if [ "$CURRENT_VERSION" == "$REQUESTED_VERSION" ]; then
    echo "WordPress is already at version $REQUESTED_VERSION. Skipping install."
    exit 0
fi

echo "Installing WordPress version $REQUESTED_VERSION..."
wp core download --version=$REQUESTED_VERSION --skip-content --force

exit 0
