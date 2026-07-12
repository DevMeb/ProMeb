# Empêcher la refacturation d'une prestation — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refuser qu'une prestation déjà rattachée à une facture soit facturée une seconde fois — ce qui vide aujourd'hui la facture d'origine, silencieusement.

**Architecture :** Trois niveaux. La Form Request refuse en 422 avec un message clair ; le service vérifie à son tour ; et surtout le rattachement devient un `update` conditionnel (`whereNull('facture_id')`) dont on contrôle le nombre de lignes affectées — seul ce dernier niveau est à l'abri d'une course entre deux requêtes simultanées.

**Tech Stack :** Laravel 12.1.1 (PHP 8.4), Pest (`it()` + `RefreshDatabase`), SQLite en mémoire pour les tests.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-12-refacturation-prestation-design.md`
- **Le rattachement conditionnel est le seul garde-fou sûr.** Valider puis agir laisse une fenêtre : entre le contrôle de la Form Request et l'`update` du service, une autre requête peut rattacher la prestation. Les niveaux 1 et 2 servent le message d'erreur et la lisibilité, pas la sûreté. Ne pas les prendre pour la protection.
- Le bug reproduit : facturer une prestation déjà facturée renvoie **201**, la facture d'origine garde son `montant_total` mais perd ses lignes, et son PDF tombe en **500**.
- Aucune facture n'est corrompue en base aujourd'hui (vérifié : 0 facture sans ligne sur 5). Aucune réparation de données n'est à faire.
- Aucun changement front : le store ne propose déjà que les prestations non facturées, et il relaie automatiquement le message d'erreur 422 en toast.
- Les tests tournent en local, en SQLite en mémoire : `php artisan test --testsuite=Feature`. **JAMAIS `php artisan test` sans `--testsuite=Feature`** : `phpunit.xml` déclare une suite `Unit` qui n'existe pas (préexistant, hors périmètre). Point de départ : 83 tests verts.
- Commits en français, format `type: description`.

**Écart assumé par rapport à la spec.** Celle-ci décrit *trois* niveaux de protection, dont un contrôle explicite dans `FactureService::create()` (« vérifier qu'aucune prestation n'est déjà facturée » avant de créer la facture). Ce plan ne l'implémente **pas** : il serait strictement redondant. Le rattachement conditionnel de la tâche 2 attrape déjà ce cas — une prestation déjà facturée n'est pas affectée par l'`update`, donc le décompte ne correspond pas et l'exception est levée. Ajouter une vérification en amont, c'est deux chemins de code pour une seule règle, dont un jamais exercé par un test. La validation (tâche 1) porte déjà le message d'erreur clair pour l'appel API direct.

---

### Task 1 : La validation refuse et explique

**Files:**
- Modify: `app/Http/Requests/FactureRequest.php`
- Test: `tests/Feature/RefacturationTest.php`

**Interfaces:**
- Consumes: rien.
- Produces: `POST /api/factures` renvoie 422 si une prestation est déjà facturée, ou n'appartient pas à l'utilisateur. La tâche 2 ajoute les garde-fous du service.

Aujourd'hui la règle est `'prestations.*' => 'integer|exists:prestations,id'` : elle n'exclut pas les prestations déjà facturées, et ne vérifie même pas qu'elles appartiennent à l'utilisateur connecté.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/RefacturationTest.php` :

```php
<?php

use App\Models\Client;
use App\Models\Facture;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Crée un utilisateur avec un client, un taux et une prestation libre.
 * Retourne [$user, $prestation].
 */
function contexteFacturation(float $heures = 10, float $taux = 50): array
{
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);
    $th     = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => $taux]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $th->id,
        'facture_id'      => null,
        'heures'          => $heures,
    ]);

    return [$user, $prestation];
}

it('refuse de facturer une prestation deja facturee', function () {
    [$user, $prestation] = contexteFacturation();

    // 1re facture : elle rattache la prestation.
    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertCreated();

    $factureOrigine = Facture::first();

    // 2e tentative avec la MÊME prestation.
    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertStatus(422);

    // La facture d'origine est intacte : ses lignes ET son montant.
    expect($factureOrigine->fresh()->prestations)->toHaveCount(1);
    expect((float) $factureOrigine->fresh()->montant_total)->toBe(500.0);

    // Aucune seconde facture n'a été créée.
    expect(Facture::count())->toBe(1);
});

it('laisse le PDF de la facture d\'origine intact apres une tentative de refacturation', function () {
    $user   = User::factory()->create([
        'iban'        => 'FR7630001007941234567890185',
        'prenom'      => 'Jean',
        'adresse'     => '1 rue de la Paix',
        'ville'       => 'Paris',
        'code_postal' => '75001',
        'siren'       => '123456789',
        'nom_societe' => 'JD Conseil',
    ]);
    $client = Client::factory()->create(['user_id' => $user->id]);
    $th     = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 50]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $th->id,
        'facture_id'      => null,
        'heures'          => 10,
    ]);

    $this->actingAs($user)->postJson('/api/factures', ['prestations' => [$prestation->id]])->assertCreated();
    $facture = Facture::first();

    $this->actingAs($user)->postJson('/api/factures', ['prestations' => [$prestation->id]])->assertStatus(422);

    // Le PDF doit toujours se générer : c'est lui qui tombait en 500.
    $this->actingAs($user)
        ->get("/api/factures/{$facture->id}/pdf")
        ->assertOk();
});

it('refuse de facturer la prestation d\'un autre utilisateur', function () {
    [$proprietaire, $prestation] = contexteFacturation();
    $intrus = User::factory()->create();

    $this->actingAs($intrus)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertStatus(422);

    expect($prestation->fresh()->facture_id)->toBeNull();
    expect(Facture::count())->toBe(0);
});

it('facture normalement des prestations libres', function () {
    [$user, $prestation] = contexteFacturation();

    $response = $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]]);

    $response->assertCreated();

    expect($prestation->fresh()->facture_id)->not->toBeNull();
    expect((float) Facture::first()->montant_total)->toBe(500.0);
});
```

