# Lot de nettoyage : logs observables, message d'erreur, Navbar — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rendre les erreurs métier visibles dans Dozzle en production, corriger un message d'erreur trompeur, et faire enfin afficher le nom de l'utilisateur (réactif) dans la Navbar.

**Architecture :** Quatre corrections indépendantes. La principale : les canaux de log custom (`prestation`, `facture`, `client`), aujourd'hui figés en `driver => single` (fichiers invisibles en prod), suivent désormais le canal par défaut de l'environnement — donc `stderr` en prod. Un réglage FPM garantit que la sortie des workers remonte au container. Plus deux corrections isolées (message d'erreur, Navbar).

**Tech Stack :** Laravel 12 (PHP 8.4), Vue 3 + Pinia, Docker (php:8.4-fpm), Pest, Vitest.

## Global Constraints

- **Le cœur du lot : les erreurs des services doivent devenir visibles dans Dozzle (`docker logs`) en production.** Aujourd'hui `BaseService::handleExceptions` journalise via `Log::channel('facture'|'prestation'|'client')`, et ces canaux écrivent dans `storage/logs/*.log` — invisibles dans `docker logs`. Le `LOG_CHANNEL=stderr` de la prod ne les touche pas (il ne change que le canal *par défaut*).
- **Aucun changement de comportement en local** : en développement (`LOG_CHANNEL=stack`), les canaux custom doivent continuer d'écrire dans des fichiers. Seule la prod (`LOG_CHANNEL=stderr`) change.
- **Navbar** : afficher `user.name` (renvoyé par l'API) à la place de `user.avatar` (champ inexistant, toujours l'avatar par défaut). `user` et `isAuthenticated` doivent passer par `storeToRefs` (aujourd'hui déstructurés directement → non réactifs) ; `logout` reste une fonction déstructurée directement.
- Le projet n'a pas de tests de composants : la Navbar se vérifie par `npm run build` + contrôle manuel. Le reste (message d'erreur, config de log) se vérifie en Pest et par lecture de config.
- Tests : `php artisan test --testsuite=Feature` (96 verts au départ) et `npm run test` (53 verts). **Jamais `php artisan test` sans `--testsuite=Feature`.**
- Commits en français, format `type: description`.

---

### Task 1 : Les canaux de log custom suivent l'environnement

Le cœur du lot. Aujourd'hui `config/logging.php` déclare `prestation`, `facture`, `client` en `driver => single` (fichiers). On les fait pointer sur une pile qui suit le canal par défaut de l'environnement.

**Files:**
- Modify: `config/logging.php`
- Test: `tests/Feature/LogChannelsTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: les canaux `prestation`, `facture`, `client` résolvent vers le driver de l'environnement (`stderr` en prod, `single`/fichier en local).

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/LogChannelsTest.php` :

```php
<?php

it('les canaux metier ne sont pas figes sur le driver single', function () {
    // Avant : driver 'single' en dur → fichiers invisibles dans docker logs.
    foreach (['prestation', 'facture', 'client'] as $canal) {
        expect(config("logging.channels.$canal.driver"))->not->toBe('single');
    }
});

it('les canaux metier suivent le canal par defaut de l\'environnement', function () {
    // En prod (LOG_CHANNEL=stderr), ils doivent router vers stderr.
    config()->set('logging.default', 'stderr');

    foreach (['prestation', 'facture', 'client'] as $canal) {
        // Le canal métier délègue au canal par défaut : sa cible effective est stderr.
        expect(config("logging.channels.$canal.channels"))->toContain('stderr');
    }
});
```

> Note : le second test suppose que les canaux métier deviennent des **stacks** dont la liste `channels` contient le canal par défaut résolu. C'est le mécanisme de l'étape 3 — si ton implémentation diffère (par ex. un alias direct), adapte l'assertion pour qu'elle prouve la même chose : le canal métier route vers la cible du canal par défaut.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/LogChannelsTest.php`
Expected: FAIL — les canaux ont `driver => single` (le premier test échoue), et pas de clé `channels` (le second échoue).

- [ ] **Step 3: Rediriger les canaux métier vers le canal par défaut**

Dans `config/logging.php`, remplacer les trois blocs `prestation`, `facture`, `client`. Aujourd'hui chacun ressemble à :

```php
        'facture' => [
            'driver' => 'single',
            'path' => storage_path('logs/facture.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
```

Les remplacer par un **stack** qui délègue au canal par défaut de l'environnement :

```php
        'prestation' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_CHANNEL', 'single')),
            'ignore_exceptions' => false,
        ],

        'facture' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_CHANNEL', 'single')),
            'ignore_exceptions' => false,
        ],

        'client' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_CHANNEL', 'single')),
            'ignore_exceptions' => false,
        ],
