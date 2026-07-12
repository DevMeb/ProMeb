<?php

namespace App\Services;

use App\Enums\FactureStatut;
use App\Models\Facture;
use App\Models\Prestation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FactureService extends BaseService
{
    public function getAll()
    {
        return $this->handleExceptions(
            fn() => Facture::where('user_id', Auth::id())
                            ->with('prestations.client', 'prestations.tauxHoraire')
                            ->get(), 
            "Erreur lors de la récupération des factures",
            "facture"
        );
    }

    public function create(array $data)
    {
        return $this->handleExceptions(function () use ($data) {
            return DB::transaction(function () use ($data) {
                $ids = $data['prestations'];

                $prestations = Prestation::whereIn('id', $ids)
                                    ->where('user_id', Auth::id())
                                    ->with('tauxHoraire')
                                    ->get();

                // Vérifier que toutes les prestations demandées appartiennent bien à l'utilisateur
                if ($prestations->count() !== count(array_unique($ids))) {
                    throw ValidationException::withMessages([
                        'prestations' => "Une ou plusieurs prestations sélectionnées n'existent pas ou ne vous appartiennent pas.",
                    ]);
                }

                // Une facture ne peut concerner qu'un seul client.
                // ValidationException (et non Exception) : c'est une erreur métier,
                // que l'utilisateur doit lire — pas un plantage serveur en 500.
                $clients = $prestations->groupBy('client_id');
                if ($clients->count() > 1) {
                    throw ValidationException::withMessages([
                        'prestations' => 'Toutes les prestations doivent concerner le même client. Créez une facture par client.',
                    ]);
                }

                $heuresTotal = $prestations->sum('heures');
                $montantTotal = $prestations->sum(fn($p) => $p->heures * $p->tauxHoraire->taux);

                $facture = Facture::create([
                    'heures_total'  => $heuresTotal,
                    'montant_total' => $montantTotal,
                    'user_id'       => Auth::id(),
                    'statut'        => FactureStatut::EnAttentePaiement,
                ]);

                // Le rattachement ne touche QUE les prestations encore libres.
                // Si une autre requête en a rattaché une entre-temps (deux onglets),
                // le nombre de lignes affectées ne correspondra pas : on annule tout
                // plutôt que d'écraser sa facture — ce qui la viderait silencieusement.
                //
                // Attention : sur MySQL, update() renvoie le nombre de lignes *changées*,
                // pas *appariées*. Le décompte ci-dessous n'est juste que parce que
                // facture_id passe forcément de NULL à une valeur. Rendre cette écriture
                // idempotente (réattacher à la même facture) ferait lire 0 et rejetterait
                // une facture pourtant légitime.
                $affectees = Prestation::whereIn('id', $ids)
                    ->where('user_id', Auth::id())
                    ->whereNull('facture_id')
                    ->update(['facture_id' => $facture->id]);

                if ($affectees !== count(array_unique($ids))) {
                    throw ValidationException::withMessages([
                        'prestations' => "Une ou plusieurs prestations viennent d'être rattachées à une autre facture. Rechargez la page et réessayez.",
                    ]);
                }

                return $facture->refresh()->load('prestations.client', 'prestations.tauxHoraire');
            });
        }, "Erreur lors de la création de la facture", "facture");
    }


    public function delete(Facture $facture)
    {
        return $this->handleExceptions(function () use ($facture) {
                        
            Prestation::where('facture_id', $facture->id)
            ->update(['facture_id' => null]);

            $facture->delete();

            return $facture;
        }, "Erreur lors de la suppression de la facture (ID: $facture->id)", "facture");
    }

    public function getPdf(Facture $facture) {
        return $this->handleExceptions(function () use ($facture) {
                        
            $prestations = $facture->prestations->load('client', 'tauxHoraire');
            $client = $facture->prestations->first()->client;
            $user = Auth::user();

            $champsRequis = ['iban', 'name', 'prenom', 'adresse', 'ville', 'code_postal', 'siren', 'nom_societe'];
            $infosManquantes = array_filter($champsRequis, fn($champ) => empty($user->$champ));

            if (!empty($infosManquantes)) {
                $labels = [
                    'iban' => 'IBAN',
                    'name' => 'Nom',
                    'prenom' => 'Prénom',
                    'adresse' => 'Adresse',
                    'ville' => 'Ville',
                    'code_postal' => 'Code postal',
                    'siren' => 'SIREN',
                    'nom_societe' => 'Nom de la société',
                ];

                $champsLisibles = array_map(
                    fn ($champ) => $labels[$champ] ?? $champ,
                    $infosManquantes
                );

                abort(response()->json([
                    'message' => 'Les champs suivants ne sont pas renseignés dans votre profil : ' . implode(', ', $champsLisibles),
                ], 422));
            }

            $pdf = Pdf::loadView('invoices.pdf', [
                'facture'          => $facture,
                'prestations'      => $prestations,
                'client'           => $client,
                'user'             => $user,
                'afficherHoraires' => (bool) $client->afficher_horaires,
            ]);

            return $pdf->output();
        }, "Erreur lors de la génération au format PDF de la facture (ID: $facture->id)", "facture");
    }

    public function paid(Facture $facture) {
        return $this->handleExceptions(function () use ($facture) {
                        
            $facture->update([
                'statut' => FactureStatut::Paye,
                'paye_le' => now(),
            ]);

            return $facture->refresh()->load('prestations.client', 'prestations.tauxHoraire');
        }, "Erreur lors de la suppression de la facture (ID: $facture->id)", "facture");
        
    }
}
