# Sécurité serveur de l'authentification — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Durcir la porte d'entrée : rate limiting sur le login, CORS restreint, logout qui ne plante plus, cookie `Secure` en prod, mot de passe d'au moins 12 caractères.

**Architecture :** Cinq corrections indépendantes, du backend et de la config. Le rate limiter est un `RateLimiter` nommé enregistré dans `AppServiceProvider`, appliqué par middleware sur la route de login. Le reste est de la config (CORS lu depuis le `.env`, route logout déplacée, variables d'environnement).

**Tech Stack :** Laravel 12 (PHP 8.4), Pest, Sanctum (session).

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-14-securite-auth-serveur-design.md`
- **Rate limiting : clé `email + IP`**, 5 tentatives par minute. La clé DOIT être construite de façon **identique** dans le limiteur (`AppServiceProvider`) et dans le contrôleur (pour la remise à zéro au succès). Centraliser sa construction dans une seule méthode statique pour qu'il n'existe qu'une définition.
- **La remise à zéro au succès est explicite** : `throttle` incrémente à chaque requête, y compris une connexion réussie. `AuthController::login` appelle `RateLimiter::clear(clé)` après `Auth::attempt` réussi, sinon une connexion valide consommerait un jeton.
- **CORS : origines pilotées par le `.env`** via `FRONTEND_URL` (défaut `APP_URL`). Jamais `['*']` avec `supports_credentials`.
- **Mot de passe : `Password::min(12)`** (longueur seule, pas de complexité imposée) dans `user:create`.
- Le message de blocage du throttle reste celui de Laravel (générique) : le front l'affiche déjà via son traitement d'erreur.
- Tests : Pest, `php artisan test --testsuite=Feature`. **Jamais `php artisan test` sans `--testsuite=Feature`** (la suite `Unit` déclarée dans phpunit.xml pointe sur un dossier qui n'existe pas — préexistant, hors périmètre). En test, `CACHE_STORE=array` : le RateLimiter repart vide à chaque test (instance d'app neuve), donc pas de nettoyage inter-tests nécessaire. Point de départ : 90 tests verts.
- Le cookie `Secure` et HSTS ne sont **pas testables en CI** (variables du `.env` de prod, absentes de l'environnement de test) : vérifiés par lecture du modèle et une note de déploiement.
- Commits en français, format `type: description`.

---

### Task 1 : Rate limiting sur le login

Le cœur du lot. Un limiteur `login` clé `email + IP`, avec remise à zéro au succès.

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Http/Controllers/API/AuthController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/LoginThrottleTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: `AuthController::cleThrottle(Request): string` — la clé `email + IP`, source unique de vérité, réutilisée par le limiteur et par la remise à zéro.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/LoginThrottleTest.php` :

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('bloque la connexion apres 5 tentatives echouees (429 a la 6e)', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-vrai-mot-de-passe'),
    ]);

    // 5 tentatives échouées : refusées (422), mais pas encore bloquées.
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/auth/login', [
            'email'    => 'victime@promeb.fr',
            'password' => 'mauvais',
        ])->assertStatus(422);
    }

    // La 6e est bloquée par le limiteur.
    $this->postJson('/api/auth/login', [
        'email'    => 'victime@promeb.fr',
        'password' => 'mauvais',
    ])->assertStatus(429);
});

it('ne bloque pas une autre adresse IP pour le meme email', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-vrai-mot-de-passe'),
    ]);

    // 5 échecs depuis une première IP.
    for ($i = 1; $i <= 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    // Une IP différente sur le même email n'est pas bloquée : la clé (email+IP) change.
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2'])
        ->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
        ->assertStatus(422);
});

it('une connexion reussie remet le compteur a zero', function () {
    User::factory()->create([
        'email'    => 'victime@promeb.fr',
        'password' => bcrypt('le-bon-mot-de-passe'),
    ]);

    // 4 échecs.
    for ($i = 1; $i <= 4; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }

    // Une réussite doit effacer l'ardoise.
    $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'le-bon-mot-de-passe'])
        ->assertNoContent();

    // Après quoi on doit pouvoir de nouveau échouer 5 fois sans être bloqué :
    // si la remise à zéro n'a pas eu lieu, la 2e de ces tentatives (6e cumulée) serait un 429.
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'victime@promeb.fr', 'password' => 'mauvais'])
            ->assertStatus(422);
    }
});
```

> Note : ces tests exercent l'API réelle. `withServerVariables(['REMOTE_ADDR' => …])` fixe l'IP vue par `$request->ip()`. Le login réussi répond 204 (`assertNoContent`) — c'est ce que fait déjà `AuthController::login`.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/LoginThrottleTest.php`
Expected: FAIL. Le premier test attend un 429 à la 6ᵉ tentative mais reçoit un 422 (aucun throttle). Le troisième échoue aussi (sans remise à zéro explicite, il n'atteint pas le comportement attendu).

- [ ] **Step 3: Enregistrer le limiteur**

Dans `app/Providers/AppServiceProvider.php`, ajouter les imports et définir le limiteur dans `boot()` :

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Controllers\API\AuthController;
```

```php
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(AuthController::cleThrottle($request));
        });
    }
