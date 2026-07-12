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

# `docker compose` doit être lancé depuis la racine du dépôt (c'est là que vit
# docker-compose.yml). Se placer explicitement ici pour que le script
# fonctionne quel que soit le répertoire courant de l'appelant.
cd "$(git -C "$(dirname "${BASH_SOURCE[0]}")" rev-parse --show-toplevel)"

BASE_DE_TEST="promeb_test"
BASE_DE_DEV="my_event_app"

if [ "$BASE_DE_TEST" = "$BASE_DE_DEV" ]; then
    echo "ERREUR : la base de test ne peut pas être la base de développement (${BASE_DE_DEV})." >&2
    echo "RefreshDatabase détruirait vos données. Abandon." >&2
    exit 1
fi

# Garde-fou réel : la suite utilise RefreshDatabase, qui DÉTRUIT et recrée les
# tables. On ne peut pas se fier à la comparaison de deux constantes bash, ni
# au fait d'avoir *demandé* DB_DATABASE=promeb_test via `-e` — un
# bootstrap/cache/config.php périmé fait passer cette variable au second plan
# et Laravel résout alors la base figée dans le cache (potentiellement
# my_event_app, la base de développement). La seule vérification qui vaille
# est de demander à Laravel, depuis l'intérieur du container, quelle base il
# résout *effectivement*.
echo "→ Vérification de la base effectivement résolue par Laravel…"
BASE_EFFECTIVE=$(docker compose exec -T -e HOME=/tmp -e DB_CONNECTION=mysql -e DB_DATABASE="$BASE_DE_TEST" app \
    php artisan tinker --execute='echo config("database.connections.mysql.database");' | tr -d '\r\n')

if [ "$BASE_EFFECTIVE" != "$BASE_DE_TEST" ]; then
    echo "ERREUR : Laravel résout la base « ${BASE_EFFECTIVE} » au lieu de « ${BASE_DE_TEST} »." >&2
    echo "Un config cache (bootstrap/cache/config.php) masque probablement les variables d'environnement." >&2
    echo "Lancez : docker compose exec app php artisan config:clear" >&2
    exit 1
fi

echo "→ Préparation de la base de test « ${BASE_DE_TEST} »…"
docker compose exec -T db mysql -uroot -prootpassword \
    -e "CREATE DATABASE IF NOT EXISTS ${BASE_DE_TEST}; GRANT ALL PRIVILEGES ON ${BASE_DE_TEST}.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" \
    2> >(grep -v '^mysql: \[Warning\] Using a password on the command line interface can be insecure\.$' >&2)

echo "→ Exécution de la suite sur MySQL…"
docker compose exec -T \
    -e HOME=/tmp \
    -e DB_CONNECTION=mysql \
    -e DB_HOST=db \
    -e DB_PORT=3306 \
    -e DB_DATABASE="${BASE_DE_TEST}" \
    -e DB_USERNAME=laravel \
    -e DB_PASSWORD=secret \
    app php artisan test "$@"