> Note : le second test remplit tous les champs de profil du `user`, car `FactureService::getPdf()` avorte en 422 si l'un manque (`iban`, `name`, `prenom`, `adresse`, `ville`, `code_postal`, `siren`, `nom_societe`). `UserFactory` ne les remplit pas tous — d'où l'appel explicite.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/RefacturationTest.php`
Expected: FAIL. Le premier test échoue avec un **201 au lieu du 422** attendu, et la facture d'origine se retrouve avec **0 ligne** — c'est exactement le bug. Le test du PDF échoue avec un **500**. Le test « prestation d'un autre utilisateur » échoue aussi (201 : la règle actuelle ne scope pas sur l'utilisateur). Le dernier test (cas nominal) passe déjà.

- [ ] **Step 3: Durcir la validation**

Remplacer `app/Http/Requests/FactureRequest.php` par :

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FactureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'prestations'   => 'required|array',
            'prestations.*' => [
                'integer',
                // La prestation doit appartenir à l'utilisateur ET être libre :
                // refacturer une prestation déjà rattachée viderait sa facture d'origine.
                Rule::exists('prestations', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()->id)
                          ->whereNull('facture_id');
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'prestations.required' => 'La sélection de prestations est obligatoire.',
            'prestations.*.exists' => 'Une ou plusieurs prestations sélectionnées n\'existent pas ou sont déjà rattachées à une facture.',
        ];
    }
}
```

Le message d'erreur mentionne les deux causes possibles : une prestation inconnue et une prestation déjà facturée produisent la même règle `exists`, donc le même message. Le distinguer exigerait deux règles séparées et révélerait à un intrus l'existence d'une prestation qui ne lui appartient pas — on s'en tient donc à un message unique, mais explicite sur les deux cas.

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/RefacturationTest.php`
Expected: PASS — 4 tests.

- [ ] **Step 5: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 87 tests (83 + 4). Si un test préexistant échoue, lis-le : il facturait peut-être une prestation déjà rattachée, ou la prestation d'un autre utilisateur. Détermine s'il testait le comportement fautif (à corriger) ou autre chose (ta règle est trop stricte). **Ne le supprime pas sans comprendre.**

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/FactureRequest.php tests/Feature/RefacturationTest.php
git commit -m "fix: refuse de facturer une prestation deja facturee"
```

---

### Task 2 : Le rattachement ne peut plus écraser (le cœur)

La validation ne protège pas d'une course : entre le contrôle de la Form Request et l'`update` du service, une autre requête peut rattacher la prestation. C'est le scénario réaliste des **deux onglets**. Cette tâche ferme cette fenêtre.

**Files:**
- Modify: `app/Services/FactureService.php` (la méthode `create()`)
- Test: `tests/Feature/RefacturationTest.php` (compléter le fichier de la tâche 1)

**Interfaces:**
- Consumes: la validation de la tâche 1 (elle ne suffit pas — d'où cette tâche).
- Produces: rien (dernière tâche).

Le code actuel de `create()` rattache ainsi :

```php
Prestation::whereIn('id', $ids)
    ->where('user_id', Auth::id())
    ->update(['facture_id' => $facture->id]);
