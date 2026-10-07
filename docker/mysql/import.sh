#!/bin/sh
# Import a phpMyAdmin (or mysqldump) SQL file into the running MySQL container.
# Usage, from the project root:
#   ./docker/mysql/import.sh /path/to/elie_badawi.sql
set -e

cd "$(dirname "$0")/../.."

dump="$1"
if [ -z "$dump" ] || [ ! -f "$dump" ]; then
  echo "Usage: ./docker/mysql/import.sh /path/to/dump.sql" >&2
  exit 1
fi

docker compose --env-file compose.env exec -T mysql \
  sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$dump"

echo "Imported $dump"
