# Solder les quatre dettes du front — Plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Corriger quatre défauts qui donnent l'impression d'un logiciel qui ment : un profil qui ne se met pas à jour, une modale qui se ferme sur un échec, des erreurs de validation périmées, et une exception non attrapée.

**Architecture :** Deux corrections dans les stores (le shadowing d'`updateUser`, le vidage de `validationErrors`), une simplification de conception (supprimer le `throw` d'`auth`, devenu un vestige depuis que `apiCall` retourne la réponse), et deux corrections d'appelants. Les tests de caractérisation qui figent ces dettes **doivent casser** — c'est le signal qui délimite le changement.

**Tech Stack :** Vue 3, Pinia, Vitest 4.

## Global Constraints

- Spec de référence : `docs/superpowers/specs/2026-07-13-dettes-front-design.md`
- **Les tests qui figent les dettes VONT casser, et c'est voulu.** Ce sont des tests de caractérisation : ils attestaient d'un comportement qu'on décide maintenant de changer. Ils doivent être **récrits** pour décrire le nouveau comportement.
- **Tout autre test rouge est une régression** : on reprend le CODE, jamais le test. « Ajuster » un test hors de cette liste serait une faute.
- Les tests concernés, par leur nom exact :
  - `resources/js/stores/auth.test.js` → `BUG CONNU (fige, non corrige) : updateUser ne met jamais a jour store.user`
  - `resources/js/stores/auth.test.js` → `DIVERGENCE : apiCall relance l'erreur apres l'avoir traitee`
  - `resources/js/stores/auth.test.js` → `range un 422 dans validationErrors, SANS notifier — et relance quand meme`
  - `resources/js/stores/clients.test.js` → `DETTE FIGEE (non corrigee) : errors.validationErrors n'est jamais vide, meme apres un succes ulterieur`
  - (d'autres tests d'`auth` peuvent casser si la suppression du `throw` change leur déroulé — vérifier au cas par cas qu'il s'agit bien de la relance, et pas d'autre chose)
- Vérifications : `npm run test` (53 tests) et `npm run build`. Ne pas lancer `php artisan test` : le backend n'est pas touché.
- Le projet n'a **pas de tests de composants** : `FactureDeleteModal`, `FactureFormModal` et `Profile.vue` sont vérifiés par le build et un contrôle manuel.
- Commits en français, format `type: description`.

---

### Task 1 : Le profil qui ne se met pas à jour

**Files:**
- Modify: `resources/js/stores/auth.js`
- Modify: `resources/js/stores/auth.test.js`

**Interfaces:**
- Consumes: rien.
- Produces: `updateUser(donnees)` met désormais à jour `store.user`. La tâche 3 (`Profile.vue`) s'appuie dessus.

Le paramètre `user` de `updateUser(user)` **masque la ref `user` du store**. Le `.value` est donc posé sur l'argument, et `store.user` n'est jamais mis à jour : l'utilisateur enregistre son profil, voit le toast, et l'en-tête garde l'ancien nom jusqu'au rechargement.

- [ ] **Step 1: Récrire le test qui fige le bug**

Dans `resources/js/stores/auth.test.js`, le test nommé `BUG CONNU (fige, non corrige) : updateUser ne met jamais a jour store.user` atteste du bug. Récris-le pour qu'il décrive le comportement **corrigé** : `store.user` **est** mis à jour après `updateUser`.

Renomme-le (« BUG CONNU » n'a plus lieu d'être). Son commentaire peut rappeler ce qu'était le bug — c'est une trace utile.

```js
it('updateUser met a jour store.user', async () => {
  // Le paramètre s'appelait `user`, comme la ref du store : il la masquait,
  // et le `.value` était posé sur l'argument. store.user n'était jamais mis à jour.
  axios.put.mockResolvedValue({
    data: { user: { id: 1, name: 'Nouveau nom' }, message: 'Profil mis à jour' },
  });
  const store = useAuthStore();

  await store.updateUser({ name: 'Nouveau nom' });

  expect(store.user).toEqual({ id: 1, name: 'Nouveau nom' });
});
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

Run: `npm run test`
Expected: ce test ÉCHOUE (`store.user` vaut `null`). C'est le bug, reproduit.

- [ ] **Step 3: Corriger le shadowing**

Dans `resources/js/stores/auth.js`, renommer le paramètre d'`updateUser` pour qu'il ne masque plus la ref du store :

```js
  async function updateUser(donnees) {
    return apiCall({
      operation: 'update',
      request: () => axios.put(`/api/user/`, donnees),
      onSuccess: (response) => {
        user.value = response.data.user;   // la ref du store, enfin
        notify('success', response.data.message);
      },
    });
  }
```

- [ ] **Step 4: Lancer les tests**

Run: `npm run test`
Expected: PASS — 53 tests. Si un autre test casse, c'est une régression : reprends le code.

- [ ] **Step 5: Commit**

```bash
git add resources/js/stores/auth.js resources/js/stores/auth.test.js
git commit -m "fix: updateUser met enfin a jour l'utilisateur du store"
```

---

### Task 2 : Les erreurs de validation périmées

**Files:**
- Modify: `resources/js/stores/apiCall.js`
- Modify: `resources/js/stores/clients.test.js`
- Modify: `resources/js/components/factures/FactureFormModal.vue`

**Interfaces:**
- Consumes: rien.
- Produces: `apiCall` vide `errors.validationErrors` avant chaque appel. Le contournement de `FactureFormModal` devient inutile.

`clearErrors(operation)` ne remet à `null` que `errors[operation]`. `errors.validationErrors` **survit indéfiniment** et peut réapparaître dans une modale suivante. `FactureFormModal` contourne déjà le problème à la main.

- [ ] **Step 1: Récrire le test qui fige la dette**

Dans `resources/js/stores/clients.test.js`, le test `DETTE FIGEE (non corrigee) : errors.validationErrors n'est jamais vide, meme apres un succes ulterieur` atteste du défaut. Récris-le pour décrire le comportement **corrigé** :

```js
it('vide validationErrors avant chaque appel', async () => {
  // Auparavant, clearErrors(operation) ne vidait que errors[operation] :
  // une erreur de validation survivait à l'appel qui l'avait produite et
  // pouvait réapparaître dans une modale suivante.
  const store = useClientsStore();

  axios.post.mockRejectedValueOnce(
    erreurAxios(422, { errors: { nom: ['Le nom est obligatoire.'] } })
  );
  await store.addClient({ nom: '' });
  expect(store.errors.validationErrors).toEqual({ nom: ['Le nom est obligatoire.'] });

  axios.post.mockResolvedValueOnce({ data: { client: { id: 1 }, message: 'Créé' } });
  await store.addClient({ nom: 'EBS' });

  expect(store.errors.validationErrors).toBeNull();
});
```

- [ ] **Step 2: Lancer le test et vérifier qu'il échoue**

Run: `npm run test`
Expected: ce test ÉCHOUE (`validationErrors` contient toujours l'ancienne erreur).

- [ ] **Step 3: Vider validationErrors dans la fabrique**

Dans `resources/js/stores/apiCall.js`, au début de `apiCall`, vider **les deux** sources d'erreur :

```js
  async function apiCall({ operation, request, onSuccess, onError }) {
    clearErrors(operation);
    // Les erreurs de validation vivent sous une clé à part (renseignée par le
    // traitement des 422). Sans ce nettoyage, une erreur périmée survit à l'appel
    // qui l'a produite et réapparaît dans une modale suivante.
    clearErrors('validationErrors');
    setLoading(operation, true);
    // ... le reste inchangé
```

- [ ] **Step 4: Lancer les tests**

Run: `npm run test`
Expected: PASS — 53 tests.

- [ ] **Step 5: Retirer le contournement de FactureFormModal**

Dans `resources/js/components/factures/FactureFormModal.vue`, la fonction `nettoyerErreurs()` appelle `clearErrors('add')` **et** `clearErrors('validationErrors')`. Le second est devenu inutile : `apiCall` s'en charge.

Simplifie : `nettoyerErreurs()` ne doit plus appeler que `clearErrors('add')`. Si la fonction se réduit à un seul appel, tu peux la remplacer par cet appel direct partout où elle est utilisée — à toi de juger ce qui reste le plus lisible. **Ne change pas le comportement observable.**

Attention : le composant lit l'erreur via un `computed` `messageErreur` qui regarde `errors.validationErrors?.prestations?.[0]` **puis** `errors.add`. **Ne le touche pas** : les deux sources restent nécessaires (un 422 va toujours dans `validationErrors`, le reste dans `errors.add`).

- [ ] **Step 6: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 7: Commit**

```bash
git add resources/js/stores/apiCall.js resources/js/stores/clients.test.js resources/js/components/factures/FactureFormModal.vue
git commit -m "fix: vide les erreurs de validation avant chaque appel"
```

---

### Task 3 : Supprimer le `throw` d'auth (et l'option devenue inutile)

C'est le cœur de conception de ce plan.

**Files:**
- Modify: `resources/js/stores/auth.js`
- Modify: `resources/js/stores/apiCall.js`
- Modify: `resources/js/views/Profile.vue`
- Modify: `resources/js/stores/auth.test.js`

**Interfaces:**
- Consumes: `updateUser(donnees)` (tâche 1).
- Produces: la fabrique n'a plus d'option `relancerLesErreurs`. `updateUser` retourne une valeur *falsy* en cas d'échec, au lieu de relancer.

**Le raisonnement.** `auth` relance ses erreurs (`relancerLesErreurs: true`, qui produit le `throw err` du `catch`). C'était utile quand `apiCall` ne retournait rien d'exploitable : relancer était le seul moyen pour l'appelant de savoir que l'appel avait échoué. Depuis l'extraction d'`apiCall`, **la fonction retourne la réponse** — l'appelant peut simplement tester la valeur, comme le font les quatre autres stores.

Conséquences : `auth` devient uniforme, l'option `relancerLesErreurs` perd son **seul** utilisateur (on la retire — une option sans usage est une complexité gratuite), et `Profile.vue` n'a plus d'exception à attraper.

**`login()` n'est PAS concerné** : il n'utilise pas `apiCall` (code écrit à la main, avec son propre `try/catch`). Ne le touche pas.

- [ ] **Step 1: Récrire les tests qui figent la relance**

Dans `resources/js/stores/auth.test.js`, deux tests attestent de la relance :
- `DIVERGENCE : apiCall relance l'erreur apres l'avoir traitee`
- `range un 422 dans validationErrors, SANS notifier — et relance quand meme`

Récris-les pour décrire le nouveau comportement : `updateUser` **ne relance plus** ; il retourne une valeur *falsy* en cas d'échec, et l'erreur est rangée comme dans les autres stores.

```js
it('updateUser ne relance plus : il retourne une valeur falsy en cas d\'echec', async () => {
  // auth relançait ses erreurs (throw) parce qu'apiCall ne retournait rien
  // d'exploitable. Depuis l'extraction d'apiCall, il retourne la réponse :
  // l'appelant teste la valeur, comme dans les quatre autres stores.
  axios.put.mockRejectedValue({
    response: { status: 500, data: { message: 'Erreur serveur' } },
  });
  const store = useAuthStore();

  const resultat = await store.updateUser({ name: 'X' });

  expect(resultat).toBeFalsy();
  expect(store.errors.update).toBe('Erreur serveur');
});

it('range un 422 dans validationErrors, SANS notifier', async () => {
  axios.put.mockRejectedValue({
    response: { status: 422, data: { errors: { name: ['Le nom est obligatoire.'] } } },
  });
  const store = useAuthStore();

  const resultat = await store.updateUser({ name: '' });

  expect(store.errors.validationErrors).toEqual({ name: ['Le nom est obligatoire.'] });
  expect(notify).not.toHaveBeenCalled();
  expect(resultat).toBeFalsy();
});
```

Adapte ces tests au style et aux imports du fichier existant.

- [ ] **Step 2: Lancer les tests et vérifier qu'ils échouent**

Run: `npm run test`
Expected: ces tests ÉCHOUENT — `updateUser` relance encore (la promesse est rejetée au lieu de résoudre sur une valeur *falsy*).

- [ ] **Step 3: Retirer la relance dans auth**

Dans `resources/js/stores/auth.js`, retirer l'option de la fabrique :

```js
  const { apiCall, clearErrors } = creerApiCall({ errors, loading });
```

(et supprimer le commentaire qui expliquait `relancerLesErreurs`).

- [ ] **Step 4: Retirer l'option de la fabrique**

Dans `resources/js/stores/apiCall.js`, l'option `relancerLesErreurs` n'a plus **aucun** utilisateur. La retirer :

- de la signature : `creerApiCall({ errors, loading })` ;
- de la documentation du bloc de commentaire ;
- du `catch` : supprimer `if (relancerLesErreurs) throw err;`.

Une option sans usage est une complexité gratuite : le prochain lecteur se demanderait qui s'en sert.

- [ ] **Step 5: Adapter Profile.vue**

Dans `resources/js/views/Profile.vue`, `submitProfileUpdate` fait `await updateUser(userData.value);` sans rien vérifier — ce qui produisait une *unhandled promise rejection* quand le store relançait.

Le store ne relance plus : teste le retour, comme le font les autres composants (`ClientFormModal` fait `const success = await addClient(...)`).

```js
  const submitProfileUpdate = async () => {
    const succes = await updateUser(userData.value);

    if (!succes) return;   // l'erreur est déjà affichée (toast + errors.update)
  };
```

Si le composant a besoin de faire quelque chose au succès (fermer, réinitialiser), place-le après ce garde. **Ne change pas ce qu'il affichait déjà** : le toast et le message d'erreur sont produits par le store.

- [ ] **Step 6: Lancer les tests et le build**

Run: `npm run test`
Expected: PASS — 53 tests.

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 7: Commit**

```bash
git add resources/js/stores/auth.js resources/js/stores/apiCall.js resources/js/views/Profile.vue resources/js/stores/auth.test.js
git commit -m "refactor: auth ne relance plus ses erreurs, comme les autres stores"
```

---

### Task 4 : La modale de suppression qui se ferme sur un échec

**Files:**
- Modify: `resources/js/components/factures/FactureDeleteModal.vue`

**Interfaces:**
- Consumes: `deleteInvoice(id)`, qui retourne la réponse au succès et `undefined` à l'échec (depuis l'extraction d'`apiCall`).
- Produces: rien (dernière tâche).

```js
async function confirmDelete() {
    await deleteInvoice(props.invoice.id);
    close();                                // ← fermé quoi qu'il arrive
}
```

C'est exactement la classe de bug corrigée pour la création de facture, où la modale se fermait sur un échec en effaçant la sélection de l'utilisateur.

- [ ] **Step 1: Ne fermer qu'en cas de succès**

Dans `resources/js/components/factures/FactureDeleteModal.vue` :

```js
async function confirmDelete() {
    const succes = await deleteInvoice(props.invoice.id);

    // On ne ferme QUE si la suppression a réussi : sinon l'utilisateur croirait
    // sa facture supprimée alors qu'elle est toujours là.
    if (succes) {
        close();
    }
}
```

`deleteInvoice` retourne la réponse axios (*truthy*) au succès, et `undefined` (*falsy*) à l'échec — l'erreur est déjà affichée en toast par le store.

- [ ] **Step 2: Vérifier le build**

Run: `npm run build`
Expected: build Vite réussi.

- [ ] **Step 3: Lancer les tests**

Run: `npm run test`
Expected: PASS — 53 tests (aucun test de composant dans ce projet ; on vérifie l'absence de régression sur les stores).

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/factures/FactureDeleteModal.vue
git commit -m "fix: la modale de suppression ne se ferme plus sur un echec"
```

---

## Vérification finale

- [ ] `npm run test` — 53 tests verts.
- [ ] `npm run build` — passe.
- [ ] **Seuls `auth.test.js` et `clients.test.js` ont été modifiés parmi les fichiers de test** — et uniquement les tests nommés dans les contraintes globales. Vérifier : `git diff main --name-only -- 'resources/js/stores/*.test.js'`. Si un autre fichier de test apparaît, une régression a été maquillée.
- [ ] `grep -rn "relancerLesErreurs" resources/js/` — **aucun résultat** : l'option a bien disparu.
- [ ] Contrôle manuel dans l'application (http://localhost:8080) :
      - enregistrer son profil → le nom affiché dans l'interface se met à jour **sans recharger la page** ;
      - créer une facture → fonctionne toujours ;
      - supprimer une facture → fonctionne, et la modale reste ouverte si la suppression échoue.