```

Cet `update` **écrase** le `facture_id` existant sans vérifier qu'il était nul.

- [ ] **Step 1: Écrire le test de course qui échoue**

Ajouter à la fin de `tests/Feature/RefacturationTest.php` :

```php
it('annule la facture si une prestation est rattachee entre la verification et le rattachement', function () {
    [$user, $prestation] = contexteFacturation();

    // Une autre facture, déjà en base, qui va « voler » la prestation.
    $autreFacture = Facture::factory()->create(['user_id' => $user->id]);

    // On simule la course au point exact où elle peut se produire :
    // FactureService::create() vérifie les prestations, PUIS crée la facture,
    // PUIS rattache les prestations. L'événement `created` de la facture tombe
    // donc précisément dans la fenêtre — entre la vérification et le rattachement.
    Facture::created(function (Facture $facture) use ($prestation, $autreFacture) {
        if ($facture->id !== $autreFacture->id) {
            Prestation::where('id', $prestation->id)
                ->update(['facture_id' => $autreFacture->id]);
        }
    });

    $this->actingAs($user)
        ->postJson('/api/factures', ['prestations' => [$prestation->id]])
        ->assertStatus(422);

    // La facture qui venait d'être créée doit avoir été annulée par le rollback :
    // il ne reste que $autreFacture.
    expect(Facture::count())->toBe(1);
    expect(Facture::first()->id)->toBe($autreFacture->id);
});
```

> **Subtilité du test, à comprendre avant de le lire de travers.** Le rattachement du listener s'exécute *dans* la transaction de `create()`. Quand l'exception provoque le rollback, cet `update` est annulé lui aussi — donc après le test, `$prestation->facture_id` sera `null`, et non `$autreFacture->id`. C'est un artefact du test, pas du comportement réel : dans une vraie course, l'autre requête aurait sa propre transaction, déjà committée. N'assert donc PAS sur `$prestation->facture_id` ici. Ce que le test prouve, et qui suffit : la seconde facture n'est **pas** créée, et rien n'a été écrasé.

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

Run: `php artisan test --testsuite=Feature tests/Feature/RefacturationTest.php`
Expected: le nouveau test ÉCHOUE avec un **201 au lieu du 422** — la validation est passée (la prestation était libre au moment du contrôle), et l'`update` du service a écrasé le rattachement de `$autreFacture`. C'est la course, reproduite.

- [ ] **Step 3: Rendre le rattachement conditionnel**

Dans `app/Services/FactureService.php`, méthode `create()`, remplacer le bloc de rattachement par :

```php
                // Le rattachement ne touche QUE les prestations encore libres.
                // Si une autre requête en a rattaché une entre-temps (deux onglets),
                // le nombre de lignes affectées ne correspondra pas : on annule tout
                // plutôt que d'écraser sa facture — ce qui la viderait silencieusement.
                $affectees = Prestation::whereIn('id', $ids)
                    ->where('user_id', Auth::id())
                    ->whereNull('facture_id')
                    ->update(['facture_id' => $facture->id]);

                if ($affectees !== count(array_unique($ids))) {
                    throw ValidationException::withMessages([
                        'prestations' => "Une ou plusieurs prestations viennent d'être rattachées à une autre facture. Rechargez la page et réessayez.",
                    ]);
                }
```

`ValidationException` est déjà importée dans ce fichier (elle sert au garde-fou « les prestations ne vous appartiennent pas »). L'exception lève un 422 et déclenche le rollback de la transaction qui enveloppe déjà `create()` : la facture qui venait d'être créée disparaît.

- [ ] **Step 4: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/RefacturationTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 5: Vérifier que le garde-fou sert vraiment**

Preuve de bonne foi, à faire avant de commiter. Retire temporairement le `->whereNull('facture_id')` de l'`update` (en gardant le contrôle de `$affectees`), puis relance :

Run: `php artisan test --testsuite=Feature tests/Feature/RefacturationTest.php`
Expected: le test de course ÉCHOUE (l'`update` écrase à nouveau, `$affectees` vaut 1, aucune exception). Si le test reste vert sans le `whereNull`, c'est qu'il ne prouve rien — recommence.

Puis **restaure** le `->whereNull('facture_id')` et vérifie que tout repasse au vert.

- [ ] **Step 6: Lancer la suite complète**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — 88 tests (87 + 1).

- [ ] **Step 7: Commit**

```bash
git add app/Services/FactureService.php tests/Feature/RefacturationTest.php
git commit -m "fix: le rattachement d'une prestation n'ecrase plus sa facture"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — 88 tests verts.
- [ ] Contrôle manuel dans l'application (http://localhost:8080) : créer une facture depuis des prestations non facturées fonctionne toujours, et les prestations facturées ne sont plus proposées dans le formulaire.
- [ ] Aucune facture sans ligne en base après ces manipulations :
      `docker compose exec -T -e HOME=/tmp app php artisan tinker --execute="echo App\Models\Facture::withCount('prestations')->having('prestations_count', 0)->count();"`
      Attendu : `0`.
