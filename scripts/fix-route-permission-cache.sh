#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f app/Http/Middleware/RoutePermissionMiddleware.php ]]; then
  echo "Missing app/Http/Middleware/RoutePermissionMiddleware.php" >&2
  exit 1
fi

if ! grep -q "'route.permission' => RoutePermissionMiddleware::class" bootstrap/app.php; then
  echo "Missing route.permission alias in bootstrap/app.php" >&2
  exit 1
fi

composer dump-autoload
php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan route:list
