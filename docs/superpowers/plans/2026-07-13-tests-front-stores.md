# Tests front des stores — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Installer Vitest et caractériser le comportement actuel des stores Pinia, pour disposer d'un filet avant d'extraire `apiCall` (dupliqué dans cinq stores, dont un a divergé).

**Architecture :** Des tests de *caractérisation* — ils décrivent ce que le code **fait**, pas ce qu'il devrait faire. Ils doivent passer sur le code d'aujourd'hui **sans qu'une seule ligne des stores soit modifiée**. Au moment du refactor, ce qui reste vert n'a pas bougé ; ce qui casse est exactement ce qu'on a changé.

**Tech Stack :** Vitest 4 (environnement `node`, pas de DOM), Pinia, Vue 3, Vite 7.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-13-tests-front-stores-design.md`
- **INTERDIT de modifier `resources/js/stores/*.js`.** C'est le critère de réussite du plan. Si un test ne passe pas, c'est le TEST qui est faux, pas le store. `git status` ne doit montrer, à la fin, que des fichiers de test, la config, `package.json`, `package-lock.json` et le workflow.
- **Les tests décrivent le comportement EXISTANT, y compris ses défauts et ses divergences.** Ne « corrige » rien en passant. Le store des factures retourne le résultat de `onSuccess` là où les autres retournent la réponse : le test doit **constater** cela, pas le déplorer.
- **Pas de `jsdom` ni de `happy-dom`.** On ne teste que des stores : aucun rendu, donc `environment: 'node'`. N'installe rien d'autre que `vitest`.
- **Piège 1 :** `resources/js/utils.js` appelle `useToast()` **au chargement du module** (pas dans une fonction). Importer un store — qui importe `utils` — déclencherait cet appel hors application Vue. Il FAUT mocker `@/utils` dans chaque fichier de test.
- **Piège 2 :** `resources/js/stores/factures.js` importe le store du dashboard. Chaque test doit activer une instance Pinia (`setActivePinia(createPinia())`) avant d'instancier un store.
- **Piège 3 :** l'alias `@ → resources/js` n'est PAS dans `vite.config.js` — c'est `laravel-vite-plugin` qui l'injecte. Vitest ne le verrait pas : il doit être redéclaré dans `vitest.config.js`.
- Vitest 4 supporte Vite 7 (vérifié : peer dependency `vite: '^6.0.0 || ^7.0.0 || ^8.0.0'`).
- Le backend n'est pas touché : la suite PHP (90 tests) doit rester verte.
- Commits en français, format `type: description`.

**Noms exacts des stores** (à ne pas deviner) :

| Fichier | Export |
|---|---|
| `@/stores/clients` | `useClientsStore` |
| `@/stores/taux-horaires` | `useTauxHorairesStore` |
| `@/stores/prestations` | `usePrestationsStore` |
| `@/stores/factures` | `useInvoicesStore` (en anglais — c'est ainsi, ne le renomme pas) |
| `@/stores/auth` | `useAuthStore` |

**Sur la forme du plan :** la tâche 1 donne le fichier de test **en entier** — c'est la référence. Les tâches 2 et 3 ne répètent que les tests *spécifiques* (les divergences, la logique métier), car les quatre autres fichiers déclinent le même squelette (mêmes mocks, même `beforeEach`, même helper `erreurAxios`) sur des noms de fonctions différents. Recopier quatre fois quatre-vingts lignes quasi identiques rendrait ce plan illisible sans rien apporter. Lis `clients.test.js` et les stores concernés : tout ce qu'il te faut y est.

---

### Task 1 : L'outillage, et le premier store caractérisé

On installe Vitest et on caractérise `clients` — le store « standard », dont l'`apiCall` est partagé à l'identique par `taux-horaires` et `prestations`. S'il passe, l'outillage est bon.

**Files:**
- Modify: `package.json`
- Create: `vitest.config.js`
- Create: `resources/js/stores/clients.test.js`

**Interfaces:**
- Consumes: rien.
- Produces: `npm run test` lance Vitest. Le pattern de mock (`axios` + `@/utils`) et l'amorce Pinia que les tâches 2 et 3 réutilisent.

- [ ] **Step 1: Installer Vitest**

Run: `npm install -D vitest`
Expected: installation réussie, `vitest` apparaît dans les `devDependencies` de `package.json`.

- [ ] **Step 2: Ajouter les scripts npm**

Dans `package.json`, ajouter aux `scripts` :

```json
"test": "vitest run",
"test:watch": "vitest"
```

- [ ] **Step 3: Créer la configuration de test**

Créer `vitest.config.js` :

```js
import { defineConfig } from 'vitest/config';
import { fileURLToPath } from 'node:url';

// Config dédiée, distincte de vite.config.js : celui-ci charge trois plugins
// (Laravel, PWA, Tailwind) inutiles ici et sources de fragilité.
export default defineConfig({
  test: {
    // Aucun rendu de composant : les stores n'ont pas besoin d'un DOM.
    environment: 'node',
    include: ['resources/js/**/*.test.js'],
  },
  resolve: {
    alias: {
      // Dans l'application, cet alias est injecté par laravel-vite-plugin,
      // et non déclaré dans vite.config.js. Vitest ne le verrait donc pas.
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
});
```

- [ ] **Step 4: Écrire les tests du store clients**

Créer `resources/js/stores/clients.test.js` :

```js
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import axios from 'axios';
import { useClientsStore } from '@/stores/clients';
import { notify } from '@/utils';