```

Ainsi : en prod (`LOG_CHANNEL=stderr`), un `Log::channel('facture')->error(...)` route vers `stderr` → `docker logs` → Dozzle. En local (`LOG_CHANNEL=stack` ou `single`), il route vers les fichiers, comportement inchangé.

> Le suffixe `.log` par canal (un fichier séparé par domaine) disparaît. C'est assumé : la séparation par fichier n'a de valeur qu'en local, et `Log::channel('facture')` préfixe déjà chaque ligne du message d'erreur du service — le domaine reste identifiable dans le flux.

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/LogChannelsTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 98 tests (96 + 2).

- [ ] **Step 6: Commit**

```bash
git add config/logging.php tests/Feature/LogChannelsTest.php
git commit -m "fix: les canaux de log metier suivent l'environnement (stderr en prod)"
```

---

### Task 2 : FPM fait remonter la sortie des workers

Garantit que ce que le canal `stderr` écrit (dans un worker PHP-FPM) atteint réellement la sortie du container, donc Dozzle. Sans ce réglage, `catch_workers_output` peut être à `no` selon l'image, et la sortie des workers serait avalée.

**Files:**
- Modify: `Dockerfile.prod`

**Interfaces:**
- Consumes: rien.
- Produces: le container PHP-FPM de prod remonte la sortie de ses workers vers stdout/stderr.

Ce changement n'est **pas testable en CI** (il faut construire l'image et l'exécuter) : il se vérifie par lecture, et surtout au déploiement (voir la vérification finale).

- [ ] **Step 1: Ajouter le réglage à l'étape PHP-FPM du Dockerfile**

Dans `Dockerfile.prod`, dans l'étape `FROM php:8.4-fpm AS php_app` (avant le `CMD ["php-fpm"]`), ajouter une ligne qui active la remontée de la sortie des workers :

```dockerfile
# Fait remonter la sortie des workers (dont le canal de log `stderr` de Laravel)
# vers stdout/stderr du container, pour qu'elle soit visible dans docker logs / Dozzle.
RUN echo "catch_workers_output = yes" > /usr/local/etc/php-fpm.d/zz-docker-logs.conf
```

> On écrit un fichier `.conf` dédié dans `php-fpm.d/` plutôt que d'éditer `www.conf` : c'est additif, lisible, et le préfixe `zz-` garantit qu'il est chargé en dernier (il prime). L'image officielle `php:8.4-fpm` charge tous les `.conf` de ce dossier.

- [ ] **Step 2: Vérifier que le Dockerfile reste syntaxiquement cohérent**

Run: `grep -n "catch_workers_output\|CMD" Dockerfile.prod`
Expected: la ligne `catch_workers_output` apparaît **avant** `CMD ["php-fpm"]`, dans l'étape `php_app`.

> On ne construit pas l'image ici (coûteux, et non requis pour valider le lot). La vérification réelle se fait au déploiement — voir la vérification finale.

- [ ] **Step 3: Commit**

```bash
git add Dockerfile.prod
git commit -m "fix: FPM remonte la sortie des workers vers les logs du container"
```

---

### Task 3 : Le message d'erreur de `paid` dit la vérité

**Files:**
- Modify: `app/Services/FactureService.php`
- Test: `tests/Feature/FacturePaidTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: rien.

`FactureService::paid` journalise « Erreur lors de la **suppression** de la facture » alors qu'il marque une facture payée. En cas d'incident réel, on chercherait au mauvais endroit.

- [ ] **Step 1: Écrire le test qui échoue**

Le message n'est journalisé qu'en cas d'exception dans `paid`. Plutôt que de provoquer un échec réel de base de données (fragile), on force `update()` à lever via un **mock partiel** de `Facture`, et on **espionne le log** (`Log::spy()`) pour capturer le message réellement passé à `error()`. `BaseService::handleExceptions` fait `Log::channel('facture')->error("$errorMessage - ...")` puis relance — le test attrape donc l'exception relancée.

Créer `tests/Feature/FacturePaidTest.php` :

```php
<?php

use App\Models\Facture;
use App\Services\FactureService;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

it('journalise un message de paiement (pas de suppression) quand paid echoue', function () {
    Log::spy();

    // Facture dont l'update lève : on isole l'erreur sans dépendre de la DB.
    $facture = Mockery::mock(Facture::class)->makePartial();
    $facture->id = 42;
    $facture->shouldReceive('update')->andThrow(new RuntimeException('échec simulé'));

    // handleExceptions logge puis relance : on avale l'exception relancée.
    try {
        app(FactureService::class)->paid($facture);
    } catch (RuntimeException $e) {
        // attendu
    }

    // Le canal 'facture' a bien reçu un message parlant de PAIEMENT, pas de suppression.
    Log::shouldHaveReceived('channel')->with('facture');
    Log::shouldHaveReceived('error')->withArgs(function (string $message) {
        return str_contains($message, 'paiement') && ! str_contains($message, 'suppression');
    });
});
```

> `Log::spy()` remplace le logger par un espion : `Log::channel('facture')` renvoie l'espion, et `->error(...)` est enregistré, sans rien écrire nulle part. On assert ensuite sur le message capturé. Aucune dépendance à la base ni à un échec réel — le test est déterministe. Il **ne teste pas** la présence de la chaîne dans le code source (ce serait tautologique) : il exerce le vrai chemin d'erreur de `paid` et vérifie le message effectivement journalisé.

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

