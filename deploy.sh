#!/bin/bash
#
# Script de déploiement ProMeb — exécuté sur le VPS Infine par le workflow CD (SSH).
# Prérequis : un .env de production présent dans APP_DIR (voir .env.production.example).
#
set -euo pipefail

APP_DIR="/var/www/ProMeb"
COMPOSE="docker compose -f docker-compose.production.yml"
APP_CONTAINER="promeb_app"   # nom du service ET du container dans le compose de prod

cd "$APP_DIR"

echo "▶ 1/9 — Récupération du code"
git pull origin main

echo "▶ 2/9 — Build des images"
$COMPOSE build

echo "▶ 3/9 — Démarrage des containers"
$COMPOSE up -d

echo "▶ 4/9 — Injection du .env (container en www-data → chown via root)"
docker cp .env "${APP_CONTAINER}:/var/www/html/.env"
docker exec -u root "$APP_CONTAINER" chown www-data:www-data /var/www/html/.env

echo "▶ 5/9 — Nettoyage du cache de config (valeurs potentiellement obsolètes)"
$COMPOSE exec -T "$APP_CONTAINER" php artisan config:clear

echo "▶ 6/9 — Attente de MySQL (bornée à ~2 min ; set -e n'interrompt pas un until)"
for i in $(seq 1 40); do
    if $COMPOSE exec -T "$APP_CONTAINER" php artisan db:show > /dev/null 2>&1; then
        break
    fi
    if [ "$i" -eq 40 ]; then
        echo "Erreur : base de données injoignable après 2 min (vérifier le .env)." >&2
        exit 1
    fi
    echo "   …base indisponible ($i/40)"
    sleep 3
done

echo "▶ 7/9 — Clé applicative (première fois uniquement)"
if ! grep -q "^APP_KEY=base64:" .env; then
    APP_KEY=$($COMPOSE exec -T "$APP_CONTAINER" php artisan key:generate --show)
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    docker cp .env "${APP_CONTAINER}:/var/www/html/.env"
    docker exec -u root "$APP_CONTAINER" chown www-data:www-data /var/www/html/.env
fi

echo "   → Lien de stockage public"
$COMPOSE exec -T "$APP_CONTAINER" php artisan storage:link 2>/dev/null || true

# Pas de db:seed : le DatabaseSeeder de ProMeb n'est pas idempotent (crée un
# utilisateur de test). Les migrations suffisent en production.
echo "   → Migrations"
$COMPOSE exec -T "$APP_CONTAINER" php artisan migrate --force

echo "▶ 8/9 — Purge du cache applicatif (entrées périmées du déploiement précédent)"
$COMPOSE exec -T "$APP_CONTAINER" php artisan cache:clear

echo "   → Permissions storage / bootstrap cache"
docker exec -u root "$APP_CONTAINER" chown -R www-data:www-data storage bootstrap/cache

echo "▶ 9/9 — Mise en cache Laravel (après .env complet + migrations)"
$COMPOSE exec -T "$APP_CONTAINER" php artisan config:cache
$COMPOSE exec -T "$APP_CONTAINER" php artisan route:cache
$COMPOSE exec -T "$APP_CONTAINER" php artisan view:cache

echo "✅ Déploiement terminé avec succès."