vi.mock('axios');

// utils.js appelle useToast() AU CHARGEMENT DU MODULE : sans ce mock, importer
// un store planterait hors application Vue.
vi.mock('@/utils', () => ({
  notify: vi.fn(),
  formatDate: vi.fn(),
  formatNombre: vi.fn(),
  formatEuros: vi.fn(),
  validateEmail: vi.fn(),
}));

/** Construit une erreur axios telle que le store la reçoit. */
function erreurAxios(status, data = {}) {
  return { response: { status, data } };
}

describe('store clients — apiCall (le comportement de référence)', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it('retourne la reponse au succes', async () => {
    axios.get.mockResolvedValue({ data: { clients: [{ id: 1, nom: 'EBS' }] } });
    const store = useClientsStore();

    const resultat = await store.fetchClients();

    // Le comportement de référence : apiCall retourne la RÉPONSE.
    expect(resultat).toBeTruthy();
    expect(resultat.data.clients).toHaveLength(1);
    expect(store.clients).toHaveLength(1);
  });

  it('redescend le chargement a false apres un succes', async () => {
    axios.get.mockResolvedValue({ data: { clients: [] } });
    const store = useClientsStore();

    await store.fetchClients();

    expect(store.loading.fetch).toBe(false);
  });

  it('redescend le chargement a false meme apres un echec', async () => {
    axios.get.mockRejectedValue(erreurAxios(500, { message: 'Boom' }));
    const store = useClientsStore();

    await store.fetchClients();

    expect(store.loading.fetch).toBe(false);
  });

  it('range un 422 dans validationErrors, SANS notifier', async () => {
    axios.post.mockRejectedValue(
      erreurAxios(422, { errors: { nom: ['Le nom est obligatoire.'] } })
    );
    const store = useClientsStore();

    const resultat = await store.addClient({ nom: '' });

    expect(store.errors.validationErrors).toEqual({ nom: ['Le nom est obligatoire.'] });
    // Un 422 ne déclenche AUCUN toast : c'est ce qui a rendu un bloc d'erreur
    // inaffichable dans la modale de facture.
    expect(notify).not.toHaveBeenCalled();
    expect(resultat).toBeFalsy();
  });

  it('range les autres erreurs dans errors[operation] ET notifie', async () => {
    axios.post.mockRejectedValue(erreurAxios(500, { message: 'Erreur serveur' }));
    const store = useClientsStore();

    const resultat = await store.addClient({ nom: 'EBS' });

    expect(store.errors.add).toBe('Erreur serveur');
    expect(notify).toHaveBeenCalledWith('error', 'Erreur serveur');
    expect(resultat).toBeFalsy();
  });

  it('vide l\'erreur precedente de l\'operation avant un nouvel appel', async () => {
    const store = useClientsStore();

    axios.post.mockRejectedValueOnce(erreurAxios(500, { message: 'Erreur serveur' }));
    await store.addClient({ nom: 'EBS' });
    expect(store.errors.add).toBe('Erreur serveur');

    axios.post.mockResolvedValueOnce({ data: { client: { id: 1 }, message: 'Créé' } });
    await store.addClient({ nom: 'EBS' });

    expect(store.errors.add).toBeNull();
  });
});
```

- [ ] **Step 5: Lancer les tests**

Run: `npm run test`
Expected: PASS — 6 tests.

Si un test échoue, **ne touche PAS au store** : c'est ton test qui décrit mal le comportement réel. Lis `resources/js/stores/clients.js` et corrige le test.

- [ ] **Step 6: Vérifier qu'aucun store n'a été modifié**

Run: `git status --short resources/js/stores/`
Expected: seul `clients.test.js` (nouveau fichier) apparaît. **Aucun `.js` de store modifié.** C'est le critère de réussite du plan.

- [ ] **Step 7: Commit**

```bash
git add package.json package-lock.json vitest.config.js resources/js/stores/clients.test.js
git commit -m "test: installe vitest et caracterise le store clients"
```

---

### Task 2 : Les quatre autres stores, divergences comprises

**Files:**
- Create: `resources/js/stores/taux-horaires.test.js`
- Create: `resources/js/stores/prestations.test.js`
- Create: `resources/js/stores/factures.test.js`
- Create: `resources/js/stores/auth.test.js`

**Interfaces:**
- Consumes: la config et le pattern de mock de la tâche 1.
- Produces: rien (la tâche 3 est indépendante).

Le cœur de cette tâche : **figer les deux divergences**.

- `auth` **relance l'erreur** (`throw err` dans son `catch`). Les quatre autres ne relancent pas.
- `factures` retourne **le résultat de `onSuccess`**, là où les autres retournent la réponse. C'est la divergence qui a produit le bug d'`addInvoice` (qui ne retournait rien, donc la modale se fermait même en cas d'échec). Le test doit la **constater** telle quelle : il devra être modifié lors du refactor, et c'est précisément ce qui documentera le changement.

- [ ] **Step 1: Caractériser taux-horaires et prestations (l'apiCall standard)**

Ces deux stores ont un `apiCall` **identique caractère pour caractère** à celui de `clients`. Lis-les pour connaître les noms exacts de leurs fonctions et les clés de leurs réponses (`resources/js/stores/taux-horaires.js`, `resources/js/stores/prestations.js`).

Créer `resources/js/stores/taux-horaires.test.js` et `resources/js/stores/prestations.test.js`, sur le modèle exact de `clients.test.js` (même en-tête de mocks, même `beforeEach`, même helper `erreurAxios`). Pour chacun, couvrir :

- le succès retourne la **réponse** ;
- `loading[operation]` redescend à `false` au succès **et** à l'échec ;
- un 422 va dans `errors.validationErrors`, **sans** `notify` ;
- une erreur 500 va dans `errors[operation]` **avec** `notify`.

- [ ] **Step 2: Caractériser le store des factures (LA divergence)**

Créer `resources/js/stores/factures.test.js`. Lis d'abord `resources/js/stores/factures.js` pour les noms exacts.

Points à couvrir, en plus du 422 / 500 / loading :

```js
it('DIVERGENCE : apiCall retourne le resultat de onSuccess, pas la reponse', async () => {
  // Les quatre autres stores font `if (onSuccess) onSuccess(response); return response;`
  // Celui-ci fait `return onSuccess ? onSuccess(response) : response;`
  // addInvoice retourne donc ce que retourne SON onSuccess — un `true` explicite,
  // ajouté précisément parce que sans lui l'appelant recevait `undefined` et ne
  // pouvait pas distinguer un succès d'un échec.
  axios.post.mockResolvedValue({ data: { facture: { id: 1 }, message: 'Créée' } });
  const store = useInvoicesStore();

  const resultat = await store.addInvoice({ prestations: [1] });

  expect(resultat).toBe(true);   // et NON la réponse axios
});

