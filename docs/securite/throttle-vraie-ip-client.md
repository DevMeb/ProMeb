# Faire remonter la vraie IP client jusqu'au throttle de login

Contexte : `AuthController::cleThrottle()` construit la clé du rate limiter de
login avec `email + IP` (voir
`docs/superpowers/specs/2026-07-14-securite-auth-serveur-design.md`). En
production, tant que les trois couches ci-dessous ne sont pas configurées,
`$request->ip()` renvoie l'IP du **proxy Nginx du VPS**, constante pour tous
les clients — la clé dégénère de fait en `email` seul. Ce n'est pas une faille
(l'anti-force-brute par email fonctionne toujours), mais le bénéfice du
`+ IP` (empêcher un attaquant de bloquer volontairement le compte d'autrui en
DoS ciblé) n'est atteint qu'une fois ce guide appliqué.

Ce document explique, couche par couche, comment faire remonter la vraie IP
du client jusqu'à `$request->ip()` dans Laravel. Il est autosuffisant : pas
besoin d'avoir suivi le reste du projet pour le dérouler.

## Vue d'ensemble du trajet d'une requête

```
Client → Nginx du VPS (reverse proxy, hors dépôt)
       → Nginx du container `promeb_nginx` (nginx/default.prod.conf, dans le dépôt)
       → PHP-FPM `promeb_app` → Laravel (bootstrap/app.php)
```

Chaque saut ajoute une couche réseau entre le client et Laravel. Sans rien
configurer, chaque couche voit l'IP du sauteur précédent, pas celle du client
d'origine. Il faut :

1. Que le premier saut (Nginx du VPS) note l'IP réelle du client dans un
   en-tête HTTP (`X-Forwarded-For`, `X-Real-IP`).
2. Que le deuxième saut (Nginx du container) transmette cet en-tête et
   l'utilise pour renseigner `REMOTE_ADDR` côté PHP.
3. Que Laravel fasse confiance à ce `REMOTE_ADDR`-là (et seulement à
   celui-là) pour peupler `$request->ip()`.

Sauter une étape casse la chaîne (l'IP ne remonte pas) ; mal faire l'étape 3
(faire confiance à n'importe qui) ouvre une faille pire que le problème
d'origine — voir l'avertissement en fin de document.

## 1. Nginx du VPS (reverse proxy, hors dépôt)

Ce fichier vit sur le VPS Infine, pas dans ce dépôt (c'est le proxy qui route
`promeb.hotel-longchamps.fr` vers le container `promeb_nginx`). Il doit
transmettre l'IP réelle du client aux en-têtes standard :

```nginx
server {
    listen 443 ssl;
    server_name promeb.hotel-longchamps.fr;

    location / {
        proxy_pass http://promeb_nginx:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

- `$remote_addr` est l'IP du client tel que vu par ce Nginx (le premier saut
  ne peut pas se faire mentir dessus au niveau TCP).
- `$proxy_add_x_forwarded_for` ajoute `$remote_addr` à un éventuel
  `X-Forwarded-For` déjà présent dans la requête entrante (plutôt que de
  l'écraser), ce qui garde une chaîne cohérente si jamais un autre
  intermédiaire existe en amont.

**Vérifier / ajouter** : se connecter au VPS, ouvrir le `server {}` qui route
vers `promeb_nginx`, confirmer la présence des trois `proxy_set_header`
ci-dessus. C'est probablement déjà fait pour d'autres projets sur ce VPS
(pattern standard, voir `CLAUDE.md`) — mais à vérifier explicitement pour
`promeb.hotel-longchamps.fr`, ne pas supposer.

Recharger après modification :

```bash
nginx -t && systemctl reload nginx
```

## 2. Nginx du container (`nginx/default.prod.conf`, dans le dépôt)

Ce Nginx-là reçoit la requête du Nginx du VPS via le réseau Docker partagé
`sites_network` (voir `docker-compose.production.yml`). Par défaut, PHP-FPM
verra `REMOTE_ADDR` = l'IP du Nginx du VPS sur ce réseau Docker, pas celle du
client. Il faut dire à ce Nginx : « fais confiance à l'en-tête
`X-Forwarded-For` envoyé par le Nginx du VPS, et remplace `$remote_addr` par
la vraie IP qu'il contient » — c'est le rôle du module `ngx_http_realip_module`
(inclus par défaut dans l'image `nginx:alpine`).

Ajouter dans le bloc `server {}` de `nginx/default.prod.conf` :

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name promeb.hotel-longchamps.fr www.promeb.hotel-longchamps.fr;
    root /var/www/html/public;

    # Ne faire confiance qu'aux en-têtes X-Forwarded-For envoyés depuis le
    # réseau Docker partagé sites_network (là où vit le Nginx du VPS côté
    # container) — jamais depuis n'importe quelle IP (voir avertissement
    # plus bas sur le "*").
    set_real_ip_from 172.16.0.0/12;   # plage par défaut des réseaux Docker
    real_ip_header X-Forwarded-For;
    real_ip_recursive on;

    add_header X-Frame-Options "SAMEORIGIN";
    # ... reste du fichier inchangé
```

**Comment trouver le réseau à mettre dans `set_real_ip_from`** :

```bash
docker network inspect sites_network --format '{{ (index .IPAM.Config 0).Subnet }}'
```

Cette commande affiche le sous-réseau réellement attribué à `sites_network`
sur le VPS (souvent une plage `172.x.0.0/16` ou similaire — les réseaux
Docker par défaut piochent dans `172.16.0.0/12`, mais **vérifier la valeur
réelle plutôt que de la supposer**, elle peut varier selon ce qui est déjà
alloué sur l'hôte). Remplacer `172.16.0.0/12` ci-dessus par le résultat exact
de cette commande si possible — une plage plus précise que la totalité de
`172.16.0.0/12` réduit la surface si un jour un container compromis se trouve
sur le même réseau Docker.

`real_ip_recursive on` permet de remonter la chaîne `X-Forwarded-For` si elle
contient plusieurs IP (utile si un jour un CDN ou un autre proxy s'ajoute
devant le Nginx du VPS) en ignorant les IP qui appartiennent elles-mêmes à des
plages de confiance, pour ne garder que la première IP externe.

Ce changement est dans le dépôt : il part avec le build de l'image
`promeb_nginx` (`Dockerfile.prod`, stage `nginx_app`), donc un simple
redéploiement (`deploy.sh` → `docker compose build && docker compose up -d`)
suffit à l'appliquer.

## 3. Laravel (`bootstrap/app.php`)

Une fois `REMOTE_ADDR` correct au niveau de PHP-FPM (étape 2), il reste à dire
à Laravel qu'il peut faire confiance à cette valeur. Par défaut, le
middleware `TrustProxies` de Laravel ignore tous les en-têtes `Forwarded` /
`X-Forwarded-*` sauf s'il connaît explicitement l'IP (ou la plage) de qui les
envoie — ici, le container `promeb_nginx` lui-même (le seul intermédiaire que
PHP-FPM voit directement).

Dans `bootstrap/app.php` :

```php
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... inchangé ... */)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->trustProxies(
            at: '172.16.0.0/12', // même réseau sites_network que l'étape 2
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

Remplacer `172.16.0.0/12` par le sous-réseau exact de `sites_network` trouvé à
l'étape 2 (idéalement la même valeur des deux côtés, pour cohérence).

### Pourquoi jamais `at: '*'`

`trustProxies(at: '*')` dit à Laravel : « fais confiance à l'en-tête
`X-Forwarded-For` peu importe qui envoie la requête ». Or `X-Forwarded-For`
est un en-tête HTTP **ordinaire**, entièrement contrôlable par le client — y
compris un attaquant qui parle directement à `promeb_nginx` (ou même à
Laravel, si jamais un port est exposé sans passer par le VPS). Avec `at: '*'`
en place :

1. L'attaquant envoie `POST /api/auth/login` avec
   `X-Forwarded-For: 1.2.3.4` (une IP qu'il invente).
2. Laravel fait confiance à cet en-tête : `$request->ip()` renvoie
   `1.2.3.4`.
3. La clé du throttle devient `email|1.2.3.4` — neuve, jamais vue.
4. L'attaquant recommence avec `X-Forwarded-For: 1.2.3.5`, `.6`, `.7`, …
   à chaque tentative : chaque requête obtient un compteur de throttle
   vierge.
5. Le rate limiting `email + IP` est **entièrement contourné** — pire que
   l'absence du `+ IP` documentée dans la spec (qui, elle, retombe sur un
   throttle par email toujours effectif).

`at: '<réseau sites_network>'` limite la confiance au seul expéditeur légitime
de cet en-tête dans notre topologie (le container `promeb_nginx`, qui lui-même
ne transmet que ce que le Nginx du VPS lui a envoyé, qui lui-même a écrit
`$remote_addr` — une valeur que le client ne peut pas falsifier au niveau
TCP). Un attaquant qui contacte directement `promeb_app` ou `promeb_nginx`
sans passer par le VPS n'est pas dans cette plage : son `X-Forwarded-For`
est ignoré, et `$request->ip()` retombe sur l'IP de connexion réelle (la
sienne, ou celle du Nginx du VPS s'il passe par le chemin normal).

## Comment vérifier que ça fonctionne

Après avoir appliqué les trois couches et redéployé :

1. Ajouter temporairement une route de diagnostic (à retirer après le test,
   ne jamais la laisser en prod) :

   ```php
   // routes/web.php ou routes/api.php, temporaire
   Route::get('/debug-ip', fn (Request $request) => $request->ip());
   ```

2. Depuis **deux machines différentes** (deux réseaux différents — par
   exemple son poste et un téléphone en 4G, ou deux VPN différents), visiter
   `https://promeb.hotel-longchamps.fr/debug-ip`.

3. Constater que :
   - Les deux réponses affichent des IP **différentes** entre elles.
   - Aucune des deux IP n'est celle du container (`docker network inspect
     sites_network` pour comparer, ou `docker exec promeb_nginx hostname -i`).
   - L'IP affichée correspond à l'IP publique réelle de la machine utilisée
     (vérifiable via `curl ifconfig.me` depuis cette même machine).

4. Retirer la route de diagnostic une fois la vérification faite.

Si les deux réponses affichent la **même** IP (ou l'IP du container), une des
trois couches n'est pas configurée correctement — reprendre dans l'ordre
Nginx VPS → Nginx container → Laravel, chacune ne pouvant fonctionner que si
la précédente le fait.

Une fois ces trois couches en place et vérifiées, retirer la note ajoutée
dans `docs/superpowers/specs/2026-07-14-securite-auth-serveur-design.md`
(section « Rate limiting : clé `email + IP` ») : le compromis `email + IP`
sera alors réellement tenu en production.
