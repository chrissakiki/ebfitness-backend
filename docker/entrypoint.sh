#!/bin/sh
set -e

mkdir -p /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads

exec apache2-foreground
