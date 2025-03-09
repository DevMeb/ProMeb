<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Prestation;
use App\Enums\FactureStatut;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        
        $prestations = Prestation::whereBetween('date', [$start, $end])->with('facture')->get();
        
        $prestationsNonFactured = $prestations->filter(function ($prestation) {
            return is_null($prestation->facture_id);
        })->values();
        
        $factures = Facture::whereBetween('created_at', [$start, $end])
            ->with('prestations')
            ->get();

        $countPrestationsFactured = $factures->map(function ($facture) {
            return $facture->prestations->count();
        })->sum();

        $caFacture = $factures->filter(function ($facture) {
            return $facture->statut === FactureStatut::Paye;
        })->sum('montant_total');
        
        $tauxHoraire = env('TAUX_HORAIRE', 20);

        $caAttendu = Prestation::whereBetween('date', [$start, $end])
            ->sum('heures') * $tauxHoraire;
        
        // Différence entre CA facturé et CA attendu
        $difference = $caFacture - $caAttendu;

        $caDetails = [
            'ca_facture' => $caFacture,
            'ca_attendu' => $caAttendu,
            'difference' => $difference,
        ];

        $prestationsDetails = [
            'count_prestations' => count($prestations),
            'count_prestations_factured' => $countPrestationsFactured,
            'count_prestations_non_factured' => count($prestationsNonFactured),
        ];
        
        return response()->json([
            'month'                => $month,
            'factures'             => $factures,
            'prestations_non_factured' => $prestationsNonFactured,
            'caDetails'           => $caDetails,
            'prestationsDetails'   => $prestationsDetails,
        ], 200);
    }

}
