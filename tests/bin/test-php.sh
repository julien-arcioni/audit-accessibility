#!/bin/bash
# Lance chaque tests/php/*.php (sauf lib.php) ; échoue si un fichier échoue.
cd "$(dirname "$0")/../.." || exit 1
statut=0
for f in tests/php/*.php; do
  [ "$(basename "$f")" = "lib.php" ] && continue
  echo "== $f"
  tests/bin/wp.sh eval-file "$f" || statut=1
done
exit $statut
