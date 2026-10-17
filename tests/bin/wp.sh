#!/bin/bash
# WP-CLI du site Local « Accessibilite ». Surchargeable : WP_CLI=/chemin/vers/commande
if [ -n "$WP_CLI" ]; then
  exec $WP_CLI "$@"
fi
L="$HOME/Library/Application Support/Local"
PHP="$L/lightning-services/php-8.3.11+0/bin/darwin-arm64/bin/php"
export PATH="$L/lightning-services/mysql-8.0.35+4/bin/darwin-arm64/bin:$PATH"
exec "$PHP" -d mysqli.default_socket="$L/run/3eGkTgo2E/mysql/mysqld.sock" -d memory_limit=512M "/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/wp-cli.phar" --path="/Users/julienarcioni/Local Sites/accessibilite/app/public" "$@"