```

- [ ] **Step 4: Centraliser la clé et remettre le compteur à zéro au succès**

Dans `app/Http/Controllers/API/AuthController.php`, ajouter les imports :

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
```

Ajouter la méthode statique — **la seule** définition de la clé — et vider le compteur au succès :

```php
    /**
     * Clé de limitation du login : couple email + IP.
     * Source unique de vérité, utilisée par le limiteur (AppServiceProvider)
     * ET par la remise à zéro ci-dessous. Les deux DOIVENT rester identiques.
     */
    public static function cleThrottle(Request $request): string
    {
        return Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if(Auth::attempt($credentials)) {
            RateLimiter::clear(AuthController::cleThrottle($request));
            $request->session()->regenerate();
            return response()->noContent();
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials are incorrect.'
        ]);
    }
```

> Le message d'erreur reste inchangé (anglais) : sa francisation est dans le lot front, hors périmètre ici.

- [ ] **Step 5: Appliquer le middleware sur la route**

Dans `routes/api.php`, ajouter `throttle:login` à la route de login :

```php
        Route::post('/login', [AuthController::class, 'login'])
            ->name('login')
            ->middleware('throttle:login');
```

- [ ] **Step 6: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/LoginThrottleTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 7: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 93 tests (90 + 3).

- [ ] **Step 8: Commit**

```bash
git add app/Providers/AppServiceProvider.php app/Http/Controllers/API/AuthController.php routes/api.php tests/Feature/LoginThrottleTest.php
git commit -m "feat: limite les tentatives de connexion (throttle par email + IP)"
```

---

### Task 2 : Le logout ne plante plus

**Files:**
- Modify: `routes/api.php`
- Test: `tests/Feature/LogoutTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: rien.

Aujourd'hui la route `logout` est hors du groupe `auth:sanctum`, et `Auth::logout()` sans session lève une erreur 500. La déplacer dans le groupe fait qu'un logout non authentifié reçoit un 401 propre.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/LogoutTest.php` :

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deconnecte un utilisateur authentifie', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/auth/logout')
        ->assertNoContent();
});

it('repond 401 (et non 500) sur un logout sans session', function () {
    $this->postJson('/api/auth/logout')
        ->assertStatus(401);
});
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/LogoutTest.php`
Expected: le second test ÉCHOUE — l'API renvoie un **500** au lieu du 401 attendu (`Auth::logout()` sans session). Le premier peut passer ou échouer selon la config actuelle.

- [ ] **Step 3: Déplacer la route logout dans le groupe protégé**

Dans `routes/api.php`, le groupe `auth` déclare aujourd'hui `login` ET `logout`. Sortir `logout` de ce groupe et la placer dans le groupe `Route::middleware('auth:sanctum')` existant (celui qui protège déjà `user`, `clients`, etc.).

Concrètement :
1. Retirer la ligne `logout` du groupe `Route::prefix('auth')`.
2. À l'intérieur du groupe `Route::middleware('auth:sanctum')->group(function () { … })`, ajouter :

```php
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
```

Le groupe `auth` ne contient alors plus que `login` (avec son `throttle:login`).

> Vérifier que le chemin final reste `/api/auth/logout` (le préfixe `api` vient de `bootstrap/app.php`, `logout` doit garder son segment `auth/`).

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/LogoutTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 95 tests (93 + 2).

- [ ] **Step 6: Commit**

```bash
git add routes/api.php tests/Feature/LogoutTest.php
git commit -m "fix: le logout renvoie 401 au lieu de 500 sans session"
```

---

### Task 3 : CORS restreint aux origines du front

**Files:**
- Modify: `config/cors.php`
- Modify: `.env.example`
- Modify: `.env.production.example`
- Test: `tests/Feature/CorsTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: rien.

`config/cors.php` autorise `['*']` avec `supports_credentials => true`. On restreint aux origines venues du `.env`.

- [ ] **Step 1: Écrire le test qui échoue**

Créer `tests/Feature/CorsTest.php` :

```php
<?php

it('n\'autorise pas le joker comme origine CORS', function () {
    // Une origine unique et explicite, jamais '*' : coupler '*' et
    // supports_credentials laisse n'importe quel site lire les réponses authentifiées.
    expect(config('cors.allowed_origins'))->not->toContain('*');
});

it('reflete uniquement l\'origine configuree, pas une origine etrangere', function () {
    config()->set('cors.allowed_origins', ['https://promeb.example']);

    $reponse = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
        'HTTP_ORIGIN'                         => 'https://evil.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD'  => 'POST',
    ]);

    // L'origine étrangère ne doit PAS être renvoyée dans l'en-tête.
    expect($reponse->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.com');
});
```

