#!/bin/bash
set -euo pipefail

cd /app

# Railway injects PORT; Apache image defaults to 80.
if [ -n "${PORT:-}" ] && [ "${PORT}" != "80" ]; then
  sed -i "s/^Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null \
    || sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf
fi

echo "Running database migrations..."
php yii migrate --interactive=0

echo "Starting Apache..."
exec apache2-foreground
