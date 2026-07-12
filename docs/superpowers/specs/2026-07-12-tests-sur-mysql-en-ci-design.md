# Faire tourner la suite de tests sur MySQL en CI

Date : 2026-07-12

## Problème

`phpunit.xml` fait tourner la suite sur **SQLite en mémoire** ; la production est
sur **MySQL**. Les deux moteurs ne traitent pas les cascades ni les contraintes
d'intégrité de la même façon.

**Cette divergence a déjà laissé passer deux bugs réels**, dans une seule branche
(PR #22, 2026-07-12) :

1. Une migration passant `prestations.client_id` / `taux_horaire_id` en `RESTRICT`
   cassait la suppression d'un `User` **sur MySQL** (`SQLSTATE 23000 / 1451` :
   InnoDB supprime la ligne `clients` avant la ligne `prestations` qui la
   référence). Sur SQLite, l'ordre de résolution diffère et le test passait — un
   faux négatif structurel.
2. Le correctif de ce bug réintroduisait une perte de données, et là encore le
   test SQLite était vert.

Les deux n'ont été trouvés qu'en exerçant la vraie base MySQL à la main. **La CI
est aveugle à cette famille de bugs**, et c'est précisément la famille qui détruit
des données.

## Décisions

**La CI tourne sur MySQL ; le développement local reste sur SQLite.** Trois options
ont été pesées :

- *MySQL partout, y compris en local* — plus aucune divergence, mais chaque
  lancement passe de 1,4 s à 9,3 s et exige que Docker tourne. Alourdit
  l'itération, notamment sur le front.
- *Les deux moteurs en CI* — double le temps de CI pour un bénéfice quasi nul :
  c'est MySQL qui compte, SQLite n'est qu'une commodité de développement.
- **Retenu : CI sur MySQL, local sur SQLite.** La CI devient le filet fidèle à la
  production ; l'itération locale reste instantanée.

**Conséquence assumée** : un test peut passer en local et casser en CI. C'est le
rôle du filet, et il bloque avant le merge.

**Aucune modification de `phpunit.xml`.** Vérifié : les variables d'environnement
du système **priment** sur les `<env>` de `phpunit.xml`. Lancer la suite avec
`DB_CONNECTION=mysql …` la fait tourner sur MySQL sans toucher au fichier, qui
continue de déclarer SQLite pour le développement local. C'est ce qui rend le
changement petit.

**Vérifié au préalable, et c'est ce qui débloque le sujet** : les **88 tests
passent déjà sur MySQL** (9,3 s contre 1,4 s sur SQLite). Aucun test n'est à
réparer.

## Conception

### Le workflow

`.github/workflows/ci.yml`, job `tests` :

- Un **service `mysql:8.0`**, avec un *healthcheck* (`mysqladmin ping`). Sans lui,
  les tests attaqueraient une base pas encore prête — la cause classique d'une CI
  qui échoue une fois sur trois.
- L'extension **`pdo_mysql`** ajoutée à la liste installée par `setup-php`
  (`pdo_sqlite` reste : elle ne coûte rien et sert si un test la demande un jour).
- Le step d'exécution reçoit les variables `DB_CONNECTION`, `DB_HOST`, `DB_PORT`,
  `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` pointant sur le service.

La base de test du service porte un nom dédié (`promeb_test`) : la suite utilise
`RefreshDatabase`, qui détruit et recrée les tables à chaque exécution.

### Le script local

`bin/test-mysql.sh` encapsule la commande Docker qui lance la suite contre la base
MySQL du container de développement, dans une **base séparée** (`promeb_test`), et
la crée si elle n'existe pas.

Il ne doit jamais pointer sur la base de développement (`my_event_app`) :
`RefreshDatabase` effacerait les données de travail. Le script doit donc refuser de
tourner si le nom de base n'est pas celui de test — une garde explicite, pas une
convention.

### La documentation

`docs/tests.md`, court : quelle commande lance quoi, et **quand** utiliser MySQL en
local — dès qu'on touche aux cascades, aux contraintes d'intégrité ou aux
suppressions. C'est là que SQLite ment, et c'est la seule chose que le lecteur doit
retenir.

## Tests

Le livrable est une configuration : sa vérification passe par l'exécution.

- La CI de la PR doit être **verte**, et son log doit montrer que la suite a bien
  tourné sur **MySQL** — pas sur SQLite. Le temps d'exécution le trahit (≈ 9 s
  contre 1,4 s), mais ce n'est pas une preuve suffisante : le workflow affichera
  explicitement la connexion utilisée (`php artisan db:show` avant les tests, ou
  équivalent).
- **La preuve que le filet fonctionne** : la CI doit échouer si les tests tournent
  sur un moteur qui ment. Concrètement, on vérifie que le job échoue quand la base
  MySQL n'est pas joignable, plutôt que de retomber silencieusement sur SQLite.
- `bin/test-mysql.sh` doit tourner et donner 88 tests verts, sans altérer la base
  de développement (vérifier son contenu avant / après).

## Hors périmètre

- Faire tourner le développement local sur MySQL.
- Le workflow de déploiement (`deploy.yml`), inchangé.
- Les tests front (le projet n'en a pas).