Run: `php artisan test --testsuite=Feature tests/Feature/FacturePaidTest.php`
Expected: FAIL — le message loggé contient « suppression », pas « paiement ».

- [ ] **Step 3: Corriger le message**

Dans `app/Services/FactureService.php`, méthode `paid`, remplacer le message d'erreur :

```php
        }, "Erreur lors du paiement de la facture (ID: $facture->id)", "facture");
```

- [ ] **Step 4: Lancer le test et vérifier qu'il passe**

Run: `php artisan test --testsuite=Feature tests/Feature/FacturePaidTest.php`
Expected: PASS.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 99 tests (98 + 1).

- [ ] **Step 6: Commit**

```bash
git add app/Services/FactureService.php tests/Feature/FacturePaidTest.php
git commit -m "fix: le message d'erreur de paid parle de paiement, pas de suppression"
```

---

### Task 4 : La Navbar affiche le nom de l'utilisateur, réactivement

**Files:**
- Modify: `resources/js/components/Navbar.vue`

**Interfaces:**
- Consumes: `user.name` (renvoyé par l'API, exposé par le store auth).
- Produces: rien (dernière tâche).

Aujourd'hui la Navbar : (1) déstructure `{ user, isAuthenticated, logout }` du store **sans `storeToRefs`** → `user` et `isAuthenticated` ne sont pas réactifs ; (2) affiche `user.avatar` — un champ que l'API ne renvoie pas, donc toujours l'avatar par défaut.

- [ ] **Step 1: Rendre user et isAuthenticated réactifs**

Dans `resources/js/components/Navbar.vue`, remplacer la déstructuration directe. Aujourd'hui :

```js
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const { user, isAuthenticated, logout } = authStore;
```

Par :

```js
import { storeToRefs } from "pinia";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const { user, isAuthenticated } = storeToRefs(authStore);
const { logout } = authStore;
```

`user` et `isAuthenticated` sont de l'état réactif → `storeToRefs`. `logout` est une fonction → déstructuration directe.

- [ ] **Step 2: Afficher le nom à la place de l'avatar**

Toujours dans `Navbar.vue`, remplacer la balise `<img … :src="user.avatar || defaultAvatar" …>` (ligne ~38) par l'affichage du nom :

```html
                <span class="flex items-center gap-2 rounded-full bg-gray-800 px-3 py-1.5 text-sm text-white">
                  <span class="sr-only">Menu utilisateur</span>
                  <UserCircleIcon class="size-6 text-gray-300" aria-hidden="true" />
                  <span class="font-medium">{{ user?.name }}</span>
                </span>
```

Ajouter l'import de l'icône en haut du script (le projet utilise déjà `@heroicons/vue/24/outline`) :

```js
import { Bars3Icon, XMarkIcon, UserCircleIcon } from "@heroicons/vue/24/outline";
```

> `user?.name` : l'optional chaining évite un plantage si `user` est momentanément `null` (avant que `checkAuth` n'ait peuplé le store). Une icône générique remplace l'avatar — pas de champ `avatar` inventé.

- [ ] **Step 3: Retirer defaultAvatar s'il devient orphelin**

Vérifier si `defaultAvatar` (l'ancienne image par défaut) est encore utilisé ailleurs dans le fichier :

Run: `grep -n "defaultAvatar" resources/js/components/Navbar.vue`
Expected : s'il n'apparaît plus que dans sa déclaration (import ou `const`), le retirer. S'il sert encore ailleurs, le garder.

- [ ] **Step 4: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi, aucune erreur de compilation Vue (un import manquant — `storeToRefs`, `UserCircleIcon` — casserait ici).

- [ ] **Step 5: Vérifier l'absence de régression front**

Run: `npm run test`
Expected: 53 tests verts (aucun test de composant, mais on confirme que les stores ne sont pas cassés).

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/Navbar.vue
git commit -m "fix: la navbar affiche le nom de l'utilisateur, de maniere reactive"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — 99 tests verts.
- [ ] `npm run test` — 53 tests verts.
- [ ] `npm run build` — passe.
- [ ] Les canaux `prestation`, `facture`, `client` de `config/logging.php` sont des stacks suivant `LOG_CHANNEL`, plus aucun `driver => single` en dur pour eux.
- [ ] Contrôle manuel : dans l'app, le nom de l'utilisateur s'affiche dans la Navbar, et se met à jour après modification du profil (sans recharger la page — c'est le bénéfice combiné du `storeToRefs` ici et du fix de `updateUser` déjà en place).
- [ ] **Rappel de déploiement / vérification en prod** (hors CI) : après déploiement, provoquer une erreur métier (ou consulter Dozzle au démarrage) et **confirmer que les logs des services apparaissent bien dans `docker logs` / Dozzle**. Si rien n'apparaît malgré `catch_workers_output = yes`, vérifier que l'image a bien été reconstruite (le `.conf` n'est ajouté qu'à la construction de `Dockerfile.prod`).
