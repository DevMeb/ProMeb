# Colonne « Horaires » optionnelle sur les factures PDF — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permettre de masquer la colonne « Horaires » du tableau des prestations sur la facture PDF, client par client.

**Architecture :** Un booléen `afficher_horaires` sur la table `clients` (défaut `true`, donc aucun changement pour les clients existants). `FactureService::getPdf()` le lit sur le client de la facture et le passe à la vue Blade sous le nom `$afficherHoraires` ; la vue conditionne l'en-tête et la cellule. Une case à cocher dans la fiche client pilote la valeur.

**Tech Stack :** Laravel (API), Pest (`it()` + `RefreshDatabase`), Eloquent factories, barryvdh/laravel-dompdf, Vue 3 `<script setup>` + Pinia, Tailwind.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-12-facture-pdf-colonne-horaires-optionnelle-design.md`
- Nom du champ en base et dans l'API : `afficher_horaires` (snake_case, français, cohérent avec `code_postal`).
- Nom de la variable de vue Blade : `$afficherHoraires`.
- Défaut : `true`. Un client existant, ou créé sans le champ, affiche les horaires.
- La donnée `horaires` de la prestation reste saisie et stockée : seul son affichage sur le PDF est conditionné.
- Les tests tournent en local, en SQLite en mémoire (cf. `phpunit.xml`) : `php artisan test --testsuite=Feature`. Ni Docker ni MySQL requis. (`php artisan test` sans argument échoue : `phpunit.xml` déclare une suite `Unit` alors que `tests/Unit/` n'existe pas — préexistant, hors périmètre.)
- Commits en français, format `type: description`.

---

### Task 1 : Le champ en base et sur le modèle

**Files:**
- Create: `database/migrations/<timestamp>_add_afficher_horaires_to_clients_table.php`
- Modify: `app/Models/Client.php`
- Test: `tests/Feature/ClientAfficherHorairesTest.php`

**Interfaces:**
- Consumes: rien (première tâche).
- Produces: `Client::$afficher_horaires` — booléen casté, présent dans `$fillable`. Les tâches 2, 3 et 4 en dépendent.

- [ ] **Step 1: Écrire le test qui échoue**

Créer `tests/Feature/ClientAfficherHorairesTest.php` :

```php
<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('affiche les horaires par défaut pour un client créé sans le champ', function () {
    $client = Client::factory()->create(['user_id' => User::factory()]);

    expect($client->fresh()->afficher_horaires)->toBeTrue();
});

it('permet de masquer les horaires sur un client', function () {
    $client = Client::factory()->create([
        'user_id'           => User::factory(),
        'afficher_horaires' => false,
    ]);

    expect($client->fresh()->afficher_horaires)->toBeFalse();
});
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

Run: `php artisan test --testsuite=Feature tests/Feature/ClientAfficherHorairesTest.php`
Expected: FAIL — colonne `afficher_horaires` inconnue (`SQLSTATE ... Unknown column`).

- [ ] **Step 3: Créer la migration**

Run: `php artisan make:migration add_afficher_horaires_to_clients_table --table=clients`

Puis remplacer le corps du fichier généré par :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('afficher_horaires')->default(true)->after('siren');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('afficher_horaires');
        });
    }
};
```

- [ ] **Step 4: Déclarer le champ sur le modèle**

Dans `app/Models/Client.php`, ajouter `'afficher_horaires'` à `$fillable` (après `'siren'`) et ajouter la méthode de cast :

```php
    protected $fillable = [
        'nom',
        'adresse',
        'code_postal',
        'ville',
        'pays',
        'siren',
        'afficher_horaires',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'afficher_horaires' => 'boolean',
        ];
    }
```

- [ ] **Step 5: Lancer le test et vérifier qu'il passe**

Run: `php artisan test --testsuite=Feature tests/Feature/ClientAfficherHorairesTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 6: Commit**

```bash
git add database/migrations app/Models/Client.php tests/Feature/ClientAfficherHorairesTest.php
git commit -m "feat: ajoute le champ afficher_horaires sur le client"
```

---

