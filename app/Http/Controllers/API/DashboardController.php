<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Prestation;
use App\Enums\FactureStatut;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Retourne les données du tableau de bord pour un mois donné.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        
        try {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Format de mois invalide.'], 422);
        }
        
        $userId = Auth::id();

        // 🔹 Récupération des prestations du mois
        $prestations = Prestation::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->with('tauxHoraire', 'client')
            ->get();

        // 🔹 Prestations non facturées
        $prestationsUnbilled = $prestations->whereNull('facture_id');

        // 🔹 Factures payées (ayant au moins une prestation dans la période)
        $facturesPaid = Facture::where('user_id', $userId)
            ->where('statut', FactureStatut::Paye)
            ->whereHas('prestations', function ($q) use ($start, $end) {
                $q->whereBetween('date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            // ⬇️ On charge TOUTES les prestations de la facture
            ->with('prestations.client', 'prestations.tauxHoraire')
            ->get();

        // 🔹 Factures en attente de paiement (ayant au moins une prestation dans la période)
        $facturesUnpaid = Facture::where('user_id', $userId)
            ->where('statut', FactureStatut::EnAttentePaiement)
            ->whereHas('prestations', function ($q) use ($start, $end) {
                $q->whereBetween('date', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
            })
            ->with('prestations.client', 'prestations.tauxHoraire')
            ->get();

        return response()->json([
            'month' => $month,
            'prestations' => $prestations,
            'prestations_unbilled' => $prestationsUnbilled,
            'factures_paid' => $facturesPaid,
            'factures_unpaid' => $facturesUnpaid,
        ], 200);
    }

}