it('addInvoice retourne une valeur falsy en cas d\'echec', async () => {
  axios.post.mockRejectedValue({ response: { status: 422, data: { errors: { prestations: ['Déjà facturée.'] } } } });
  const store = useInvoicesStore();

  const resultat = await store.addInvoice({ prestations: [1] });

  // C'est ce qui permet à la modale de ne PAS se fermer sur un échec.
  expect(resultat).toBeFalsy();
  expect(store.errors.validationErrors).toEqual({ prestations: ['Déjà facturée.'] });
});

it('paid indexe la cle de chargement par facture', async () => {
  // Une clé unique ("paid") ferait clignoter le bouton de TOUTES les lignes.
  axios.patch.mockResolvedValue({ data: { facture: { id: 12, statut: 'payé' }, message: 'Payée' } });
  const store = useInvoicesStore();

  await store.paid(12);

  expect(store.loading.paid_12).toBe(false);   // la clé existe, indexée
  expect(store.loading.paid).toBeUndefined();  // et surtout : PAS de clé globale
});
```

> Note : `factures.js` importe le store du dashboard. L'amorce `setActivePinia(createPinia())` du `beforeEach` suffit — ne mocke pas le store du dashboard, laisse-le s'instancier.

- [ ] **Step 3: Caractériser le store auth (il RELANCE l'erreur)**

Créer `resources/js/stores/auth.test.js`. Lis d'abord `resources/js/stores/auth.js`.

La spécificité à figer :

```js
it('DIVERGENCE : auth relance l\'erreur apres l\'avoir traitee', async () => {
  // Son catch se termine par `throw err;` — les quatre autres stores ne relancent pas.
  // Ses appelants peuvent donc l'attraper. Retirer ce throw les casserait en silence.
  axios.get.mockResolvedValue({});   // /sanctum/csrf-cookie
  axios.post.mockRejectedValue({ response: { status: 401, data: { message: 'Identifiants invalides' } } });
  const store = useAuthStore();

  await expect(store.login('a@b.c', 'mauvais')).rejects.toBeDefined();

  expect(store.errors.login).toBe('Identifiants invalides');
});
```

> `login` appelle `axios.get('/sanctum/csrf-cookie')` avant le `POST` : il faut donc mocker les deux.

- [ ] **Step 4: Lancer toute la suite**

Run: `npm run test`
Expected: PASS — tous les fichiers de test.

Rappel : si un test échoue, **le store a raison, pas le test**. Ne modifie aucun store.

- [ ] **Step 5: Vérifier qu'aucun store n'a été modifié**

Run: `git status --short resources/js/stores/`
Expected: uniquement des fichiers `.test.js` (nouveaux). Aucun `.js` de store modifié.

- [ ] **Step 6: Commit**

```bash
git add resources/js/stores/*.test.js
git commit -m "test: caracterise les stores taux-horaires, prestations, factures et auth"
```

---

### Task 3 : La logique métier, et le job CI

**Files:**
- Create: `resources/js/stores/logique-metier.test.js`
- Modify: `.github/workflows/ci.yml`

**Interfaces:**
- Consumes: la config de la tâche 1.
- Produces: rien (dernière tâche).

On couvre la logique qu'on a déjà vue se tromper : filtres, tri, sélection.

- [ ] **Step 1: Caractériser la logique métier des stores**

Créer `resources/js/stores/logique-metier.test.js`. Lis `resources/js/stores/prestations.js` et `resources/js/stores/factures.js` pour les noms exacts.

À couvrir :

**Store des prestations :**
- `unbilledPrestations` ne retient que les prestations dont `facture_id` est `null`.
- `filteredPrestations` : filtres `month_year`, `client_id`, `taux_horaire_id`.
- `isAnyFilterActive` : `false` quand tous les filtres sont des chaînes vides, `true` dès qu'un seul est renseigné.

**Store des factures :**
- `filteredInvoices` est triée par **`id` décroissant** — jamais par `created_at`, que l'API renvoie au format `d/m/Y H:i:s`, intriable en JavaScript. Écris un test qui le prouve : injecte des factures dont l'ordre par `id` contredit l'ordre du tableau source.
- Le filtre par **statut**.
- Le filtre par **client** (il compare `invoice.prestations[0].client_id`).
- Le filtre par **mois** : une facture est retenue si **au moins une** de ses prestations tombe dans le mois (`prestation.date` est au format `Y-m-d`, la comparaison est un préfixe).
- Une facture **sans prestation** ne fait pas planter les filtres (l'accès est protégé par `?.`).

Pour alimenter ces stores sans passer par l'API, injecte directement dans leur état : `store.prestations = [...]` / `store.invoices = [...]`. Les `watch` qui recalculent les listes filtrées sont **asynchrones** : après avoir modifié l'état, `await nextTick()` (importé de `vue`) avant d'asserter, sinon tu lis la valeur d'avant.

- [ ] **Step 2: Lancer la suite**

Run: `npm run test`
Expected: PASS — tous les tests.

- [ ] **Step 3: Ajouter le job CI**

Dans `.github/workflows/ci.yml`, ajouter un **second job**, à côté du job `tests` existant (ne le modifie pas) :

```yaml
  tests-front:
    name: Tests (front)
    runs-on: ubuntu-latest

    steps:
      - name: Checkout du code
        uses: actions/checkout@v4

      - name: Installation de Node 22
        uses: actions/setup-node@v4
        with:
          node-version: '22'
          cache: 'npm'

      - name: Installation des dépendances npm
        run: npm ci

      - name: Exécution des tests front
        run: npm run test
```

- [ ] **Step 4: Valider la syntaxe YAML**

Run: `npx --yes js-yaml .github/workflows/ci.yml > /dev/null && echo "YAML valide"`
Expected: `YAML valide`. Une erreur d'indentation dans un workflow ne se voit qu'une fois poussé.

- [ ] **Step 5: Vérifier que le backend n'a pas bougé**

Run: `php artisan test`
Expected: PASS — 90 tests (1 ignoré). Rien de ce plan ne touche au backend ; un échec ici signalerait une erreur.

- [ ] **Step 6: Commit**

```bash
git add resources/js/stores/logique-metier.test.js .github/workflows/ci.yml
git commit -m "test: caracterise la logique metier des stores et ajoute le job CI front"
```

---

## Vérification finale

- [ ] `npm run test` — tous les tests passent.
- [ ] **`git diff main --stat -- resources/js/stores/*.js`** — **AUCUN store modifié**. C'est le critère de réussite du plan : les tests décrivent le code tel qu'il est. S'il a fallu toucher un store, le filet ne vaut rien.
- [ ] `php artisan test` — 90 tests verts (backend intact).
- [ ] Sur la PR : la CI affiche **deux jobs verts et distincts** — « Tests (Pest) » et « Tests (front) ».