### Task 2 : Le champ traverse l'API

**Files:**
- Modify: `app/Http/Requests/ClientRequest.php`
- Modify: `app/Http/Resources/ClientResource.php`
- Test: `tests/Feature/ClientAfficherHorairesTest.php` (compléter le fichier de la tâche 1)

**Interfaces:**
- Consumes: `Client::$afficher_horaires` (tâche 1).
- Produces: la clé `afficher_horaires` acceptée en entrée par `POST /api/clients` et `PUT /api/clients/{client}`, et renvoyée dans la réponse JSON. La tâche 4 (UI) en dépend.

- [ ] **Step 1: Écrire les tests qui échouent**

Ajouter à la fin de `tests/Feature/ClientAfficherHorairesTest.php` :

```php
it('persiste afficher_horaires à la création via l\'API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/clients', [
        'nom'               => 'Client discret',
        'afficher_horaires' => false,
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.afficher_horaires', false);
    expect(Client::where('nom', 'Client discret')->first()->afficher_horaires)->toBeFalse();
});

it('persiste afficher_horaires à la mise à jour via l\'API', function () {
    $user   = User::factory()->create();
    $client = Client::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->putJson("/api/clients/{$client->id}", [
        'nom'               => $client->nom,
        'afficher_horaires' => false,
    ]);

    $response->assertOk();
    expect($client->fresh()->afficher_horaires)->toBeFalse();
});
```

> Note : `assertCreated()` suppose que `ClientController::store` renvoie un 201. Si le contrôleur renvoie 200, adapter l'assertion à `assertOk()` — ne pas changer le contrôleur, cette tâche n'a pas à modifier son code de statut.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/ClientAfficherHorairesTest.php`
Expected: FAIL sur les 2 nouveaux tests — le champ n'est pas validé donc jamais passé au modèle, `afficher_horaires` reste à `true`.

- [ ] **Step 3: Autoriser le champ dans la validation**

Dans `app/Http/Requests/ClientRequest.php`, ajouter la règle à la fin du tableau `rules()` :

```php
            'siren'             => 'nullable|regex:/^\d{9}(\d{5})?$/',
            'afficher_horaires' => 'sometimes|boolean',
```

`sometimes` : si le front n'envoie pas la clé, la valeur en base n'est pas touchée (et vaut `true` par défaut à la création).

- [ ] **Step 4: Exposer le champ dans la ressource**

Dans `app/Http/Resources/ClientResource.php`, ajouter la clé après `siren` :

```php
            'siren'             => $this->siren,
            'afficher_horaires' => (bool) $this->afficher_horaires,
