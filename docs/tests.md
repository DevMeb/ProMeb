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
