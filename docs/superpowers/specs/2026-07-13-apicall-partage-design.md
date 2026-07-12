# Extraire `apiCall` : une seule source de vérité pour les appels API

Date : 2026-07-13

## Problème

`apiCall` — la fonction qui enveloppe chaque appel API d'un store (chargement,
erreurs, notifications) — est **copiée dans cinq stores**, avec ses compagnes
`clearErrors` et `setLoading` : environ 28 lignes dupliquées cinq fois.

Trois de ces copies (`clients`, `taux-horaires`, `prestations`) sont **identiques
caractère pour caractère**. Une a **divergé** : `factures`.

```js
// Le comportement de référence (4 stores)
if (onSuccess) onSuccess(response);
return response;

// factures
return onSuccess ? onSuccess(response) : response;
```

**Cette divergence a produit un bug réel.** `addInvoice` retournait ce que
retournait *son* `onSuccess` — c'est-à-dire `undefined`, faute de `return`. La
modale de création de facture ne pouvait donc pas distinguer un succès d'un échec :
elle se fermait dans les deux cas, effaçant la sélection de l'utilisateur (jusqu'à
25 prestations à recocher). Le correctif d'alors — ajouter un `return true`
explicite dans `onSuccess` — était un pansement sur le symptôme.

**La duplication est la cause.** Quatre copies d'une même fonction, dont une a
dérivé sans que rien ne le signale. Sans extraction, la prochaine divergence est
une question de temps.

## Décisions

**Extraire une fabrique partagée**, plutôt que d'aligner seulement le mouton noir.
Aligner `factures` corrigerait ce symptôme-ci et laisserait la cause intacte.

**Le comportement de référence est celui des quatre stores conformes.** C'est
`factures` qui s'aligne, pas l'inverse.

**Le filet existe déjà.** 52 tests de caractérisation (PR #29) décrivent le
comportement actuel de chaque store, divergences comprises. Ils sont le juge de ce
refactor : ce qui reste vert n'a pas bougé ; ce qui casse est exactement ce qu'on a
changé.

## Conception

### La fabrique

`resources/js/stores/apiCall.js` exporte `creerApiCall({ errors, loading, relancerLesErreurs })`,
qui retourne `{ apiCall, clearErrors, setLoading }`. Chaque store lui passe ses
propres refs `errors` et `loading`.

Elle porte le comportement des quatre stores conformes, et absorbe les **deux
besoins réels** qui existent aujourd'hui :

- **`onError`** (paramètre optionnel de `apiCall`) : `getInvoicePdf` en a besoin
  pour extraire un message d'erreur d'une réponse en `blob`. Les autres stores ne
  le passent pas — rien ne change pour eux.
- **`relancerLesErreurs`** (option de la fabrique) : seul `auth` l'active, pour
  conserver le `throw err` de son `catch`.

### Le piège que les tests ont désamorcé

Le `throw` d'`auth` **ne concerne pas `login`**. Contrairement à ce que supposait la
conception initiale, `login()` n'utilise **pas** `apiCall` : c'est du code écrit à
la main, qui ne relance rien, code son message d'erreur en dur et notifie via un
toast importé directement. **Seul `updateUser()` passe par `apiCall`** dans ce
store — et c'est lui, et lui seul, que le `throw` sert.

Supprimer l'option en croyant `login` concerné aurait cassé silencieusement le seul
appelant qui en dépend. Les tests de caractérisation ont corrigé cette erreur avant
qu'elle ne soit commise.

### Les changements de comportement — tous dans `factures.js`

Ce sont les seuls, et ils sont assumés :

1. **`apiCall` retourne la réponse.** Conséquences : `addInvoice` n'a plus besoin du
   `return true` ajouté en pansement (il est retiré), et `fetchInvoices` cesse de
   retourner `undefined`.
2. **`getInvoicePdf` construit l'URL de son blob après l'appel**, et non plus dans
   `onSuccess`.
3. **`onError` est attendu (`await`).** Aujourd'hui il ne l'est pas : son message
   d'erreur est publié par une promesse flottante, après qu'`apiCall` a déjà résolu.
   Le test actuel survit à trois microtâches et casse à cinq — il passe, mais il est
   ancré sur une course.

`FactureFormModal` fait `if (succes) close()`. La réponse axios étant *truthy*, ce
code continue de fonctionner sans modification.

### Ce qu'on ne corrige surtout pas

Deux dettes réelles ont été **figées** par les tests de caractérisation :

- `auth.updateUser()` ne met **jamais** à jour `store.user` : le paramètre `user`
  masque la ref du store, et le `.value` est posé sur l'argument.
- `errors.validationErrors` n'est **jamais vidé** : `clearErrors(operation)` ne
  remet à `null` que `errors[operation]`.

**Leurs tests doivent rester verts.** Les corriger ici mélangerait deux
changements : un refactor (aucun changement de comportement) et des corrections de
bugs. Si le refactor les fait casser, c'est qu'un comportement a changé sans qu'on
l'ait décidé — et le filet aura fait son travail.

`dashboard.js` reste à l'écart : il n'a pas d'`apiCall` et gère son chargement à la
main. L'y intégrer serait une refonte sans rapport.

## Tests

**Le filet existe : 52 tests (PR #29).** Aucun nouveau test n'est à écrire pour le
refactor lui-même — c'est tout l'intérêt de les avoir écrits avant.

**Les seuls tests autorisés à casser** sont ceux qui décrivent les divergences de
`factures` (`resources/js/stores/factures.test.js`) :

- « DIVERGENCE : apiCall retourne le resultat de onSuccess, pas la reponse » → devra
  être récrit pour constater que la réponse est retournée.
- Les tests dépendant du `return true` d'`addInvoice`.

Tout autre test rouge est une **régression** : le corriger en modifiant le test
serait une faute — c'est le code qu'il faut reprendre.

Vérification finale : `npm run test` vert, `php artisan test` vert (90), et le
décompte des lignes supprimées (~110) confirme que la duplication a bien disparu.

## Hors périmètre

- Corriger le shadowing de `user` dans `updateUser`.
- Vider `errors.validationErrors` dans `clearErrors`.
- `dashboard.js`.
- `auth.login()`, qui n'utilise pas `apiCall`.