```

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/ClientAfficherHorairesTest.php`
Expected: PASS — 4 tests.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/ClientRequest.php app/Http/Resources/ClientResource.php tests/Feature/ClientAfficherHorairesTest.php
git commit -m "feat: expose afficher_horaires dans l'API client"
```

---

### Task 3 : Le PDF respecte le réglage

C'est le cœur de la fonctionnalité. Le test porte sur le rendu de la vue Blade, en amont de dompdf : `Pdf::output()` renvoie du binaire compressé, inexploitable pour une assertion textuelle.

**Files:**
- Modify: `app/Services/FactureService.php` (dans `getPdf()`, l'appel `Pdf::loadView()` aux lignes 116-121)
- Modify: `resources/views/invoices/pdf.blade.php` (l'en-tête ligne 154, la cellule ligne 165)
- Test: `tests/Feature/FacturePdfHorairesTest.php`

**Interfaces:**
- Consumes: `Client::$afficher_horaires` (tâche 1).
- Produces: la vue `invoices.pdf` exige désormais une variable `$afficherHoraires` (booléen) en plus de `$facture`, `$prestations`, `$client`, `$user`. Tout appelant de cette vue doit la fournir.

- [ ] **Step 1: Écrire les tests qui échouent**

Créer `tests/Feature/FacturePdfHorairesTest.php` :

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
 * Rend la vue du PDF pour un client dont afficher_horaires vaut $afficherHoraires,
 * et renvoie le HTML produit.
 */
function rendreVuePdf(bool $afficherHoraires): string
{
    $user   = User::factory()->create();
    $client = Client::factory()->create([
        'user_id'           => $user->id,
        'afficher_horaires' => $afficherHoraires,
    ]);
    $taux    = TauxHoraire::factory()->create(['user_id' => $user->id, 'taux' => 25]);
    $facture = Facture::factory()->create(['user_id' => $user->id]);

    $prestation = Prestation::factory()->create([
        'user_id'         => $user->id,
        'client_id'       => $client->id,
        'taux_horaire_id' => $taux->id,
        'facture_id'      => $facture->id,
        'horaires'        => '11h45-15h 18h45-2h',
        'heures'          => 9.25,
    ]);

    return view('invoices.pdf', [
        'facture'          => $facture,
        'prestations'      => collect([$prestation->load('tauxHoraire')]),
        'client'           => $client,
        'user'             => $user,
        'afficherHoraires' => $afficherHoraires,
    ])->render();
}

it('affiche la colonne horaires quand le client l\'accepte', function () {
    $html = rendreVuePdf(true);

    expect($html)->toContain('Horaires');
    expect($html)->toContain('11h45-15h 18h45-2h');
});

it('masque la colonne horaires quand le client la refuse', function () {
    $html = rendreVuePdf(false);

    expect($html)->not->toContain('Horaires');
    expect($html)->not->toContain('11h45-15h 18h45-2h');
});

it('conserve les autres colonnes quand les horaires sont masqués', function () {
    $html = rendreVuePdf(false);

    // Les 5 colonnes restantes sont toujours là.
    expect($html)->toContain('Réf.');
    expect($html)->toContain('Date');
    expect($html)->toContain('Qté');
    expect($html)->toContain('PU HT');
    expect($html)->toContain('Total HT');
});
```

> Note : le rendu de la vue exige un `$user` avec `iban`, `name`, `prenom`, `adresse`, `ville`, `code_postal`, `siren`, `nom_societe` renseignés — vérifier que `UserFactory` les remplit. Si l'un manque, le compléter dans l'appel `User::factory()->create([...])` du helper plutôt que de modifier la factory.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `php artisan test --testsuite=Feature tests/Feature/FacturePdfHorairesTest.php`
Expected: le test « affiche » PASSE déjà (la colonne est en dur), les tests « masque » et « conserve » ÉCHOUENT — le HTML contient toujours « Horaires » alors qu'on ne veut plus le voir.

- [ ] **Step 3: Conditionner la colonne dans la vue**

Dans `resources/views/invoices/pdf.blade.php`, entourer l'en-tête (ligne 154) et la cellule (ligne 165) :

```blade
                <thead>
                    <tr>
                        <th>Réf.</th>
                        <th>Date</th>
                        @if ($afficherHoraires)
                        <th>Horaires</th>
                        @endif
                        <th>Qté</th>
                        <th>PU HT</th>
                        <th>Total HT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prestations as $prestation)
                    <tr>
                        <td>{{ $prestation->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($prestation->date)->format('d/m/Y') }}</td>
                        @if ($afficherHoraires)
                        <td>{{ $prestation->horaires }}</td>
                        @endif
                        <td>{{ number_format($prestation->heures, 2, ',', ' ') }}</td>
                        <td>{{ number_format($prestation->tauxHoraire->taux ?? 20, 2, ',', ' ') }} €</td>
                        <td>{{ number_format($prestation->heures * ($prestation->tauxHoraire->taux ?? 20), 2, ',', ' ') }} €</td>
                    </tr>
                    @endforeach
                </tbody>
```

- [ ] **Step 4: Passer la variable depuis le service**

Dans `app/Services/FactureService.php`, méthode `getPdf()`, compléter l'appel `Pdf::loadView()` (lignes 116-121) :

```php
            $pdf = Pdf::loadView('invoices.pdf', [
                'facture'          => $facture,
                'prestations'      => $prestations,
                'client'           => $client,
                'user'             => $user,
                'afficherHoraires' => (bool) $client->afficher_horaires,
            ]);
```

