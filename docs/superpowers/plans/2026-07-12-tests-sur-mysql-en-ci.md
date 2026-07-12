# Tests sur MySQL en CI — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Faire tourner la suite de tests sur MySQL en CI — le moteur de la production — au lieu de SQLite, qui a déjà laissé passer deux bugs de cascade.

**Architecture :** Le workflow GitHub Actions gagne un service `mysql:8.0` et pointe les tests dessus via des variables d'environnement. `phpunit.xml` n'est pas touché : les variables du système priment sur ses `<env>`, donc le développement local continue de tourner sur SQLite, instantanément. Un script encapsule la commande pour lancer la suite sur MySQL en local quand c'est nécessaire.

**Tech Stack :** GitHub Actions, MySQL 8.0, PHP 8.4, Pest, Docker Compose (dev).

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-12-tests-sur-mysql-en-ci-design.md`
- **Ne PAS modifier `phpunit.xml`.** Vérifié : les variables d'environnement du système priment sur ses `<env>`. Le fichier continue de déclarer SQLite (pour le local) et la CI le surcharge sans y toucher.
- **Vérifié au préalable : les 88 tests passent déjà sur MySQL** (9,3 s, contre 1,4 s sur SQLite). Aucun test n'est à réparer. Si un test échoue sur MySQL, c'est que quelque chose a été cassé — ne le contourne pas, signale-le.
- La base de test porte un nom **dédié** (`promeb_test`). La suite utilise `RefreshDatabase`, qui **détruit et recrée les tables** : pointer sur la base de développement (`my_event_app`) effacerait les données de travail (30 prestations, 5 factures).
- Une CI verte ne suffit pas comme preuve : si la connexion retombait silencieusement sur SQLite, elle serait verte **et** aveugle — la situation qu'on veut quitter. Le workflow doit **afficher la connexion réellement utilisée**.
- Identifiants MySQL du container de dev (`docker-compose.yml`) : base `my_event_app`, utilisateur `laravel`, mot de passe `secret`, root `rootpassword`. Le service `db`, l'applicatif `app`.
- Commits en français, format `type: description`.

---

### Task 1 : La CI tourne sur MySQL

**Files:**
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: rien.
- Produces: le job `tests` s'exécute contre un service MySQL. La tâche 2 fournit l'équivalent en local.

- [ ] **Step 1: Ajouter le service MySQL et pointer les tests dessus**

Remplacer le contenu de `.github/workflows/ci.yml` par :

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  tests:
    name: Tests (Pest)
    runs-on: ubuntu-latest

    # La suite tourne sur MySQL, le moteur de la production.
    # SQLite (utilisé en local pour la vitesse) résout les cascades et les
    # contraintes d'intégrité différemment : il a déjà laissé passer deux bugs
    # de perte de données (cf. PR #22).
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: promeb_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h 127.0.0.1 -uroot -proot"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=10

    env:
      DB_CONNECTION: mysql
      DB_HOST: 127.0.0.1
      DB_PORT: 3306
      DB_DATABASE: promeb_test
      DB_USERNAME: root
      DB_PASSWORD: root

    steps:
      - name: Checkout du code
        uses: actions/checkout@v4

      - name: Installation de PHP 8.4
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo, pdo_mysql, pdo_sqlite, mbstring, zip, exif, pcntl, gd, bcmath, intl
          coverage: none

      - name: Cache des dépendances Composer
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Installation des dépendances Composer
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Préparation de l'environnement
        run: |
          cp .env.example .env
          php artisan key:generate

      # Preuve que la suite tourne bien sur MySQL. Une CI verte ne suffit pas :
      # si la connexion retombait sur SQLite, elle serait verte ET aveugle.
      - name: Vérification de la connexion à la base
        run: php artisan db:show

      - name: Exécution des tests
        run: php artisan test --testsuite=Feature
```

Points de conception, à ne pas altérer :

- Le **healthcheck** du service (`mysqladmin ping`) : sans lui, les tests attaquent une base pas encore prête, et la CI échoue une fois sur trois. `--health-retries=10` laisse à MySQL 8 le temps de démarrer (il est lent au premier boot).
- Le step **`php artisan db:show`** avant les tests : il affiche la connexion et la base réellement utilisées. C'est la preuve exigée par la spec — sans lui, on ne saurait pas distinguer une CI qui teste MySQL d'une CI qui est retombée sur SQLite. Il joue aussi le rôle de garde-fou : si MySQL n'est pas joignable, `db:show` **échoue et fait tomber le job**, au lieu de laisser les tests démarrer sur un autre moteur. La CI ne peut donc pas être verte et aveugle.
- `DB_HOST` vaut **`127.0.0.1`**, pas `mysql` : le job tourne directement sur le runner (pas dans un container), et le service est exposé sur le port `3306` de l'hôte.
- `pdo_sqlite` reste installé : il ne coûte rien et sert si un test le demande un jour.

- [ ] **Step 2: Valider la syntaxe YAML localement**

