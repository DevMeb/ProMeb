# Tests front : caractériser les stores avant de les refactorer

Date : 2026-07-13

## Problème

Le projet n'a **aucun test front**. Toute la logique des stores Pinia — appels API,
gestion du chargement, des erreurs, des notifications, filtres, tris, totaux — n'est
vérifiée que par la lecture et par un parcours manuel dans l'application.

Ce trou a un coût mesurable. Rien que dans les dernières PR, il a laissé passer :

- `addInvoice` qui ne retournait **rien** (son `onSuccess` n'avait pas de `return`,
  et `apiCall` renvoie ce que retourne `onSuccess`) : la modale de création se
  fermait donc même en cas d'échec, effaçant la sélection de l'utilisateur ;
- un bloc d'erreur qui **ne pouvait jamais s'afficher**, parce qu'`apiCall` range
  les 422 dans `errors.validationErrors` — sans toast — alors que le composant ne
  lisait que `errors.add` ;
- `loading.paid`, une clé unique partagée par toutes les factures, qui faisait
  clignoter le bouton de toutes les lignes en attente.

Aucun de ces défauts n'était visible au build. Tous auraient été attrapés par un
test de store.

**Le déclencheur immédiat** : on veut extraire `apiCall`, dupliqué dans cinq
stores, dont un a divergé. Refactorer cinq stores sans filet, en se reposant sur
un parcours manuel, reproduirait exactement la méthode qui a produit ces bugs.

## Décisions

**Écrire les tests AVANT le refactor, sur le code actuel, sans le modifier.**
Ce sont des tests de *caractérisation* : ils décrivent ce que le code **fait**, pas
ce qu'il **devrait** faire. Ils captureront donc aussi les divergences — le test du
store des factures constatera qu'il retourne le résultat de `onSuccess`, celui des
clients qu'il retourne la réponse.

C'est contre-intuitif mais c'est le cœur de la démarche : un test qui décrit le
comportement *souhaité* ne prouve rien tant que le refactor n'est pas fait. Un test
qui décrit le comportement *existant* devient un juge dès qu'on touche au code —
ce qui reste vert n'a pas bougé, ce qui casse est exactement ce qu'on a changé.

**Critère de réussite, vérifiable :** ces tests passent sur le code d'aujourd'hui
**sans qu'une seule ligne des stores soit modifiée**. Si un store doit être touché
pour faire passer un test, c'est le test qui est faux.

**Périmètre : les stores seulement.** Pas de `@vue/test-utils`, pas de test de
composant. C'est le filet dont le refactor a besoin, et cela couvre la logique la
plus fragile. Les composants viendront séparément, si le besoin s'en fait sentir.

**Un job CI distinct** (« Tests (front) »), séparé du job PHP : quand la CI échoue,
on voit immédiatement de quel côté.

## Conception

### L'outillage

`vitest` seul, en `devDependency`. **Pas de `jsdom` ni de `happy-dom`** : on ne teste
que des stores, il n'y a aucun rendu, donc l'environnement `node` suffit. Vitest 4
supporte Vite 7 (vérifié : `vite: '^6.0.0 || ^7.0.0 || ^8.0.0'` dans ses peer
dependencies).

Un **`vitest.config.js` dédié**, et non `vite.config.js`. Ce dernier charge trois
plugins (Laravel, PWA, Tailwind) inutiles ici et sources de fragilité. La config de
test se contente de l'essentiel :

- `environment: 'node'` ;
- l'alias `@ → resources/js`. **Il doit être déclaré explicitement** : dans
  l'application, cet alias n'est pas dans `vite.config.js` — c'est
  `laravel-vite-plugin` qui l'injecte. Vitest ne le verrait donc pas.

Scripts npm : `test` (`vitest run`) et `test:watch` (`vitest`).

### Deux pièges à désamorcer

**`resources/js/utils.js` appelle `useToast()` au chargement du module**, pas dans
une fonction. Importer un store — qui importe `utils` — déclencherait donc cet appel
hors de toute application Vue. Les tests **mockent `@/utils`**, ce qu'on veut de
toute façon : c'est ainsi qu'on vérifie que `notify` est appelé au bon moment.

**`factures.js` importe le store du dashboard.** Chaque test doit donc activer une
instance Pinia (`setActivePinia(createPinia())`) avant d'instancier un store.

`axios` est mocké dans tous les tests.

### Ce qu'on caractérise

**Le comportement de `apiCall`**, pour chacun des cinq stores qui en ont un
(`clients`, `taux-horaires`, `prestations`, `factures`, `auth`) :

- **Succès** : ce que l'appel retourne, `loading[operation]` passe à `true` puis
  redescend à `false`, les erreurs précédentes sont vidées.
- **Échec 422** : `errors.validationErrors` est renseigné, **aucun toast n'est
  émis**, et l'appel retourne une valeur *falsy*.
- **Échec générique (500, réseau)** : `errors[operation]` est renseigné, un toast
  est émis, l'appel retourne une valeur *falsy*.
- **`loading` redescend à `false` même en cas d'échec** (le `finally`).

Deux comportements spécifiques, à constater tels quels :

- **`auth` relance l'erreur** (`throw err` dans son `catch`) : ses appelants peuvent
  l'attraper. Les quatre autres ne relancent pas.
- **`factures` retourne le résultat de `onSuccess`**, là où les quatre autres
  retournent la réponse. C'est la divergence qui a produit le bug d'`addInvoice`.
  Le test la fige : il **devra être modifié** lors du refactor, et c'est
  précisément ce qui documentera le changement.

**La logique métier** déjà vue se tromper :

- `unbilledPrestations` : ne retient que les prestations sans `facture_id`.
- `filteredInvoices` : filtres statut / client / mois, et **tri par `id`
  décroissant** (jamais par `created_at`, que l'API renvoie au format `d/m/Y H:i:s`,
  intriable en JavaScript).
- Le filtre par mois retient une facture si **au moins une** de ses prestations
  tombe dans le mois.
- `filteredPrestations` et `isAnyFilterActive` du store des prestations.
- `paid` écrit une clé de chargement **indexée par facture** (`paid_<id>`), et non
  une clé unique.

### La CI

Un job `Tests (front)` dans `.github/workflows/ci.yml`, indépendant du job PHP :
Node 22, `npm ci`, `npm run test`. Il bloque le merge au même titre que les tests
PHP — sinon le filet ne sert à rien.

## Tests

Le livrable **est** la suite de tests. Sa vérification :

- `npm run test` passe, **sans qu'aucun fichier de `resources/js/stores/` n'ait été
  modifié** (à vérifier par `git status` : seuls des fichiers de test, la config et
  `package.json` doivent apparaître).
- La suite PHP (90 tests) reste verte : rien de ce qui est fait ici ne touche au
  backend.
- La CI de la PR est verte, avec **deux jobs** distincts et visibles.

## Hors périmètre

- Le refactor de `apiCall` : c'est le sous-projet suivant, et il s'appuiera sur ces
  tests.
- Les tests de composants (`@vue/test-utils`).
- `dashboard.js`, qui n'a pas d'`apiCall` et gère son chargement à la main.
