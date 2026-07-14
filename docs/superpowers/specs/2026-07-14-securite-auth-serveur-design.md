# Durcir la sécurité serveur de l'authentification

Date : 2026-07-14

## Problème

Un audit de l'authentification (session Sanctum, Laravel 12 + Vue 3 SPA) a
confirmé un socle sain — CSRF actif, pas d'IDOR sur le profil, fixation de session
couverte, pas d'énumération de comptes — mais cinq défauts côté serveur, tous
vérifiés en exerçant l'API réelle.

### Critiques

**1. Aucun rate limiting sur `POST /api/auth/login`.** En Laravel 11+, le groupe de
middleware `api` n'applique plus `throttle` par défaut, et `bootstrap/app.php` ne
fait que `$middleware->statefulApi()`. Prouvé : 30 tentatives échouées en ~9 s,
aucun 429. La force brute est triviale et sans coût.

**2. CORS ouvert à toutes les origines, avec credentials.** `config/cors.php` a
`allowed_origins => ['*']` **et** `supports_credentials => true`. Prouvé : un
preflight depuis `https://evil.com` reçoit `Access-Control-Allow-Origin: evil.com`
+ `Allow-Credentials: true`. Seul le cookie `SameSite=Lax` empêche aujourd'hui
l'exploitation complète — une unique ligne de défense, qui sauterait si le cookie
passait un jour en `SameSite=none`.

### Importants

**3. `POST /api/auth/logout` sans session → HTTP 500.** La route est hors du groupe
`auth:sanctum`, et `Auth::logout()` échoue sans session (« Session store not set on
request. »).

**4. Cookie de session non `Secure` en production.** `SESSION_SECURE_COOKIE` est
absent de `.env.production.example` ; sa valeur par défaut est `null`. Le cookie de
session peut donc transiter en clair sur une requête HTTP (downgrade/MITM), malgré
l'`APP_URL` en HTTPS.

**5. Politique de mot de passe quasi inexistante.** `user:create` — le **seul**
chemin de création de compte (pas d'inscription publique) — valide avec
`min:2`. Prouvé : un mot de passe de 2 caractères est accepté. Cela sape entièrement
la correction du rate limiting.

## Décisions

Cinq corrections, un seul thème : durcir la porte d'entrée. On ne touche pas à ce
que l'audit a déclaré sain (CSRF, profil, fixation de session).

**Rate limiting : clé `email + IP`.** Ni l'IP seule (deux utilisateurs derrière un
même NAT se gêneraient ; des IP tournantes passent au travers), ni l'email seul (un
attaquant pourrait bloquer volontairement le compte d'autrui — déni de service
ciblé). Le couple email+IP cible la force brute sans ces effets de bord. Une
connexion réussie remet le compteur à zéro.

**CORS : liste des origines pilotée par le `.env`.** Cohérent avec la façon dont
`SESSION_DOMAIN` et `SANCTUM_STATEFUL_DOMAINS` sont déjà gérés. Une variable
`FRONTEND_URL` par environnement, plutôt qu'une liste en dur dans le code.

**Mot de passe : longueur seule, 12 caractères.** Les recommandations actuelles
(NIST) privilégient la longueur sur la complexité imposée — plus efficace, et moins
pénible pour l'unique créateur de comptes (toi, via artisan).

## Conception

| # | Fichier | Changement |
|---|---|---|
| 1 | `app/Providers/AppServiceProvider.php` | Définir un `RateLimiter::for('login', …)` clé `email + IP`, 5 tentatives/minute |
| 1 | `routes/api.php` | `->middleware('throttle:login')` sur la route de login |
| 2 | `config/cors.php` | `allowed_origins` lit `FRONTEND_URL` (défaut `APP_URL`) au lieu de `['*']` |
| 2 | `.env.example` | Documenter `FRONTEND_URL=http://localhost` |
| 2 | `.env.production.example` | `FRONTEND_URL=https://promeb.hotel-longchamps.fr` |
| 3 | `routes/api.php` | Déplacer la route `logout` dans le groupe `auth:sanctum` |
| 4 | `.env.production.example` | Ajouter `SESSION_SECURE_COOKIE=true` |
| 5 | `app/Console/Commands/CreateUser.php` | Règle `Password::min(12)` au lieu de `min:2` |

