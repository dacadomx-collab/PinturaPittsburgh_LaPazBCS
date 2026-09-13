#!/usr/bin/env bash
# Instancia aislada autorizada para pruebas locales. No usa el servicio del sistema.
set -euo pipefail
project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
db_dir="$project_root/logs/local-mariadb"
case "${1:-start}" in
  start)
    command -v mariadbd >/dev/null
    command -v mariadb-install-db >/dev/null
    mkdir -p "$db_dir"
    chmod 700 "$db_dir"
    if [ ! -d "$db_dir/data/mysql" ]; then
      mariadb-install-db --no-defaults --datadir="$db_dir/data" \
        --auth-root-authentication-method=normal --skip-test-db > "$db_dir/install.log" 2>&1
    fi
    exec mariadbd --no-defaults --datadir="$db_dir/data" \
      --socket="$db_dir/server.sock" --pid-file="$db_dir/server.pid" \
      --log-error="$db_dir/server.log" --bind-address=127.0.0.1 --port=3307 \
      --skip-name-resolve --local-infile=0
    ;;
  stop)
    exec mariadb-admin --no-defaults --protocol=SOCKET --socket="$db_dir/server.sock" -u root shutdown
    ;;
  *) echo 'Uso: bash scripts/local_database.sh start|stop' >&2; exit 1 ;;
esac