Run: `python3 -c "import yaml,sys; yaml.safe_load(open('.github/workflows/ci.yml')); print('YAML valide')"`
Expected: `YAML valide`. Une erreur d'indentation dans un workflow ne se voit qu'une fois poussé — autant l'attraper maintenant.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/ci.yml
git commit -m "ci: fait tourner la suite de tests sur MySQL"
```

> La vérification réelle de cette tâche se fait **sur la PR** : la CI doit être verte, et le log du step « Vérification de la connexion à la base » doit montrer `mysql` (et non `sqlite`). Le contrôleur du plan s'en assurera après l'ouverture de la PR — c'est indiqué dans la vérification finale.

---

### Task 2 : Lancer la suite sur MySQL en local

**Files:**
- Create: `bin/test-mysql.sh`
- Create: `docs/tests.md`

**Interfaces:**
- Consumes: rien (indépendant de la tâche 1).
- Produces: `bin/test-mysql.sh`, exécutable, qui lance la suite contre la base MySQL du container de développement.

- [ ] **Step 1: Écrire le script**

Créer `bin/test-mysql.sh` :

```bash
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
```

Notes :

- `-e HOME=/tmp` est nécessaire : sans un HOME inscriptible, les commandes artisan
  échouent dans ce container.
- `DB_HOST=db` (et non `127.0.0.1`) : le script exécute la commande **dans** le
  container `app`, qui joint la base par le réseau Docker.
- `"$@"` transmet les arguments : `bin/test-mysql.sh tests/Feature/MonTest.php`
  fonctionne.
- La garde comparant les deux noms de base peut sembler tautologique puisque les
  deux valeurs sont écrites juste au-dessus — c'est voulu : elle protège contre
  la modification distraite d'une seule des deux constantes.

- [ ] **Step 2: Rendre le script exécutable**

Run: `chmod +x bin/test-mysql.sh`

- [ ] **Step 3: Vérifier que le script tourne, et qu'il ne touche pas la base de dev**

D'abord, relever l'état de la base de développement :

Run: `docker compose exec -T -e HOME=/tmp app php artisan tinker --execute="echo 'prestations: ' . App\Models\Prestation::count() . ' | factures: ' . App\Models\Facture::count();"`
Expected: affiche le nombre de prestations et de factures (attendu : 30 et 5). **Note ces valeurs.**

Puis lancer le script :

Run: `./bin/test-mysql.sh`
Expected: **88 tests verts**, en ~10 s (nettement plus lent que les 1,4 s de SQLite — c'est le signe que MySQL est bien utilisé).

Puis re-vérifier la base de développement :

Run: `docker compose exec -T -e HOME=/tmp app php artisan tinker --execute="echo 'prestations: ' . App\Models\Prestation::count() . ' | factures: ' . App\Models\Facture::count();"`
Expected: **exactement les mêmes valeurs qu'avant**. Si elles ont changé, le script a détruit les données de développement : c'est un échec grave, arrête-toi et signale-le.

- [ ] **Step 4: Écrire la documentation**

Créer `docs/tests.md` :

```markdown
# Lancer les tests

## Par défaut — SQLite en mémoire (rapide)

```bash
php artisan test --testsuite=Feature
```

Environ 1,4 s. C'est ce que fait `phpunit.xml`, et c'est ce qu'il faut pour itérer.

> `php artisan test` **sans** `--testsuite=Feature` échoue : `phpunit.xml` déclare
> une suite `Unit` alors que `tests/Unit/` n'existe pas.

## Sur MySQL — le moteur de la production (fidèle)

```bash
./bin/test-mysql.sh
```

Environ 10 s. Nécessite que le container de développement tourne
(`docker compose up -d`). La suite s'exécute contre une base dédiée
(`promeb_test`), jamais contre la base de développement.

**Utilisez-le dès que vous touchez aux cascades, aux contraintes d'intégrité ou
aux suppressions.** SQLite et MySQL ne les résolvent pas de la même façon, et
c'est précisément là que SQLite ment : il a déjà laissé passer deux bugs de perte
de données (cf. PR #22), que seule une exécution sur MySQL a révélés.

## En intégration continue

La CI tourne **sur MySQL** — voir `.github/workflows/ci.yml`. Un test peut donc
passer en local (SQLite) et casser en CI (MySQL) : c'est le rôle du filet, et il
bloque avant le merge.
```

- [ ] **Step 5: Commit**

```bash
git add bin/test-mysql.sh docs/tests.md
git commit -m "test: script pour lancer la suite sur MySQL en local"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — 88 tests verts (SQLite, inchangé).
- [ ] `./bin/test-mysql.sh` — 88 tests verts (MySQL), et la base de développement est intacte.
- [ ] **Sur la PR** : la CI est verte, et le log du step « Vérification de la connexion à la base » affiche bien `mysql` — pas `sqlite`. C'est la preuve que le filet est réellement en place ; sans elle, on aurait une CI verte et toujours aveugle.
