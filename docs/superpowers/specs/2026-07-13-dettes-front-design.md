# Solder quatre dettes du front

Date : 2026-07-13

## Problème

Quatre défauts, chacun mineur pris isolément, mais qui ensemble donnent
l'impression d'un logiciel qui ment : il affiche un succès sans l'avoir produit, ou
un état qui n'est plus vrai.

Tous les quatre sont **figés par des tests de caractérisation** (PR #29) — des tests
qui attestent du comportement réel sans le juger. Les corriger fera donc **casser
ces tests**, et c'est le signal attendu : ils décrivaient un comportement qu'on
décide maintenant de changer.

### 1. Le profil ne se met pas à jour à l'écran

`resources/js/stores/auth.js` :

```js
async function updateUser(user) {          // ← le paramètre masque la ref du store
  onSuccess: (response) => {
    user.value = response.data.user;       // ← pose .value sur l'ARGUMENT
```

`store.user` n'est **jamais** mis à jour. L'utilisateur enregistre son profil, voit
le toast « Mis à jour », et l'en-tête continue d'afficher l'ancien nom jusqu'au
rechargement de la page.

### 2. La modale de suppression de facture se ferme même en cas d'échec

`resources/js/components/factures/FactureDeleteModal.vue` :

```js
async function confirmDelete() {
    await deleteInvoice(props.invoice.id);
    close();                                // ← fermé quoi qu'il arrive
}
```

C'est exactement la classe de bug corrigée pour la création de facture, où la
modale se fermait sur un échec en effaçant la sélection de l'utilisateur.

### 3. Les erreurs de validation périmées peuvent réapparaître

`clearErrors(operation)` — appelé par `apiCall` au début de chaque appel — ne remet
à `null` que `errors[operation]`. **`errors.validationErrors` n'est jamais vidé.**

Une erreur de validation peut donc survivre à l'appel qui l'a produite et
réapparaître dans une modale suivante. `FactureFormModal` contourne déjà le
problème à la main (`clearErrors('validationErrors')`) — un contournement qui
disparaîtra avec la correction.

### 4. `Profile.vue` n'attrape pas le `throw` d'`auth`

`await updateUser(userData.value)` sans `try/catch`, alors que le store **relance**
l'erreur (`relancerLesErreurs: true`). Un échec produit une *unhandled promise
rejection*.

## Décisions

**Supprimer le `throw` d'`auth` plutôt que d'ajouter un `try/catch`.**

C'est le point de conception de cette spec. Ce `throw` était utile quand `apiCall`
ne retournait rien d'exploitable : relancer l'erreur était le seul moyen pour
l'appelant de savoir que l'appel avait échoué. Depuis l'extraction d'`apiCall`
(PR #30), **`apiCall` retourne la réponse** — l'appelant peut donc simplement tester
la valeur, comme le font déjà les quatre autres stores.

Conséquences :

- `auth` devient uniforme avec les autres stores ;
- l'option **`relancerLesErreurs` de la fabrique perd son seul utilisateur** : elle
  est retirée (YAGNI — une option sans usage est une complexité gratuite) ;
- `Profile.vue` teste le retour d'`updateUser` au lieu d'attraper une exception ;
- le défaut 4 disparaît sans `try/catch`.

Ajouter un `try/catch` aurait masqué le symptôme en laissant l'anomalie de
conception intacte.

**Vider `validationErrors` dans la fabrique, pas dans chaque composant.**
`FactureFormModal` le fait déjà à la main ; c'est un contournement, pas une
solution. `apiCall` doit vider les deux sources d'erreur avant chaque appel.

## Conception

| Fichier | Changement |
|---|---|
| `resources/js/stores/auth.js` | Renommer le paramètre d'`updateUser` (le shadowing) ; retirer `relancerLesErreurs: true` |
| `resources/js/stores/apiCall.js` | Vider `errors.validationErrors` en plus de `errors[operation]` avant chaque appel ; supprimer l'option `relancerLesErreurs`, devenue sans usage |
| `resources/js/views/Profile.vue` | Tester le retour d'`updateUser` au lieu de laisser filer une exception |
| `resources/js/components/factures/FactureDeleteModal.vue` | Ne fermer que si la suppression a réussi |
| `resources/js/components/factures/FactureFormModal.vue` | Retirer le contournement `clearErrors('validationErrors')`, devenu inutile |

`nettoyerErreurs()` dans `FactureFormModal` se réduit alors à `clearErrors('add')` —
ou disparaît si l'appel direct suffit. À trancher à l'implémentation, sans changer
le comportement observable.

## Tests

**Les tests de caractérisation qui figent ces dettes vont casser. C'est le
résultat attendu**, et il délimite exactement ce qui change :

- celui qui atteste que `store.user` n'est **pas** mis à jour par `updateUser` ;
- celui qui atteste qu'`errors.validationErrors` **survit** à un nouvel appel ;
- ceux qui attestent qu'`auth` **relance** ses erreurs.

Ils doivent être **récrits** pour décrire le nouveau comportement : `store.user`
*est* mis à jour, `validationErrors` *est* vidé, `updateUser` ne relance *plus* mais
retourne une valeur *falsy* en cas d'échec.

**Tout autre test rouge est une régression** : on reprend le code, jamais le test.

Le projet n'a pas de tests de composants. `FactureDeleteModal`, `FactureFormModal`
et `Profile.vue` sont vérifiés par `npm run build` et un contrôle manuel :
enregistrer son profil et voir l'en-tête se mettre à jour ; tenter de supprimer une
facture et vérifier qu'un échec laisse la modale ouverte.

## Hors périmètre

- L'envoi de facture par e-mail (fonctionnalité absente, sujet à part entière).
- `login()`, qui n'utilise pas `apiCall` et n'est pas concerné par la suppression du
  `throw`.
- L'UI d'import des prestations.
