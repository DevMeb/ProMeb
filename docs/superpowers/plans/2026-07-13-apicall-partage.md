# Extraire `apiCall` en fabrique partagée — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Supprimer les cinq copies de `apiCall` / `clearErrors` / `setLoading` (~140 lignes dupliquées) au profit d'une fabrique unique, en alignant au passage le store des factures, qui avait divergé.

**Architecture :** Une fabrique `creerApiCall({ errors, loading, relancerLesErreurs })` rend à chaque store son `apiCall`, `clearErrors` et `setLoading`. Elle porte le comportement des quatre stores conformes ; c'est `factures` qui s'aligne. Les 52 tests de caractérisation (PR #29) sont le juge : ce qui reste vert n'a pas bougé.

**Tech Stack :** Vue 3, Pinia, Vitest 4.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-13-apicall-partage-design.md`
- **Le filet définit ce qui a le droit de casser.** Les SEULS tests autorisés à rougir sont ceux de `resources/js/stores/factures.test.js` qui décrivent ses divergences. **Tout autre test rouge est une régression** : on reprend le CODE, jamais le test. « Ajuster » un test jusqu'au vert est une faute — le refactor n'aurait alors rien prouvé.
- **Deux dettes sont figées par des tests et NE DOIVENT PAS être corrigées ici** :
  1. `auth.updateUser()` ne met jamais à jour `store.user` (le paramètre `user` masque la ref du store). Son test l'atteste — il doit **rester vert**.
  2. `errors.validationErrors` n'est jamais vidé par `clearErrors`. Son test l'atteste — il doit **rester vert**.
  Les corriger ici mélangerait un refactor (aucun changement de comportement) et des corrections de bugs. Si l'un de ces tests casse, c'est qu'un comportement a changé sans qu'on l'ait décidé.
- **Le comportement de référence est celui des quatre stores conformes** : `if (onSuccess) onSuccess(response); return response;`. C'est `factures` qui s'aligne, pas l'inverse.
- **`auth.login()` n'utilise PAS `apiCall`** (code écrit à la main, ne relance rien, message en dur, toast direct). Seul `updateUser()` y passe — et c'est lui, et lui seul, que le `throw` du `catch` sert. Ne touche pas à `login`.
- `dashboard.js` n'a pas d'`apiCall` : il reste à l'écart.
- Vérification : `npm run test` (52 tests) et `php artisan test` (90 tests, backend intact — rien ici ne le touche).
- Commits en français, format `type: description`.

---

### Task 1 : La fabrique, et le store de référence

On crée la fabrique et on migre `clients` — le store dont l'`apiCall` **est** le comportement de référence. **Ses tests doivent rester verts sans qu'on y touche** : c'est la preuve que la fabrique reproduit fidèlement l'original.

**Files:**
- Create: `resources/js/stores/apiCall.js`
- Modify: `resources/js/stores/clients.js`

**Interfaces:**
- Consumes: rien.
- Produces: `creerApiCall({ errors, loading, relancerLesErreurs })` → `{ apiCall, clearErrors, setLoading }`. Les tâches 2, 3 et 4 l'utilisent.

- [ ] **Step 1: Créer la fabrique**

Créer `resources/js/stores/apiCall.js` :

```js
import { notify } from '@/utils';

/**
 * Fabrique l'enveloppe des appels API d'un store : chargement, erreurs,
 * notifications.
 *
 * Cette fonction était copiée dans cinq stores. Trois copies étaient identiques
 * caractère pour caractère, une avait divergé — et cette divergence a produit un
 * bug réel (une modale qui se fermait sur un échec, effaçant la saisie).
 *
 * @param {object} refs
 * @param {import('vue').Ref<object>} refs.errors   Les erreurs du store, par opération.
 * @param {import('vue').Ref<object>} refs.loading  Les états de chargement, par opération.
 * @param {boolean} [refs.relancerLesErreurs=false] Relance l'erreur après l'avoir
 *   traitée. Seul le store `auth` l'active : son `updateUser()` compte dessus.
 */
export function creerApiCall({ errors, loading, relancerLesErreurs = false }) {
  function clearErrors(operation) {
    if (operation) {
      errors.value[operation] = null;
    } else {
      errors.value = {};
    }
  }

  function setLoading(operation, state) {
    loading.value[operation] = state;
  }

  /**
   * @param {object} options
   * @param {string} options.operation   Clé sous laquelle chargement et erreur sont rangés.
   * @param {Function} options.request   L'appel axios.
   * @param {Function} [options.onSuccess] Effets de bord au succès. Sa valeur de
   *   retour est IGNORÉE : apiCall retourne toujours la réponse.
   * @param {Function} [options.onError]   Gestion d'erreur sur mesure, qui remplace
   *   le traitement par défaut. Seul `getInvoicePdf` en a besoin (réponse en blob).
   * @returns {Promise<*>} La réponse au succès, `undefined` en cas d'échec.
   */
  async function apiCall({ operation, request, onSuccess, onError }) {
    clearErrors(operation);
    setLoading(operation, true);

    try {
      const response = await request();
      if (onSuccess) onSuccess(response);
      return response;
    } catch (err) {
      if (onError) {
        // `await` : onError peut être asynchrone (getInvoicePdf lit un blob).
        // Sans lui, son message d'erreur serait publié APRÈS qu'apiCall a résolu.
        await onError(err);
      } else if (err.response?.status === 422) {
        errors.value.validationErrors = err.response.data.errors;
      } else {
        errors.value[operation] = err.response?.data?.message || "Une erreur est survenue.";
        notify('error', errors.value[operation]);
      }

      if (relancerLesErreurs) throw err;
    } finally {
      setLoading(operation, false);
    }
  }

  return { apiCall, clearErrors, setLoading };
}
```

> Note : `clearErrors` ne vide **pas** `errors.validationErrors` — c'est une dette
> connue, figée par un test. Ne la « corrige » pas ici.

- [ ] **Step 2: Migrer le store clients**

Dans `resources/js/stores/clients.js` :

1. Ajouter l'import : `import { creerApiCall } from '@/stores/apiCall';`
2. **Supprimer** les trois fonctions `clearErrors`, `setLoading` et `apiCall` (elles se suivent, juste après les `ref`).
3. Les remplacer, immédiatement après `const loading = ref({});`, par :

```js
  const { apiCall, clearErrors, setLoading } = creerApiCall({ errors, loading });
```

4. Vérifier que l'import `notify` de `@/utils` est **toujours utilisé** ailleurs dans le fichier (les `onSuccess` l'appellent pour les toasts de succès). S'il ne l'est plus, le retirer ; s'il l'est, le garder.

- [ ] **Step 3: Lancer les tests**

Run: `npm run test`
Expected: PASS — **52 tests**, dont les 7 de `clients.test.js`, **sans qu'un seul test ait été modifié**.

C'est le résultat qui compte : la fabrique reproduit exactement le comportement du store de référence. Si un test de `clients` casse, **la fabrique est fausse** — corrige-la, ne touche pas au test.

- [ ] **Step 4: Vérifier que la fabrique sert vraiment**

Preuve de bonne foi. Casse temporairement la fabrique : dans `apiCall.js`, remplace `setLoading(operation, true);` par rien (supprime la ligne).

Run: `npm run test`
Expected: **ÉCHEC** — le test « monte le chargement a true PENDANT la requete » rougit. C'est la preuve que le filet couvre le code que tu viens d'extraire.

Puis **restaure la ligne** et relance : 52 tests verts.

- [ ] **Step 5: Commit**

```bash
git add resources/js/stores/apiCall.js resources/js/stores/clients.js
git commit -m "refactor: extrait apiCall en fabrique partagee, migre le store clients"
```

---

### Task 2 : Les deux autres stores conformes

`taux-horaires` et `prestations` ont un `apiCall` **identique caractère pour caractère** à celui de `clients`. Leur migration est mécanique, et leurs tests doivent rester verts sans modification.

**Files:**
- Modify: `resources/js/stores/taux-horaires.js`
- Modify: `resources/js/stores/prestations.js`

**Interfaces:**
- Consumes: `creerApiCall({ errors, loading })` (tâche 1).
- Produces: rien.

- [ ] **Step 1: Migrer taux-horaires**

Dans `resources/js/stores/taux-horaires.js` :

1. Ajouter `import { creerApiCall } from '@/stores/apiCall';`
2. Supprimer les fonctions `clearErrors`, `setLoading` et `apiCall`.
3. Les remplacer, juste après `const loading = ref({});`, par :

```js
  const { apiCall, clearErrors, setLoading } = creerApiCall({ errors, loading });
```

4. Vérifier si l'import `notify` est toujours utilisé dans le fichier (les toasts de succès des `onSuccess`). Le retirer seulement s'il ne l'est plus.

- [ ] **Step 2: Migrer prestations**

Même opération dans `resources/js/stores/prestations.js`.

Attention : ce store a de la logique métier autour (`activeFilters`, `filteredPrestations`, `unbilledPrestations`, un `watch`). **N'y touche pas.** Tu ne remplaces que les trois fonctions.

- [ ] **Step 3: Lancer les tests**

Run: `npm run test`
Expected: PASS — 52 tests, **aucun test modifié**. Si un test de `taux-horaires` ou de `prestations` casse, c'est une régression : reprends le code.

- [ ] **Step 4: Commit**

```bash
git add resources/js/stores/taux-horaires.js resources/js/stores/prestations.js
git commit -m "refactor: migre les stores taux-horaires et prestations vers la fabrique"
```

---

### Task 3 : Le store auth, qui relance ses erreurs

**Files:**
- Modify: `resources/js/stores/auth.js`

**Interfaces:**
- Consumes: `creerApiCall({ errors, loading, relancerLesErreurs })` (tâche 1).
- Produces: rien.

Ce store a une particularité : son `catch` se termine par `throw err;`. Il **relance** l'erreur après l'avoir traitée.

**Le piège, que les tests ont désamorcé :** ce `throw` **ne sert pas à `login`**. Contrairement à ce qu'on pourrait croire, `login()` n'utilise **pas** `apiCall` — c'est du code écrit à la main, avec son propre `try/catch`, son message d'erreur en dur et un toast importé directement. **Seul `updateUser()` passe par `apiCall`** dans ce store, et c'est lui, et lui seul, que le `throw` sert.

**Ne touche pas à `login()` ni à `logout()`.** Tu ne remplaces que les trois fonctions.

- [ ] **Step 1: Migrer auth**

Dans `resources/js/stores/auth.js` :

1. Ajouter `import { creerApiCall } from '@/stores/apiCall';`
2. Supprimer les fonctions `clearErrors`, `setLoading` et `apiCall`.
3. Les remplacer, juste après la déclaration de `loading`, par :

```js
  // relancerLesErreurs : ce store relance l'erreur après l'avoir traitée.
  // C'est updateUser() — la seule opération de ce store qui passe par apiCall —
  // qui en dépend. login() a son propre try/catch et ne passe pas par ici.
  const { apiCall, clearErrors, setLoading } = creerApiCall({
    errors,
    loading,
    relancerLesErreurs: true,
  });
```

4. Vérifier si l'import `notify` est toujours utilisé. Attention : ce store importe aussi `useToast` de `vue-toastification` **directement** (pour `login`) — ne le retire pas.

- [ ] **Step 2: Lancer les tests**

Run: `npm run test`
Expected: PASS — 52 tests, **aucun test modifié**.

Deux tests méritent ton attention ; ils doivent **rester verts** :
- celui qui prouve qu'`updateUser` **relance** l'erreur (si tu as oublié `relancerLesErreurs: true`, il rougira) ;
- celui qui fige le bug connu : **`updateUser` ne met jamais à jour `store.user`** (le paramètre masque la ref). **Ne corrige pas ce bug** — s'il devient vert autrement, c'est que tu as changé un comportement sans le décider.

- [ ] **Step 3: Commit**

```bash
git add resources/js/stores/auth.js
git commit -m "refactor: migre le store auth vers la fabrique (avec relance des erreurs)"
```

---

### Task 4 : Le store des factures — la divergence

C'est ici, et **seulement ici**, que des tests ont le droit de casser.

**Files:**
- Modify: `resources/js/stores/factures.js`
- Modify: `resources/js/stores/factures.test.js`

**Interfaces:**
- Consumes: `creerApiCall({ errors, loading })` (tâche 1).
- Produces: rien (dernière tâche).

Son `apiCall` faisait `return onSuccess ? onSuccess(response) : response;` — il retournait ce que retournait `onSuccess`. En s'alignant sur la fabrique, il retourne désormais **la réponse**. Trois conséquences, toutes assumées :

1. **`addInvoice`** avait un `return true;` explicite à la fin de son `onSuccess` — un pansement ajouté parce que, sans lui, l'appelant recevait `undefined` et ne pouvait pas distinguer un succès d'un échec. **Ce pansement devient inutile : retire-le.** `FactureFormModal` fait `if (succes) close()` : la réponse axios étant *truthy*, il continue de fonctionner.
2. **`getInvoicePdf`** construisait l'URL de son blob **dans** `onSuccess` et comptait sur le retour d'`apiCall` pour la récupérer. Il doit désormais la construire **après** l'appel.
3. **`fetchInvoices`** retournait `undefined` (son `onSuccess` ne retourne rien). Il retournera la réponse. Aucun appelant n'en dépend.

- [ ] **Step 1: Migrer le store**

Dans `resources/js/stores/factures.js` :

1. Ajouter `import { creerApiCall } from '@/stores/apiCall';`
2. Supprimer `clearErrors`, `setLoading` et `apiCall`, et les remplacer, juste après `const loading = ref({});`, par :

```js
  const { apiCall, clearErrors, setLoading } = creerApiCall({ errors, loading });
```

3. Dans `addInvoice`, **supprimer le `return true;`** à la fin de son `onSuccess` (ainsi que le commentaire qui l'accompagne : il expliquait un pansement qui n'a plus lieu d'être).

4. Récrire `getInvoicePdf` pour qu'il construise l'URL **après** l'appel. Le `onSuccess` disparaît (c'est lui qui construisait l'URL) ; le `onError` est repris **à l'identique**, ses `return "";` en moins — devenus inutiles puisque c'est `getInvoicePdf` qui retourne `""` en cas d'échec :

```js
  async function getInvoicePdf(invoiceId) {
    const response = await apiCall({
      operation: "pdf",
      request: () => axios.get(`/api/factures/${invoiceId}/pdf`, { responseType: "blob" }),
      onError: async (err) => {
        // 1) Erreurs réseau (aucune réponse HTTP)
        if (!err.response) {
          const msg =
            err.code === "ECONNABORTED"
              ? "Délai d’attente dépassé. Vérifiez votre connexion et réessayez."
              : "Impossible de contacter le serveur. Vérifiez votre connexion Internet.";

          errors.value.pdf = msg;   // modale
          notify("error", msg);     // toast
          return;
        }

        const status = err.response.status;
        const contentType = (err.response.headers?.["content-type"] || "").toLowerCase();

        // Helper: extraire un message JSON même si responseType=blob
        const readJsonMessage = async () => {
          if (!contentType.includes("application/json")) return null;

          const text = await err.response.data.text();
          try {
            const payload = JSON.parse(text);
            return payload?.message || null;
          } catch {
            return null;
          }
        };

        // 2) Erreur métier "profil incomplet" (ou autre validation)
        if (status === 422 || status === 403) {
          const msg =
            (await readJsonMessage()) ||
            "Votre profil est incomplet. Complétez vos informations dans les paramètres.";

          errors.value.pdf = msg;   // modale
          notify("error", msg);     // toast
          return;
        }

        // 3) Autres erreurs HTTP (serveur, permissions, etc.)
        const msg =
          (await readJsonMessage()) ||
          "Erreur technique lors de la génération du PDF. Réessayez dans quelques instants.";

        errors.value.pdf = msg;     // modale
        notify("error", msg);       // toast
      },
    });

    // L'URL est construite APRÈS l'appel : apiCall retourne désormais la réponse,
    // et non plus ce que retourne onSuccess.
    if (!response) return "";

    return URL.createObjectURL(new Blob([response.data], { type: "application/pdf" }));
  }
```

**Ne reformule pas cette gestion d'erreur, ne la simplifie pas** : elle distingue l'erreur réseau, le profil incomplet (422/403) et l'erreur technique, et sait lire un message JSON dans une réponse en `blob`. Ce n'est pas l'objet de ce refactor.

- [ ] **Step 2: Lancer les tests et CONSTATER les échecs attendus**

Run: `npm run test`
Expected: **ÉCHEC** — et c'est le résultat voulu. Les tests qui doivent rougir sont ceux de `factures.test.js` qui décrivent la divergence :
- « DIVERGENCE : apiCall retourne le resultat de onSuccess, pas la reponse » ;
- ceux qui attendent `true` d'`addInvoice`.

**Vérifie que SEULS ces tests-là cassent.** Si un test d'un autre store rougit, ou si un test de `factures` sans rapport avec la divergence rougit, c'est une **régression** : reprends le code, pas le test.

- [ ] **Step 3: Mettre à jour les tests de la divergence**

Dans `resources/js/stores/factures.test.js`, récris les tests concernés pour qu'ils décrivent le **nouveau** comportement — désormais aligné sur les quatre autres stores :

- `apiCall` retourne **la réponse** (et non le résultat de `onSuccess`) ;
- `addInvoice` retourne donc la réponse, qui est *truthy* — ce qui suffit à `FactureFormModal` pour décider de fermer sa modale ;
- `addInvoice` retourne toujours une valeur *falsy* en cas d'échec (ce test-là ne change pas : c'est ce qui empêche la modale de se fermer sur un échec).

Renomme les tests : « DIVERGENCE » n'a plus lieu d'être, puisqu'elle a disparu. Le commentaire du test peut rappeler ce qu'elle était et le bug qu'elle a causé — c'est une trace utile.

- [ ] **Step 4: Lancer les tests**

Run: `npm run test`
Expected: PASS — 52 tests.

- [ ] **Step 5: Vérifier que la duplication a bien disparu**

Run: `grep -c "async function apiCall" resources/js/stores/*.js`
Expected: `apiCall.js:1` et **0 pour tous les autres**. Plus aucune copie.

Run: `git diff main --stat -- resources/js/stores/`
Expected: un solde **négatif** d'environ 110 lignes sur les stores (hors fichiers de test).

- [ ] **Step 6: Vérifier le backend et le build**

Run: `php artisan test`
Expected: PASS — 90 tests. Rien ici ne touche au backend ; un échec signalerait une erreur.

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 7: Commit**

```bash
git add resources/js/stores/factures.js resources/js/stores/factures.test.js
git commit -m "refactor: aligne le store des factures sur la fabrique partagee"
```

---

## Vérification finale

- [ ] `npm run test` — 52 tests verts.
- [ ] `php artisan test` — 90 tests verts.
- [ ] `npm run build` — passe.
- [ ] **Seuls les tests de `factures.test.js` décrivant la divergence ont été modifiés.** Vérifier avec `git diff main --name-only -- 'resources/js/stores/*.test.js'` : seul `factures.test.js` doit apparaître. Si un autre fichier de test a été touché, c'est qu'on a maquillé une régression.
- [ ] **Les deux dettes sont toujours figées** : le test qui atteste que `updateUser` ne met pas à jour `store.user`, et celui qui atteste que `errors.validationErrors` n'est jamais vidé, sont **toujours verts et inchangés**.
- [ ] Contrôle manuel dans l'application : se connecter, créer un client, créer une facture, **afficher un PDF** (le seul chemin dont le code change réellement), marquer une facture payée.
