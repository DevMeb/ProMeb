<?php

namespace App\Services;

use App\Enums\FactureStatut;
use App\Models\Facture;
use App\Models\Prestation;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                $prestations = Prestation::whereIn('id', $data['prestations'])
                                    ->with('tauxHoraire')
                                    ->get();

                // Vérifier si toutes les prestations appartiennent au même client
                $clients = $prestations->groupBy('client_id');
                if ($clients->count() > 1) {
                    throw new Exception("Toutes les prestations doivent appartenir au même client.");
                }

                $heuresTotal = $prestations->sum('heures');
                $montantTotal = $prestations->sum(fn($p) => $p->heures * $p->tauxHoraire->taux);

                $facture = Facture::create([
                    'heures_total'  => $heuresTotal,
                    'montant_total' => $montantTotal,
                    'user_id'       => Auth::id(),
                    'statut'        => FactureStatut::EnAttentePaiement,
                ]);

                Prestation::whereIn('id', $data['prestations'])
                    ->update(['facture_id' => $facture->id]);

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

            $champsRequis = ['name', 'prenom', 'adresse', 'ville', 'code_postal', 'siren', 'nom_societe'];
            $infosManquantes = array_filter($champsRequis, fn($champ) => empty($user->$champ));

            if (!empty($infosManquantes)) {
                throw new Exception("Veuillez compléter votre profil dans vos paramètres avant de générer une facture !");
            }

            $pdf = Pdf::loadView('invoices.pdf', [
                'facture' => $facture,
                'prestations' => $prestations,
                'client' => $client,
                'user' => $user,
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

            return $facture->refresh()->load('prestations');
        }, "Erreur lors de la suppression de la facture (ID: $facture->id)", "facture");
        
    }
}
