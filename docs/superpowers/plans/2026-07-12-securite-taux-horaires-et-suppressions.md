# Sécurité des taux horaires et suppressions non destructrices — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fermer la faille IDOR des taux horaires, et faire que supprimer un taux ou un client refuse au lieu de détruire les prestations en cascade.

**Architecture :** Les policies gagnent la vérification de propriété qui manque et refusent la suppression d'une ressource encore utilisée, avec un message explicite. Une migration passe les clés étrangères de `prestations` en `restrictOnDelete` pour que la base refuse aussi, même si un jour du code contourne les policies.

**Tech Stack :** Laravel 12.1.1 (PHP 8.4), Pest (`it()` + `RefreshDatabase`), SQLite en mémoire pour les tests, MySQL en production.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-12-securite-taux-horaires-et-suppressions-design.md`
- **L'ordre des vérifications dans les policies est imposé : la propriété D'ABORD, l'usage ENSUITE.** Si la policy comptait les prestations avant de vérifier le propriétaire, son message de refus (« utilisé par 25 prestations ») révélerait à un intrus le volume d'activité d'autrui. Le refus pour cause de propriété est un `false` muet ; seul le refus pour cause d'usage porte un message (`Illuminate\Auth\Access\Response::deny(...)`).
- `TauxHorairePolicy::update` continue d'autoriser la modification d'un taux dont les prestations ne sont **pas** facturées : ces prestations ne sont pas encore figées dans une facture, c'est légitime.
- Une prestation **facturée** ne peut être ni modifiée ni supprimée : `PrestationPolicy` le fait déjà, ne pas y toucher.
- `user_id` sur `prestations` **reste en cascade** : supprimer un compte doit tout emporter. Seuls `client_id` et `taux_horaire_id` passent en `restrictOnDelete`.
- Les tests tournent en local, en SQLite en mémoire : `php artisan test --testsuite=Feature`. **Jamais `php artisan test` sans `--testsuite=Feature`** : `phpunit.xml` déclare une suite `Unit` alors que `tests/Unit/` n'existe pas (préexistant, hors périmètre). Point de départ : 68 tests verts.
- Vérifié : Laravel 12.1.1 sait exécuter `dropForeign` sur SQLite (il recrée la table). La migration passe donc sur les deux moteurs.
- Aucun changement front n'est nécessaire : le store relaie déjà le message d'erreur de l'API en toast (`apiCall` fait `notify('error', err.response.data.message)`). Vérifié dans `resources/js/stores/taux-horaires.js`.
- Commits en français, format `type: description`.

---

### Task 1 : Fermer la faille IDOR sur les taux horaires

C'est la correction de sécurité. Elle part seule : elle doit pouvoir être déployée sans attendre le reste.

**Files:**
- Modify: `app/Policies/TauxHorairePolicy.php`
- Test: `tests/Feature/TauxHoraireSecurityTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: `TauxHorairePolicy::update()` et `::delete()` vérifient la propriété. La tâche 2 modifie le même fichier — elle s'appuie sur cette vérification, placée en premier.

Le contexte : `TauxHorairePolicy` ne compare jamais `$user->id` à `$tauxHoraire->user_id`. Elle ne regarde que les prestations facturées. Un tiers peut donc modifier et supprimer le taux d'autrui (constaté : 200, taux passé de 60 à 1).

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/TauxHoraireSecurityTest.php` :

```php
<?php

use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('interdit a un tiers de modifier le taux horaire d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $taux = TauxHoraire::factory()->create([
        'user_id' => $proprietaire->id,
        'taux'    => 60,
    ]);

    $this->actingAs($intrus)
        ->putJson("/api/taux-horaires/{$taux->id}", ['taux' => 1])
        ->assertForbidden();

    // Le taux ne doit pas avoir bougé.
    expect((float) $taux->fresh()->taux)->toBe(60.0);
});

it('interdit a un tiers de supprimer le taux horaire d\'un autre utilisateur', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $taux = TauxHoraire::factory()->create(['user_id' => $proprietaire->id, 'taux' => 60]);

    $this->actingAs($intrus)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertForbidden();

    expect(TauxHoraire::find($taux->id))->not->toBeNull();
});

