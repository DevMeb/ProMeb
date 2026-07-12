# Empêcher qu'une prestation déjà facturée soit refacturée

Date : 2026-07-12

## Problème

Créer une facture avec une prestation **déjà rattachée à une autre facture** est
accepté sans broncher, et **vide silencieusement la facture d'origine**.

Reproduit contre l'API :

```
Facture 1 créée                      : 500,00 €, 1 ligne
2ᵉ facturation de la MÊME prestation : HTTP 201  ← accepté
Facture 1 après                      : montant affiché 500,00 € — lignes rattachées : 0
PDF de la facture 1                  : HTTP 500  ← erreur serveur
```

La facture d'origine devient une **coquille qui ment** : elle conserve son
`montant_total`, figé à la création, mais n'a plus aucune ligne. Son PDF échoue —
`FactureService::getPdf()` fait `$facture->prestations->first()->client` sur une
collection vide.

### La cause racine, à deux niveaux

1. `app/Http/Requests/FactureRequest.php` valide `'prestations.*' =>
   'integer|exists:prestations,id'`. La règle n'exclut pas les prestations déjà
   facturées, et ne vérifie même pas qu'elles appartiennent à l'utilisateur.
2. `app/Services/FactureService.php`, dans `create()`, rattache les prestations
   ainsi :

   ```php
   Prestation::whereIn('id', $ids)
       ->where('user_id', Auth::id())
       ->update(['facture_id' => $facture->id]);
   ```

   Cet `update` **écrase** le `facture_id` existant sans jamais vérifier qu'il
   était nul.

### Pourquoi ça n'a pas encore frappé

Le front ne propose que les prestations non facturées (`unbilledPrestations` dans
le store des prestations). Le bug n'est donc atteignable qu'en appelant l'API
directement — **ou en ouvrant deux onglets** et en créant deux factures avec la
même prestation. Ce second scénario est réaliste : ce n'est pas une faille
théorique.

Aucune facture n'est actuellement dans cet état en base (vérifié : 0 facture sans
ligne sur 5).

## Décisions

**Refuser, à trois niveaux.** Une prestation ne peut appartenir qu'à une seule
facture : c'est une règle métier, pas une préférence. La question n'est pas *quoi*
faire, mais *où* le garantir.

**Le troisième niveau est le seul qui ferme réellement la porte.** Valider puis
agir laisse une fenêtre : entre la vérification de la Form Request et l'`update`
du service, une autre requête peut rattacher la prestation. Seul un `update`
conditionnel — qui ne touche que les lignes encore libres et vérifie combien il en
a réellement affectées — est à l'abri de cette course. Les deux premiers niveaux
existent pour le message d'erreur et la lisibilité, pas pour la sûreté.

## Conception

### 1. La validation

`FactureRequest` exige que chaque prestation appartienne à l'utilisateur **et** ne
soit pas déjà facturée :

```php
'prestations.*' => [
    'integer',
    Rule::exists('prestations', 'id')->where(function ($query) {
        $query->where('user_id', $this->user()->id)
              ->whereNull('facture_id');
    }),
],
```

C'est le pattern déjà en place dans `PrestationRequest` pour `client_id` et
`taux_horaire_id`. Le message d'erreur associé doit dire pourquoi : une prestation
inconnue et une prestation déjà facturée produisent aujourd'hui la même erreur
muette.

Effet de bord bénéfique : la règle scope enfin les prestations sur l'utilisateur,
ce que l'ancienne (`exists:prestations,id`) ne faisait pas.

### 2. Le service

`FactureService::create()` vérifie déjà que toutes les prestations appartiennent à
l'utilisateur et relèvent du même client. Il vérifiera aussi qu'aucune n'est déjà
facturée, et lèvera une `ValidationException` explicite sinon — cohérent avec les
deux garde-fous voisins.

### 3. Le rattachement (le cœur)

```php
$affectees = Prestation::whereIn('id', $ids)
    ->where('user_id', Auth::id())
    ->whereNull('facture_id')          // ne touche QUE les prestations libres
    ->update(['facture_id' => $facture->id]);

if ($affectees !== count(array_unique($ids))) {
    throw ValidationException::withMessages([
        'prestations' => "Une ou plusieurs prestations viennent d'être facturées. Rechargez la page.",
    ]);
}
```

Le `whereNull` garantit qu'aucun rattachement existant n'est jamais écrasé. Le
contrôle du nombre de lignes affectées détecte la course : si une autre requête a
rattaché la prestation entre-temps, `$affectees` sera inférieur au nombre demandé,
l'exception est levée, et la transaction de `create()` (déjà en place) annule la
facture qui venait d'être créée.

## Tests

**Le bug lui-même :**
- Facturer une prestation déjà facturée → **422**, la facture d'origine conserve
  ses lignes et son montant, et **aucune nouvelle facture n'est créée**.
- Le PDF de la facture d'origine répond toujours 200 après la tentative.

**La validation :**
- Facturer la prestation d'un autre utilisateur → 422 (l'ancienne règle
  l'autorisait à passer la validation).
- Le message d'erreur distingue « prestation inconnue » de « prestation déjà
  facturée ».

**Le cas nominal :**
- Facturer des prestations libres fonctionne toujours : la facture est créée, les
  prestations rattachées, les totaux justes.

**La course :** un test qui simule le rattachement concurrent — rattacher la
prestation à une autre facture *après* la validation mais *avant* l'`update` — et
vérifie que la seconde facture est annulée (aucune facture orpheline en base).
C'est le test qui prouve que le garde-fou du niveau 3 sert à quelque chose : sans
lui, seuls les niveaux 1 et 2 passeraient.

## Hors périmètre

- Le front : il ne propose déjà que les prestations non facturées. Le message
  d'erreur 422 remonte automatiquement en toast via le store.
- La réparation de données : aucune facture n'est corrompue en base.
- Le reste de `FactureService` (`delete`, `getPdf`, `paid`).
