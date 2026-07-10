#!/bin/bash
#
# Script de déploiement ProMeb — exécuté sur le VPS Infine par le workflow CD (SSH).
# Prérequis sur le VPS : un fichier .env de production présent dans APP_DIR
# (voir .env.production.example pour les variables attendues).
#
set -euo pipefail

APP_DIR="/var/www/ProMeb"
COMPOSE="docker compose -f docker-compose.production.yml"
APP_CONTAINER="promeb_app"

cd "$APP_DIR"

echo "▶ 1/8 — Récupération du code"
git pull origin main

echo "▶ 2/8 — Build des images"
$COMPOSE build

echo "▶ 3/8 — Démarrage des containers"
$COMPOSE up -d

echo "▶ 4/8 — Injection du .env dans le container Laravel"
docker cp .env "${APP_CONTAINER}:/var/www/html/.env"
docker exec -u root "$APP_CONTAINER" chown www-data:www-data /var/www/html/.env

echo "▶ 5/8 — Nettoyage du cache de config (valeurs potentiellement obsolètes)"
$COMPOSE exec -T "$APP_CONTAINER" php artisan config:clear

echo "▶ 6/8 — Attente de la disponibilité de MySQL"
until $COMPOSE exec -T "$APP_CONTAINER" php artisan db:show > /dev/null 2>&1; do
    echo "   …MySQL indisponible, nouvelle tentative dans 3s"
    sleep 3
done

echo "▶ 7/8 — Génération de la clé applicative (première fois uniquement)"
if ! grep -q "^APP_KEY=base64:" .env; then
    APP_KEY=$($COMPOSE exec -T "$APP_CONTAINER" php artisan key:generate --show)
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    docker cp .env "${APP_CONTAINER}:/var/www/html/.env"
    docker exec -u root "$APP_CONTAINER" chown www-data:www-data /var/www/html/.env
fi

echo "   → Migrations"
$COMPOSE exec -T "$APP_CONTAINER" php artisan migrate --force

echo "   → Permissions storage / bootstrap cache"
docker exec -u root "$APP_CONTAINER" chown -R www-data:www-data storage bootstrap/cache

echo "▶ 8/8 — Mise en cache Laravel (après .env complet + migrations)"
$COMPOSE exec -T "$APP_CONTAINER" php artisan config:cache
$COMPOSE exec -T "$APP_CONTAINER" php artisan route:cache
$COMPOSE exec -T "$APP_CONTAINER" php artisan view:cache

echo "✅ Déploiement terminé"