it('autorise le proprietaire a modifier son propre taux horaire non facture', function () {
    $user = User::factory()->create();
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $this->actingAs($user)
        ->putJson("/api/taux-horaires/{$taux->id}", ['taux' => 65])
        ->assertOk();

    expect((float) $taux->fresh()->taux)->toBe(65.0);
});
```

> Note : le troisième test garantit qu'on ne casse pas le cas nominal en fermant la faille. Si `PUT /api/taux-horaires/{id}` exige d'autres champs que `taux` (regarde `app/Http/Requests/TauxHoraireRequest.php`), complète le corps de la requête — mais ne modifie pas la Form Request.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/TauxHoraireSecurityTest.php`
Expected: les deux premiers tests ÉCHOUENT (l'API renvoie 200 au lieu de 403 — c'est la faille). Le troisième passe déjà.

- [ ] **Step 3: Fermer la faille**

Remplacer `app/Policies/TauxHorairePolicy.php` par :

```php
<?php

namespace App\Policies;

use App\Models\TauxHoraire;
use App\Models\User;

class TauxHorairePolicy
{
    /**
     * L'utilisateur peut modifier un taux horaire **s'il en est le propriétaire**
     * et **tant qu'aucune facture ne s'appuie dessus** — sinon les lignes d'une
     * facture émise changeraient rétroactivement.
     */
    public function update(User $user, TauxHoraire $tauxHoraire): bool
    {
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }

    /**
     * L'utilisateur peut supprimer un taux horaire **s'il en est le propriétaire**
     * et **tant qu'aucune facture ne s'appuie dessus**.
     */
    public function delete(User $user, TauxHoraire $tauxHoraire): bool
    {
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
    }
}
```

La vérification de propriété passe **en premier** : un intrus ne doit rien apprendre sur les données d'autrui, pas même par un message d'erreur.

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/TauxHoraireSecurityTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 71 tests (68 + 3).

- [ ] **Step 6: Commit**

```bash
git add app/Policies/TauxHorairePolicy.php tests/Feature/TauxHoraireSecurityTest.php
git commit -m "fix: ferme la faille IDOR sur les taux horaires"
```

---

### Task 2 : Refuser la suppression d'un taux ou d'un client encore utilisé

**Files:**
- Modify: `app/Policies/TauxHorairePolicy.php` (la méthode `delete`)
- Modify: `app/Policies/ClientPolicy.php` (la méthode `delete`)
- Test: `tests/Feature/SuppressionNonDestructiveTest.php`

**Interfaces:**
- Consumes: la vérification de propriété posée par la tâche 1 dans `TauxHorairePolicy`.
- Produces: `delete()` renvoie désormais `Response|bool` (et non plus `bool`) sur les deux policies — un `Response::deny(message)` quand la ressource est encore utilisée.

Le contexte : `prestations` déclare `onDelete('cascade')` sur `client_id` et `taux_horaire_id`. Aujourd'hui, supprimer un taux **jamais facturé** renvoie 200 et **détruit ses prestations non facturées** (constaté). Supprimer un client détruit **toutes** ses prestations, même facturées, laissant des factures sans lignes.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/SuppressionNonDestructiveTest.php` :