> Le second test force une origine autorisée connue, puis vérifie qu'une origine étrangère n'est pas reflétée. Sur `main`, `allowed_origins` valant `['*']`, le premier test échoue déjà.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/CorsTest.php`
Expected: le premier test ÉCHOUE (`allowed_origins` contient `*`).

- [ ] **Step 3: Restreindre les origines dans la config**

Dans `config/cors.php`, remplacer la ligne `allowed_origins` :

```php
    'allowed_origins' => array_filter([env('FRONTEND_URL', env('APP_URL'))]),
```

`array_filter` retire un éventuel `null` (si ni `FRONTEND_URL` ni `APP_URL` n'est défini), pour ne pas produire `[null]`.

- [ ] **Step 4: Documenter la variable dans les deux modèles d'environnement**

Dans `.env.example`, ajouter sous la ligne `APP_URL` :

```
# Origine autorisée pour les requêtes CORS authentifiées (le front).
FRONTEND_URL=http://localhost
```

Dans `.env.production.example`, ajouter près de `SESSION_DOMAIN` / `SANCTUM_STATEFUL_DOMAINS` :

```
FRONTEND_URL=https://promeb.hotel-longchamps.fr
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/CorsTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 6: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 97 tests (95 + 2).

- [ ] **Step 7: Commit**

```bash
git add config/cors.php .env.example .env.production.example tests/Feature/CorsTest.php
git commit -m "fix: restreint CORS a l'origine du front au lieu du joker"
```

---

### Task 4 : Mot de passe d'au moins 12 caractères, et cookie Secure en prod

Deux corrections de config groupées : la politique de mot de passe (testable) et le cookie `Secure` (une variable d'environnement).

**Files:**
- Modify: `app/Console/Commands/CreateUser.php`
- Modify: `.env.production.example`
- Test: `tests/Feature/CreateUserPasswordTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: rien (dernière tâche).

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/CreateUserPasswordTest.php` :

```php
<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuse un mot de passe de moins de 12 caracteres', function () {
    $this->artisan('user:create', [
        'name'     => 'Court',
        'email'    => 'court@promeb.fr',
        'password' => 'court',
    ])->assertFailed();

    expect(User::where('email', 'court@promeb.fr')->exists())->toBeFalse();
});

it('accepte un mot de passe de 12 caracteres ou plus', function () {
    $this->artisan('user:create', [
        'name'     => 'Correct',
        'email'    => 'correct@promeb.fr',
        'password' => 'motdepasse-solide',
    ])->assertExitCode(0);

    expect(User::where('email', 'correct@promeb.fr')->exists())->toBeTrue();
});
```

> `assertFailed()` couvre le cas où la commande s'arrête via `$this->fail(...)`. Si la commande retourne un autre code d'échec, ajuster l'assertion — mais ne pas changer la logique de la commande au-delà de la règle de validation.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/CreateUserPasswordTest.php`
Expected: le premier test ÉCHOUE — un mot de passe de 5 caractères est accepté (`min:2`), donc l'utilisateur est créé.

- [ ] **Step 3: Durcir la règle de validation**

Dans `app/Console/Commands/CreateUser.php`, ajouter l'import :

```php
use Illuminate\Validation\Rules\Password;
```

Remplacer la règle du mot de passe dans le `Validator::make` :

```php
            'password' => ['required', 'string', Password::min(12)],
```

Longueur seule, sans complexité imposée — conforme à la décision de la spec.

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/CreateUserPasswordTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 5: Ajouter SESSION_SECURE_COOKIE au modèle de prod**

Dans `.env.production.example`, ajouter près des autres variables `SESSION_` :

```
# Le cookie de session n'est transmis que sur HTTPS (obligatoire en production).
SESSION_SECURE_COOKIE=true
```

> C'est une variable d'environnement, non testable en CI (qui tourne sans ce `.env`). Elle devra être **reportée dans le vrai `.env` du VPS** au prochain déploiement — c'est noté dans la vérification finale.

- [ ] **Step 6: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 99 tests (97 + 2).

- [ ] **Step 7: Commit**

```bash
git add app/Console/Commands/CreateUser.php .env.production.example tests/Feature/CreateUserPasswordTest.php
git commit -m "fix: mot de passe min 12 caracteres et cookie de session Secure en prod"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — 99 tests verts.
- [ ] `grep -n "allowed_origins" config/cors.php` — lit `FRONTEND_URL`, plus de `['*']`.
- [ ] `grep -rn "throttle:login" routes/api.php` — présent sur la route de login.
- [ ] La clé `email + IP` n'est définie qu'à **un seul endroit** (`AuthController::cleThrottle`), utilisée par le limiteur et par la remise à zéro.
- [ ] **Rappel de déploiement** (à faire sur le VPS, hors de cette PR) : reporter `FRONTEND_URL=https://promeb.hotel-longchamps.fr` et `SESSION_SECURE_COOKIE=true` dans le `.env` de production. Sans ça, la restriction CORS et le cookie `Secure` ne prennent pas effet en prod.
