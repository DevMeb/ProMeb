#!/usr/bin/env bash
#
# Lance la suite de tests sur MySQL — le moteur de la production.
#
# Par défaut, `php artisan test` tourne sur SQLite en mémoire (rapide, mais il
# résout les cascades et les contraintes d'intégrité autrement que MySQL : il a
# déjà laissé passer deux bugs de perte de données). Utilisez ce script dès que
# vous touchez aux cascades, aux contraintes d'intégrité ou aux suppressions.
#
# Nécessite que le container de développement tourne (`docker compose up -d`).

set -euo pipefail

BASE_DE_TEST="promeb_test"
BASE_DE_DEV="my_event_app"

# Garde-fou : la suite utilise RefreshDatabase, qui DÉTRUIT et recrée les tables.
# La pointer sur la base de développement effacerait les données de travail.
if [ "$BASE_DE_TEST" = "$BASE_DE_DEV" ]; then
    echo "ERREUR : la base de test ne peut pas être la base de développement (${BASE_DE_DEV})." >&2
    echo "RefreshDatabase détruirait vos données. Abandon." >&2
    exit 1
fi

echo "→ Préparation de la base de test « ${BASE_DE_TEST} »…"
docker compose exec -T db mysql -uroot -prootpassword \
    -e "CREATE DATABASE IF NOT EXISTS ${BASE_DE_TEST}; GRANT ALL PRIVILEGES ON ${BASE_DE_TEST}.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" 2>/dev/null

echo "→ Exécution de la suite sur MySQL…"
docker compose exec -T \
    -e HOME=/tmp \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=db \
    -e DB_PORT=3306 \
    -e DB_DATABASE="${BASE_DE_TEST}" \
    -e DB_USERNAME=laravel \
    -e DB_PASSWORD=secret \
    app php artisan test --testsuite=Feature "$@"
