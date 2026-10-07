#!/bin/sh
# Runs after serversideup's Laravel automations (migrations, storage link, caches).
set -e

if [ "${DEMO_MODE:-false}" = "true" ]; then
    echo "🦁 Beast Mode Motors: seeding demo data (if empty)..."
    php /var/www/html/artisan demo:seed --if-empty --no-interaction
fi
