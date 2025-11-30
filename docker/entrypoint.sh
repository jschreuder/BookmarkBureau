#!/bin/sh
set -e

echo "BookmarkBureau starting..."

# Copy distribution config files to active config if they don't exist
# This allows users to customize configs by mounting volumes
if [ ! -f /var/www/config/env.php ] && [ -f /var/www/config/env.php.dist ]; then
    echo "Initializing env.php for production environment..."
    echo "<?php return \"production\";" > /var/www/config/env.php
fi

if [ ! -f /var/www/config/production.php ] && [ -f /var/www/config/production.php.dist ]; then
    echo "Initializing production.php from distribution file..."
    cp /var/www/config/production.php.dist /var/www/config/production.php
fi

# Ensure var directory exists with correct permissions
mkdir -p /var/www/var/logs /var/www/var/run
chmod 755 /var/www/var
chmod 755 /var/www/var/logs
chmod 755 /var/www/var/run

# Run database migrations if database doesn't exist
if [ ! -f "$DB_PATH" ]; then
    echo "Database not found at $DB_PATH, running migrations..."
    php vendor/bin/phinx migrate -e production
    echo "Database initialized successfully"
else
    echo "Database found at $DB_PATH"
    # Always run migrations to ensure schema is up to date
    echo "Checking for pending migrations..."
    php vendor/bin/phinx migrate -e production
fi

# Initialize rate limit database if it doesn't exist
if [ ! -f "$RATELIMIT_DB_PATH" ]; then
    echo "Initializing rate limit database at $RATELIMIT_DB_PATH..."
    # Rate limit DB doesn't need migrations, it's created on first use
    touch "$RATELIMIT_DB_PATH"
    chmod 644 "$RATELIMIT_DB_PATH"
fi

# Check if users file exists, if not create a default one
USERS_FILE=${USERS_FILE:-/var/www/var/users.json}
if [ ! -f "$USERS_FILE" ]; then
    echo "Creating default users file at $USERS_FILE..."
    echo '[]' > "$USERS_FILE"
    chmod 644 "$USERS_FILE"
    echo "Note: No users configured. Use the API to create users."
fi

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Caddy web server..."
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
