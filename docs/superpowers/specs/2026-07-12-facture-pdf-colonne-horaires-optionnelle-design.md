# Colonne « Horaires » optionnelle sur les factures PDF

Date : 2026-07-12

## Problème

Le tableau des prestations de la facture PDF affiche six colonnes : Réf., Date,
Horaires, Qté, PU HT, Total HT. La colonne Horaires (les plages du type
`11h45-15h 18h45-2h`) est codée en dur dans `resources/views/invoices/pdf.blade.php`.

Certains clients ne veulent pas ce niveau de détail sur leurs factures. Il faut
pouvoir masquer cette colonne pour eux, sans la masquer pour les autres et sans
cesser de saisir l'information — les horaires restent utiles en interne et
continuent d'être stockés sur la prestation.

## Décisions

**Le réglage vit sur le client**, pas sur la facture ni sur le téléchargement.
Il est fixé une fois dans la fiche client et toutes ses factures suivent. Deux
alternatives ont été écartées :

- *Choix au moment de générer le PDF* — trop facile à oublier au mauvais moment,
  et deux téléchargements de la même facture pourraient différer.
- *Réglage figé sur la facture à sa création* — garantirait qu'un PDF donné ne
  change jamais, mais coûte un champ supplémentaire sur chaque facture pour un
  besoin d'archivage qui ne s'est pas manifesté.

**Un booléen dédié, pas une structure de préférences.** Aucune autre colonne
n'est candidate au masquage aujourd'hui. Une colonne JSON `colonnes_facture`
serait payée immédiatement (validation, UI, code) pour un besoin hypothétique.
Ajouter un second booléen le jour venu est une migration triviale.

**Conséquence assumée** : modifier le réglage d'un client change aussi le PDF de
ses factures déjà émises si on les retélécharge. Acceptable — la réédition d'un
ancien PDF est un cas rare, et l'alternative coûtait plus cher que le risque.

## Conception

Le booléen `afficher_horaires` est ajouté à la table `clients` avec
`default(true)`, de sorte que les clients existants conservent exactement le PDF
qu'ils ont aujourd'hui. Le réglage traverse ensuite les couches habituelles du
projet, sans nouveau pattern :

| Couche | Fichier | Changement |
|---|---|---|
| Base | nouvelle migration | `boolean('afficher_horaires')->default(true)` |
| Modèle | `app/Models/Client.php` | champ dans `$fillable`, cast `boolean` |
| Validation | `app/Http/Requests/ClientRequest.php` | règle `sometimes\|boolean` |
| API | `app/Http/Resources/ClientResource.php` | expose le champ au front |
| Rendu | `app/Services/FactureService.php` | passe `afficher_horaires` du client à la vue |
| PDF | `resources/views/invoices/pdf.blade.php` | `@if` autour du `<th>` et du `<td>` Horaires |
| UI | `resources/js/components/clients/ClientFormModal.vue` | case à cocher, cochée par défaut |

`FactureService::getPdf()` charge déjà le client pour l'en-tête du PDF : la
valeur est donc disponible sans requête supplémentaire. Elle est passée à
`Pdf::loadView()` sous le nom `$afficherHoraires`, comme une variable de vue à
part entière plutôt que lue depuis `$client` dans la Blade, pour que la vue
déclare explicitement ce dont elle dépend.

Le tableau du PDF n'a pas de largeurs de colonnes fixes : les cinq colonnes
restantes se répartissent l'espace automatiquement. Aucun ajustement CSS.

## Tests

`Pdf::output()` renvoie du binaire, difficile à inspecter. Les tests portent donc
sur le rendu de la vue Blade, en amont de dompdf :

- Rendu avec `afficher_horaires = true` → le HTML contient l'en-tête « Horaires »
  et la valeur des horaires de la prestation.
- Rendu avec `afficher_horaires = false` → ni l'un ni l'autre, et les lignes du
  tableau comptent cinq cellules au lieu de six.
- API client : le champ se persiste à la création et à la mise à jour ; un client
  créé sans le champ vaut `true`.

## Hors périmètre

- L'e-mail de facture (`resources/views/emails/factures.blade.php`) n'affiche pas
  les horaires — rien à y changer.
- L'affichage des horaires dans l'application (liste des prestations) n'est pas
  concerné : seul le PDF client l'est.
