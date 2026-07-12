# Sécurité des taux horaires et suppressions non destructrices

Date : 2026-07-12

## Problème

Deux défauts, tous deux en production, vérifiés en exerçant l'API réelle (pas
par lecture de code).

### 1. Faille IDOR sur les taux horaires (critique)

`TauxHorairePolicy::update` et `::delete` ne vérifient **pas la propriété** : elles
ne regardent que l'existence de prestations facturées.

```php
public function update(User $user, TauxHoraire $tauxHoraire): bool
{
    return !$tauxHoraire->prestations()->whereNotNull('facture_id')->exists();
}
```

`$user` n'est jamais comparé à `$tauxHoraire->user_id`. Constaté :

- `PUT /api/taux-horaires/{id}` par un utilisateur tiers → **200**, le taux passe
  de 60 € à 1 €.
- `DELETE /api/taux-horaires/{id}` par un utilisateur tiers → **200**, le taux est
  détruit — **et ses prestations avec lui**, via la cascade décrite ci-dessous.

Un utilisateur peut donc détruire le travail d'un autre. La liste des taux est
correctement cloisonnée (un tiers ne voit pas ceux des autres), mais les
identifiants sont séquentiels : ce n'est pas une protection.

L'audit de sécurité du 2026-07-10 a corrigé les IDOR sur les factures et les
prestations. Les taux horaires ont été oubliés.

### 2. Suppressions destructrices en cascade (perte de données)

`prestations` déclare :

```php
$table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
$table->foreignId('taux_horaire_id')->constrained('taux_horaires')->onDelete('cascade');
```

Conséquences constatées :

- Supprimer un taux horaire **jamais facturé** → 200, et **toutes ses prestations
  non facturées sont supprimées**, silencieusement. Scénario réel : importer le
  relevé du mois (25 prestations, 238 h), faire du ménage dans les taux, en
  supprimer un vieux → le mois de travail disparaît.
- Supprimer un client → `ClientPolicy::delete` ne vérifie que la propriété, donc
  la suppression passe et détruit **toutes** ses prestations, y compris
  **facturées**. Les factures restent alors sans lignes, et leur PDF échoue
  (`getPdf()` fait `$facture->prestations->first()->client` sur une collection
  vide).

## Décisions

**Refuser la suppression plutôt que détruire.** Deux alternatives ont été
écartées :

- *Demander confirmation dans l'UI* — la suppression en masse resterait possible
  d'un clic, et l'API resterait destructrice pour tout autre appelant.
- *Archiver (soft delete)* — élégant à long terme, mais c'est un changement de
  modèle bien plus large (migration, filtrage de toutes les listes de sélection,
  désarchivage) pour un besoin qui ne s'est pas manifesté.

**Corriger l'IDOR et la cascade ensemble.** C'est le même fichier de policies, et
fermer la propriété sans corriger la cascade laisserait l'utilisateur détruire
ses *propres* données par accident.

**Une migration en défense en profondeur.** Les policies protègent les routes ;
la base doit protéger le reste (commandes artisan, seeders, futurs endpoints).
Aujourd'hui elle obéit et détruit.

## Conception

### Les policies

| Fichier | Changement |
|---|---|
| `app/Policies/TauxHorairePolicy.php` | `update` et `delete` vérifient la propriété (`$user->id === $tauxHoraire->user_id`). `delete` refuse en outre si le taux a **des prestations, facturées ou non**. |
| `app/Policies/ClientPolicy.php` | `delete` refuse si le client a **des prestations**. `update` reste inchangé (modifier un client est sans danger). |
| `app/Policies/PrestationPolicy.php` | Inchangé — il refuse déjà la modification et la suppression d'une prestation facturée. |

`TauxHorairePolicy::update` continue d'autoriser la modification d'un taux dont
les prestations ne sont **pas** facturées : c'est légitime, ces prestations ne
sont pas encore figées dans une facture.

Les refus liés à un usage existant renvoient un message explicite via
`Illuminate\Auth\Access\Response::deny()`, et non un `false` muet. L'utilisateur
doit comprendre pourquoi il ne peut pas supprimer :

- Taux : « Ce taux horaire est utilisé par N prestation(s). Modifiez leur taux ou
  supprimez-les avant de le supprimer. »
- Client : « Ce client a N prestation(s). Supprimez-les avant de le supprimer. »

Le refus lié à la **propriété**, lui, reste un `false` muet : expliquer à un
intrus pourquoi il est refusé lui apprendrait que la ressource existe.

**L'ordre des vérifications compte, et il est imposé : la propriété d'abord, l'usage
ensuite.** Si la policy comptait les prestations avant de vérifier le propriétaire,
son message de refus (« ce taux est utilisé par 25 prestations ») révélerait à un
intrus le volume d'activité d'un autre utilisateur — une fuite d'information par le
message d'erreur, alors même que l'accès est refusé.

### La migration

Une migration passe les deux clés étrangères de `prestations` de
`onDelete('cascade')` à `restrictOnDelete()` :

- `client_id`
- `taux_horaire_id`

`user_id` **reste en cascade** : supprimer un compte doit bien tout emporter.

**Point de vigilance à vérifier, pas à supposer** : avec `restrict` sur
`client_id` et `taux_horaire_id`, la suppression d'un `User` (dont la cascade
`user_id` supprime prestations, clients et taux) pourrait échouer selon l'ordre
dans lequel la base traite les cascades. Il n'existe aujourd'hui aucune route de
suppression de compte, donc le risque est théorique — mais un test doit le
confirmer plutôt que le postuler. Si la suppression d'un `User` échoue, la
migration devra ordonner la suppression (supprimer les prestations avant les
clients et les taux, dans un observateur de modèle ou une transaction).

### L'UI

`TauxHoraireDeleteModal.vue` et `ClientDeleteModal.vue` doivent afficher le
message renvoyé par l'API en cas de refus, au lieu d'une erreur brute. Le message
existe déjà côté serveur (`Response::deny`), il suffit de le relayer — c'est le
pattern déjà en place pour les autres erreurs du store.

## Tests

Du vrai TDD : c'est du backend, et la suite Pest existe (68 tests verts).

**Sécurité (le test échoue aujourd'hui — c'est la preuve que la faille est réelle) :**
- Un tiers ne peut pas modifier le taux horaire d'autrui → 403, taux inchangé.
- Un tiers ne peut pas supprimer le taux horaire d'autrui → 403, taux toujours là.

**Suppressions :**
- Supprimer un taux avec des prestations **non facturées** → 403, et **aucune
  prestation supprimée** (l'assertion sur la survie des prestations est le cœur
  du test : c'est la perte de données qu'on prévient).
- Supprimer un taux avec des prestations **facturées** → 403 (déjà le cas,
  verrouillé par un test).
- Supprimer un taux **sans aucune prestation** → 200, il disparaît.
- Supprimer un client avec des prestations → 403, aucune prestation supprimée.
- Supprimer un client sans prestation → 200.

**Défense en profondeur :**
- Une suppression directe en base (hors policy) d'un taux encore utilisé doit
  lever une erreur d'intégrité — preuve que `restrictOnDelete` est bien en place.
- La suppression d'un `User` fonctionne toujours (le point de vigilance ci-dessus).

## Hors périmètre

- Le soft delete / archivage des taux et clients.
- La réaffectation en masse des prestations d'un taux vers un autre (ce serait la
  suite naturelle si le refus devenait pénible à l'usage).
- Les autres policies (`FacturePolicy`, `PrestationPolicy`), qui vérifient déjà
  correctement la propriété.