```php
<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('refuse de supprimer un taux horaire utilise par des prestations non facturees, sans rien detruire', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,   // NON facturée
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/taux-horaires/{$taux->id}");

    $response->assertForbidden();

    // Le cœur du test : la prestation ne doit PAS avoir été détruite en cascade.
    expect(Prestation::find($prestation->id))->not->toBeNull();
    expect(TauxHoraire::find($taux->id))->not->toBeNull();
});

it('refuse de supprimer un taux horaire deja facture', function () {
    $user    = User::factory()->create();
    $client  = Client::factory()->create(['user_id' => $user->id]);
    $taux    = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertForbidden();

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('autorise la suppression d\'un taux horaire sans aucune prestation', function () {
    $user = User::factory()->create();
    $taux = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $this->actingAs($user)
        ->deleteJson("/api/taux-horaires/{$taux->id}")
        ->assertOk();

    expect(TauxHoraire::find($taux->id))->toBeNull();
});

it('refuse de supprimer un client qui a des prestations, sans rien detruire', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $this->actingAs($user)
        ->deleteJson("/api/clients/{$client->id}")
        ->assertForbidden();

    expect(Prestation::find($prestation->id))->not->toBeNull();
    expect(Client::find($client->id))->not->toBeNull();
});

it('autorise la suppression d\'un client sans prestation', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/clients/{$client->id}")
        ->assertOk();

    expect(Client::find($client->id))->toBeNull();
});

it('explique pourquoi la suppression est refusee', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 60]);

    Prestation::factory()->count(3)->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $response = $this->actingAs($user)->deleteJson("/api/taux-horaires/{$taux->id}");

    // Le message doit être exploitable : il dit combien de prestations bloquent.
    expect($response->json('message'))->toContain('3 prestations');
});

it('ne revele rien a un intrus dans le message de refus', function () {
    $proprietaire = User::factory()->create();
    $intrus       = User::factory()->create();

    $client = Client::factory()->create(['user_id' => $proprietaire->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $proprietaire->id, 'taux' => 60]);

    Prestation::factory()->count(25)->create([
        'user_id'         => $proprietaire->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => null,
    ]);

    $response = $this->actingAs($intrus)->deleteJson("/api/taux-horaires/{$taux->id}");

    $response->assertForbidden();

    // Le refus ne doit PAS trahir le volume d'activité du propriétaire.
    expect($response->json('message'))->not->toContain('25');
});
```

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/SuppressionNonDestructiveTest.php`
Expected: FAIL. En particulier, « refuse de supprimer un taux horaire utilisé par des prestations non facturées » échoue avec un 200 au lieu d'un 403, et la prestation a disparu (`null`) — c'est exactement la perte de données qu'on corrige.

- [ ] **Step 3: Refuser la suppression d'un taux encore utilisé**

Dans `app/Policies/TauxHorairePolicy.php`, remplacer la méthode `delete` (la méthode `update` reste telle que la tâche 1 l'a laissée) et ajouter l'import :

```php
use Illuminate\Auth\Access\Response;
```

```php
    /**
     * L'utilisateur peut supprimer un taux horaire **s'il en est le propriétaire**
     * et **si aucune prestation ne l'utilise** — facturée ou non.
     *
     * Sans ce garde-fou, la cascade de la base détruirait silencieusement les
     * prestations non facturées qui s'appuient sur ce taux.
     */
    public function delete(User $user, TauxHoraire $tauxHoraire): Response|bool
    {
        // La propriété d'abord : le message de refus ci-dessous ne doit jamais
        // renseigner un intrus sur le volume d'activité d'autrui.
        if ($user->id !== $tauxHoraire->user_id) {
            return false;
        }

        $nombre = $tauxHoraire->prestations()->count();

        if ($nombre > 0) {
            return Response::deny(
                "Ce taux horaire est utilisé par {$nombre} prestation" . ($nombre > 1 ? 's' : '') . '. '
                . 'Modifiez leur taux ou supprimez-les avant de le supprimer.'
            );
        }

        return true;
    }
```

- [ ] **Step 4: Refuser la suppression d'un client encore utilisé**

Dans `app/Policies/ClientPolicy.php`, ajouter l'import et remplacer la méthode `delete` (`update` reste inchangé : modifier un client est sans danger) :

```php
use Illuminate\Auth\Access\Response;
```

```php
    /**
     * L'utilisateur peut supprimer un client **s'il en est le propriétaire**
     * et **si aucune prestation ne lui est rattachée**.
     *
     * Sans ce garde-fou, la cascade de la base détruirait ses prestations —
     * y compris facturées, laissant des factures sans lignes dont le PDF échoue.
     */
    public function delete(User $user, Client $client): Response|bool
    {
        if ($user->id !== $client->user_id) {
            return false;
        }

        $nombre = $client->prestations()->count();

        if ($nombre > 0) {
            return Response::deny(
                "Ce client a {$nombre} prestation" . ($nombre > 1 ? 's' : '') . '. '
                . 'Supprimez-les avant de supprimer le client.'
            );
        }

        return true;
    }
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/SuppressionNonDestructiveTest.php`
Expected: PASS — 7 tests.

- [ ] **Step 6: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 78 tests (71 + 7). Si un test préexistant échoue, c'est qu'il supprimait un client ou un taux encore utilisé : lis-le, et détermine s'il testait le comportement destructeur (auquel cas il doit être corrigé) ou autre chose (auquel cas ta policy est trop stricte). Ne le supprime pas sans comprendre.

- [ ] **Step 7: Commit**

```bash
git add app/Policies/TauxHorairePolicy.php app/Policies/ClientPolicy.php tests/Feature/SuppressionNonDestructiveTest.php
git commit -m "fix: refuse la suppression d'un taux ou client encore utilise"
```

---

### Task 3 : La base refuse aussi (défense en profondeur)

Les policies protègent les routes. La base doit protéger le reste : commandes artisan, seeders, futurs endpoints. Aujourd'hui elle obéit et détruit.

**Files:**
- Create: `database/migrations/<timestamp>_restreint_suppression_prestations.php`
- Test: `tests/Feature/ContrainteIntegritePrestationsTest.php`

**Interfaces:**
- Consumes: rien (indépendant des policies).
- Produces: `prestations.client_id` et `prestations.taux_horaire_id` sont en `RESTRICT`. `prestations.user_id` reste en `CASCADE`.

Vérifié au préalable : Laravel 12.1.1 sait exécuter `dropForeign` sur SQLite (il recrée la table), donc cette migration passe aussi bien en test qu'en production MySQL.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/ContrainteIntegritePrestationsTest.php` :

