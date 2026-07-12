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
(`promeb_test`), et le script vérifie — avant de lancer quoi que ce soit —
que c'est bien cette base que Laravel résout effectivement, jamais la base de
développement.

**Utilisez-le dès que vous touchez aux cascades, aux contraintes d'intégrité ou
aux suppressions.** SQLite et MySQL ne les résolvent pas de la même façon, et
c'est précisément là que SQLite ment : il a déjà laissé passer deux bugs de perte
de données (cf. PR #22), que seule une exécution sur MySQL a révélés.

### Le garde-fou : vérifier la base effective, pas une variable demandée

`RefreshDatabase` détruit et recrée les tables. Un script naïf qui se
contenterait de comparer deux constantes, ou de supposer que
`-e DB_DATABASE=promeb_test` passé à `docker compose exec` suffit, n'est
**pas** protégé : dès qu'un `bootstrap/cache/config.php` existe dans le
container (généré par exemple par `php artisan config:cache` ou
`php artisan optimize`, une commande banale), Laravel ignore les variables
d'environnement passées à l'exécution et résout la base figée dans ce cache —
potentiellement la base de développement.

`bin/test-mysql.sh` ne suppose donc rien : il demande à Laravel, depuis
l'intérieur du container, quelle base il résout *effectivement*
(`config('database.connections.mysql.database')`) et refuse de continuer si
elle diffère de `promeb_test`. Si ce garde-fou se déclenche, videz le config
cache (`docker compose exec app php artisan config:clear`) et relancez.

## En intégration continue

La CI tourne **sur MySQL** — voir `.github/workflows/ci.yml`. Un test peut donc
passer en local (SQLite) et casser en CI (MySQL) : c'est le rôle du filet, et il
bloque avant le merge.

Le step `db:show` du workflow vérifie la connectivité MySQL, mais tourne dans
un processus séparé de PHPUnit : il ne peut pas détecter un `phpunit.xml` qui
forcerait SQLite (`force="true"` sur `<env name="DB_CONNECTION" value="sqlite"/>`)
et rendrait la CI verte tout en testant silencieusement le mauvais moteur.
`tests/Feature/MoteurBaseTest.php` comble ce trou : il vérifie le moteur
*depuis l'intérieur du processus PHPUnit*, la seule preuve qui vaille. Il ne
s'exécute que si la variable `CI` (définie par GitHub Actions, jamais touchée
par `phpunit.xml`) vaut `true` — il reste donc silencieusement skip en local
sur SQLite.
