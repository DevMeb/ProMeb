# Refonte de l'onglet Factures — liste dense et prestations dépliables

Date : 2026-07-12

## Problème

`FactureListItem.vue` affiche chaque facture en carte, et déroule **toutes** ses
prestations en blocs de quatre champs. La facture de juin 2026 compte 25
prestations : à elle seule, sa carte fait plusieurs écrans de haut. Comme les
cartes s'affichent dans une grille (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-3`)
et que les cartes d'une même ligne s'alignent sur la plus haute, une seule
facture volumineuse déforme toute la liste.

L'onglet ne remplit donc plus son office : on ne peut ni comparer deux montants
d'un coup d'œil, ni retrouver rapidement une facture à marquer payée.

## Décisions

**Une liste dense en lignes, pas une grille de cartes.** L'usage réel est de
comparer des chiffres (montants, heures, statuts) et d'agir sur une facture, pas
de parcourir du contenu hétérogène. Des colonnes alignées servent cet usage mieux
que des cartes. Deux alternatives ont été écartées :

- *Grille de cartes compactes avec accordéon* — corrige la hauteur, mais laisse
  les montants dispersés dans l'espace, donc incomparables d'un regard.
- *Cartes pleine largeur groupées par mois* — agréable, mais montre trois ou
  quatre factures par écran pour peu d'information.

**Les prestations se déplient dans la ligne**, en tableau reprenant les colonnes
du PDF (Date, Horaires, Heures, Taux, Total) avec une ligne de total. Vérifier
une facture à l'écran et la relire en PDF devient le même geste.

**Plusieurs lignes peuvent rester dépliées simultanément.** Un accordéon exclusif
qui referme la précédente gêne dès qu'on compare deux factures.

**Filtres complets** : statut, client, mois. Le filtre par mois porte sur la
**date des prestations**, pas sur la date de création de la facture — une facture
de prestations de juin est créée début juillet, et c'est « la facture de juin »
dans le langage courant. Conséquence assumée : une facture à cheval sur deux mois
apparaît dans les deux.

**Le filtrage se fait côté client**, sans appel API, comme le fait déjà le store
des prestations. Le volume de données ne justifie pas de filtrer côté serveur.

**Les horaires restent affichés dans cette vue**, même pour un client dont
`afficher_horaires` vaut `false` : ce réglage ne concerne que le PDF envoyé au
client, pas la vue interne.

## Conception

### Composants

| Fichier | Responsabilité |
|---|---|
| `resources/js/components/factures/FacturesList.vue` (modifié) | Orchestre : filtres, états (chargement / erreur / vide / vide après filtrage), liste des lignes |
| `resources/js/components/factures/FactureFilters.vue` (créé) | Statut, client, mois ; bouton de réinitialisation |
| `resources/js/components/factures/FactureListItem.vue` (récrit) | Une ligne de facture, dépliable ; porte son état d'ouverture |
| `resources/js/components/factures/FacturePrestationsTable.vue` (créé) | Le tableau des prestations affiché quand la ligne est dépliée |
| `resources/js/stores/factures.js` (modifié) | Ajoute `activeFilters`, `updateFilters`, `isAnyFilterActive`, `filteredInvoices` |

`FacturePrestationsTable` est extrait plutôt que laissé dans `FactureListItem` :
la ligne repliée et le tableau déplié sont deux préoccupations distinctes, et
`FactureListItem` reste lisible d'un seul tenant.

`FactureFilters` suit le pattern déjà en place dans
`resources/js/components/prestations/PrestationsFilters.vue` : les filtres actifs
vivent dans le store, un `watch` profond appelle `updateFilters`, et un booléen
`isAnyFilterActive` conditionne l'affichage du bouton de réinitialisation.

### Le store

Le store des factures gagne le même mécanisme que celui des prestations : un
`activeFilters` (`{ statut, client_id, month_year }`, chaînes vides par défaut) et
un `filteredInvoices` calculé.

**Le tri se fait sur `invoice.id` décroissant**, pas sur `created_at`.
`FactureResource` renvoie `created_at` au format `d/m/Y H:i:s`, une chaîne que
JavaScript ne sait ni trier ni parser nativement ; les identifiants sont
croissants dans le temps et donnent le même ordre sans parsing fragile. Ne pas
« corriger » ce point en triant sur `created_at`.

Le filtre par mois exploite le fait que **`PrestationResource` renvoie `date` au
format `Y-m-d`** : la facture est retenue si au moins une de ses prestations a une
`date` commençant par `month_year` (format `YYYY-MM`, celui que produit
`<input type="month">`). Simple comparaison de préfixe, aucun parsing de date.

Le client d'une facture se lit sur `invoice.prestations[0].client` — c'est déjà ce
que fait le composant actuel. Cet accès est protégé (`?.`) : une facture sans
prestation ferait planter le rendu de toute la liste, et le code actuel n'a
aucune garde.

### La ligne

Repliée, elle affiche : chevron, numéro, client, heures, montant, statut, et les
actions (PDF, supprimer, marquer payé) selon le statut — les mêmes règles
d'affichage qu'aujourd'hui. Dépliée, le tableau des prestations apparaît
en dessous, avec une transition de 200 ms.

Le déclencheur de dépliement est un `<button>` portant `aria-expanded` et
`aria-controls`, pour rester utilisable au clavier. Les boutons d'action sont
dans la ligne mais ne déclenchent pas le dépliement (arrêt de la propagation).

### Le responsive

Les lignes sont construites en flexbox, pas en `<table>` : sous le point de
rupture `sm`, chaque ligne se réorganise en carte (les colonnes deviennent des
paires libellé/valeur) sans jamais provoquer de défilement horizontal. Le tableau
des prestations déplié, lui, défile horizontalement dans son propre conteneur sur
petit écran.

### Les états

Quatre états distincts, tous couverts :

- **Chargement** : des lignes fantômes (skeleton), pas un spinner — la page ne
  saute pas quand les données arrivent.
- **Erreur** : le message d'erreur du store, comme aujourd'hui.
- **Aucune facture** : message d'accueil et bouton « Ajouter une facture ».
- **Aucun résultat après filtrage** : message *distinct* du précédent, avec un
  bouton « Réinitialiser les filtres ». Confondre les deux fait croire à
  l'utilisateur qu'il a perdu ses données.

## Tests

Le projet n'a pas de tests front. La vérification repose sur :

- `npm run build` — attrape les erreurs de compilation Vue.
- La suite Feature (`php artisan test --testsuite=Feature`), qui doit rester
  verte : aucun changement backend n'est prévu.
- Un passage manuel dans l'application : déplier/replier, appliquer chaque
  filtre, vérifier les quatre états, et contrôler le rendu mobile.

Installer Vitest pour tester la logique de filtrage (qui est du calcul pur)
serait défendable, mais dépasse le périmètre : ce serait introduire une chaîne de
test front entière pour cette seule fonctionnalité. À considérer séparément.

## Hors périmètre

- Le backend : aucune modification d'API, de modèle ou de migration.
- Les modals (PDF, suppression, mail, création) : inchangés.
- Le dashboard et l'onglet Prestations.