`$client` est déjà chargé ligne 88 (`$facture->prestations->first()->client`) : aucune requête supplémentaire.

- [ ] **Step 5: Lancer les tests et vérifier qu'ils passent**

Run: `php artisan test --testsuite=Feature tests/Feature/FacturePdfHorairesTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 6: Lancer toute la suite pour vérifier qu'aucun appelant de la vue n'a été oublié**

Run: `php artisan test --testsuite=Feature`
Expected: PASS — toute la suite. Un échec ici signalerait un autre appel à `view('invoices.pdf')` sans `$afficherHoraires`.

- [ ] **Step 7: Commit**

```bash
git add app/Services/FactureService.php resources/views/invoices/pdf.blade.php tests/Feature/FacturePdfHorairesTest.php
git commit -m "feat: masque la colonne horaires du PDF selon le reglage du client"
```

---

### Task 4 : La case à cocher dans la fiche client

**Files:**
- Modify: `resources/js/components/clients/ClientFormModal.vue`

**Interfaces:**
- Consumes: la clé `afficher_horaires` renvoyée et acceptée par l'API client (tâche 2).
- Produces: rien — c'est la dernière tâche.

Le formulaire envoie tout `clientData` au store (`addClient` / `updateClient`), donc ajouter la clé à l'objet réactif suffit à la transmettre. Pas de modification du store Pinia.

- [ ] **Step 1: Ajouter la case au formulaire**

Dans `resources/js/components/clients/ClientFormModal.vue`, insérer ce bloc dans le `<form>`, entre le champ SIREN (qui se termine ligne 106) et le bloc « Boutons » (ligne 108) :

```html
          <!-- Affichage des horaires sur la facture PDF -->
          <div class="flex items-start gap-2 pt-2 border-t">
            <input
              type="checkbox"
              id="afficher_horaires"
              v-model="clientData.afficher_horaires"
              class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
            />
            <label for="afficher_horaires" class="text-sm text-gray-700">
              Afficher la colonne « Horaires » sur les factures PDF
              <span class="block text-xs text-gray-500">
                Décochez si ce client ne souhaite pas voir le détail des plages horaires.
              </span>
            </label>
          </div>
```

- [ ] **Step 2: Déclarer le champ dans l'état du formulaire**

Toujours dans le même fichier, ajouter `afficher_horaires: true` aux **deux** objets de `clientData` — la valeur initiale du `ref` (lignes 144-152) et les valeurs par défaut du `watchEffect` (lignes 158-166). Un nouveau client a donc la case cochée ; en édition, `{ ...props.client }` écrase la valeur avec celle venue de l'API.

```js
  const clientData = ref({
    id: null,
    nom: '',
    adresse: '',
    code_postal: '',
    ville: '',
    pays: '',
    siren: '',
    afficher_horaires: true,
  });

  watchEffect(() => {
    clientData.value = props.client
      ? { ...props.client }
      : {
          id: null,
          nom: '',
          adresse: '',
          code_postal: '',
          ville: '',
          pays: '',
          siren: '',
          afficher_horaires: true,
        };
  });
```

- [ ] **Step 3: Vérifier le build du front**

Run: `npm run build`
Expected: build Vite réussi, sans erreur de compilation Vue.

- [ ] **Step 4: Vérifier le comportement de bout en bout**

Dans l'application : éditer un client, décocher la case, enregistrer, rouvrir la fiche — la case doit être restée décochée (l'API renvoie bien `false`). Puis générer le PDF d'une facture de ce client et vérifier que la colonne « Horaires » a disparu et que les cinq autres colonnes occupent la largeur. Faire le même contrôle sur un client resté coché : son PDF est inchangé.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/clients/ClientFormModal.vue
git commit -m "feat: case a cocher pour l'affichage des horaires dans la fiche client"
```

---

## Vérification finale

- [ ] `php artisan test --testsuite=Feature` — toute la suite passe.
- [ ] Un client existant, jamais touché, produit un PDF identique à avant (colonne Horaires présente).
- [ ] Un client avec la case décochée produit un PDF sans la colonne.
