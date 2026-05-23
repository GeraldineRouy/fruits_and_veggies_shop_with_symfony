#!/bin/sh
set -e

DB_HOST="${DB_HOST:-database}"
DB_PORT="${DB_PORT:-5432}"
DB_USER="${DB_USER:-app}"
TIMEOUT=60
ELAPSED=0

# Install composer dependencies if vendor is missing
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
    echo "Composer dependencies installed."
fi

echo "Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."

while ! pg_isready -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" > /dev/null 2>&1; do
    ELAPSED=$((ELAPSED + 1))
    if [ "${ELAPSED}" -ge "${TIMEOUT}" ]; then
        echo "ERROR: PostgreSQL not reachable after ${TIMEOUT} seconds. Aborting."
        exit 1
    fi
    sleep 1
done

echo "PostgreSQL is ready."

# Clean stale lock files
rm -f var/cache/*/*.php.lock

echo "Starting FrankenPHP..."
exec frankenphp run --config /app/Caddyfile 2>&1