### Le rate limiter

Un limiteur nommé `login`, enregistré dans `AppServiceProvider::boot()` :

```php
RateLimiter::for('login', function (Request $request) {
    $cle = Str::lower((string) $request->input('email')) . '|' . $request->ip();
    return Limit::perMinute(5)->by($cle);
});
```

Au-delà de 5 tentatives sur le même couple email+IP en une minute, Laravel renvoie
automatiquement un **429** avec l'en-tête `Retry-After`. Le message reste celui de
Laravel (générique) — le front l'affiche déjà via son propre traitement d'erreur.

**La remise à zéro au succès est explicite, pas implicite.** Le middleware
`throttle` incrémente le compteur à *chaque* requête, réussie ou non — une
connexion valide consommerait donc un jeton sans le libérer. Pour qu'une connexion
réussie « efface l'ardoise », `AuthController::login` appelle `RateLimiter::clear`
sur la même clé (`email + IP`) juste après `Auth::attempt` réussi, avant de
répondre. La clé est donc construite de façon identique dans le limiteur et dans le
contrôleur : ce point est un risque (deux définitions de la même clé) — le plan
devra soit centraliser la construction de la clé, soit la vérifier par un test qui
prouve qu'une réussite remet bien le compteur à zéro.

### CORS

`config/cors.php`, `allowed_origins` :

```php
'allowed_origins' => array_filter([env('FRONTEND_URL', env('APP_URL'))]),
```

`array_filter` évite un tableau `[null]` si aucune des deux variables n'est
définie. Une seule origine suffit (l'app a un seul front). Si un besoin
multi-origines apparaissait, on passerait à une liste séparée par des virgules —
hors périmètre aujourd'hui (YAGNI).

### Le logout

Déplacer la route dans le groupe `auth:sanctum` existant. Un logout sans session
authentifiée renverra alors un **401** propre (géré par le middleware) au lieu d'un
500. Le corps de `AuthController::logout` est inchangé : il ne s'exécute que si
l'utilisateur est authentifié.

## Tests

Pest, `php artisan test`. Le CSRF n'est pas testable en Pest (le middleware se
désactive sous les tests) — mais aucune de ces corrections n'en dépend pour être
vérifiée.

- **Rate limiting** : 5 tentatives échouées passent (422) ; la 6ᵉ est bloquée
  (429). Une IP différente sur le même email n'est pas affectée (le couple change).
  Le limiteur doit être **rendu déterministe** dans les tests (vider le cache du
  limiteur entre les tests, ou utiliser `RateLimiter::clear`).
- **Logout** : connecté → 204 ; non connecté → 401 (et non 500).
- **Politique de mot de passe** : `user:create` refuse un mot de passe de moins de
  12 caractères, accepte un mot de passe de 12+.
- **CORS** : un test qui envoie une requête avec un `Origin` étranger et vérifie que
  l'en-tête `Access-Control-Allow-Origin` ne le reflète pas. La config résolue
  (`config('cors.allowed_origins')`) ne contient pas `*`.
- **Cookie `Secure`** : c'est une variable d'environnement de production, non
  testable en CI (qui tourne sans ce `.env`). Vérifié par lecture du modèle
  `.env.production.example`, et par une note explicite : à reporter dans le `.env`
  du VPS au déploiement.

## Hors périmètre

- Les points **front** de l'audit : intercepteur axios sur 401, revalidation de
  `checkAuth`, message de login en français, état « connecté fantôme ». C'est le
  lot suivant.
- **HSTS** : à poser sur le reverse proxy Nginx du VPS (hors dépôt).
- Le seeder de test (`DatabaseSeeder`) : `deploy.sh` ne l'exécute pas ; risque déjà
  neutralisé.
- Un endpoint de changement de mot de passe : n'existe pas, et n'est pas demandé.