```php
<?php

use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('la base refuse de supprimer un taux horaire encore utilise, meme hors policy', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    // Suppression directe, en contournant complètement les policies.
    expect(fn () => $taux->delete())->toThrow(QueryException::class);

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('la base refuse de supprimer un client encore utilise, meme hors policy', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    expect(fn () => $client->delete())->toThrow(QueryException::class);

    expect(Prestation::find($prestation->id))->not->toBeNull();
});

it('la suppression d\'un utilisateur emporte toujours ses donnees', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $taux   = TauxHoraire::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
    ]);

    // user_id reste en CASCADE : supprimer un compte doit tout emporter.
    // Ce test existe parce que RESTRICT sur client_id / taux_horaire_id
    // pourrait faire échouer cette cascade selon l'ordre choisi par la base.
    $user->delete();

    expect(Prestation::find($prestation->id))->toBeNull();
    expect(Client::find($client->id))->toBeNull();
    expect(TauxHoraire::find($taux->id))->toBeNull();
});
```

> Ce troisième test est le point de vigilance de la spec. **S'il échoue**, c'est que la cascade sur `user_id` entre en conflit avec les nouvelles contraintes `RESTRICT` : la base tente de supprimer les clients et les taux avant les prestations. NE le supprime PAS et ne le contourne pas. La correction est d'ajouter un observateur qui supprime les prestations en premier — décris précisément l'échec dans ton rapport et signale-le, c'est une décision de conception qui ne t'appartient pas.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/ContrainteIntegritePrestationsTest.php`
Expected: les deux premiers tests ÉCHOUENT — aucune exception n'est levée, la suppression réussit et détruit la prestation (`CASCADE` actuel). Le troisième passe déjà.

- [ ] **Step 3: Créer la migration**

Run: `php artisan make:migration restreint_suppression_prestations --table=prestations`

Remplacer le corps du fichier généré par :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Empêche la base de détruire des prestations en cascade.
     *
     * `client_id` et `taux_horaire_id` étaient en CASCADE : supprimer un client
     * ou un taux horaire détruisait silencieusement ses prestations — y compris
     * facturées. Les policies refusent désormais ces suppressions ; la base doit
     * refuser aussi, pour tout ce qui ne passe pas par elles (commandes artisan,
     * seeders, futurs endpoints).
     *
     * `user_id` reste en CASCADE : supprimer un compte doit bien tout emporter.
     */
    public function up(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();

            $table->dropForeign(['taux_horaire_id']);
            $table->foreign('taux_horaire_id')->references('id')->on('taux_horaires')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('prestations', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();

            $table->dropForeign(['taux_horaire_id']);
            $table->foreign('taux_horaire_id')->references('id')->on('taux_horaires')->cascadeOnDelete();
        });
    }
};
```

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/ContrainteIntegritePrestationsTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 81 tests (78 + 3).

- [ ] **Step 6: Vérifier la migration sur MySQL, pas seulement sur SQLite**

Les tests tournent sur SQLite, la production sur MySQL — une migration peut passer sur l'un et échouer sur l'autre. Le container de développement expose une vraie base MySQL.

Run: `docker compose exec -T app php artisan migrate --force`
Expected: la migration `restreint_suppression_prestations` s'applique, `DONE`.

Si Docker n'est pas disponible dans l'environnement, ne prétends pas avoir vérifié : dis-le dans ton rapport.

- [ ] **Step 7: Commit**

```bash
git add database/migrations tests/Feature/ContrainteIntegritePrestationsTest.php
git commit -m "fix: la base refuse de detruire des prestations en cascade"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — 81 tests verts.
- [ ] Contrôle manuel dans l'application (http://localhost:8080) : tenter de supprimer un taux horaire utilisé par des prestations → un toast rouge explique le refus (« Ce taux horaire est utilisé par N prestations… »), et les prestations sont toujours là. Aucun changement front n'était nécessaire : le store relaie déjà le message de l'API.
- [ ] Même contrôle sur la suppression d'un client ayant des prestations.
- [ ] Supprimer un taux horaire qui n'a aucune prestation → fonctionne toujours.
