<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class FactureController extends Controller
{
    /**
     * Liste toutes les factures.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Récupère les factures triées par date de création décroissante, avec leurs prestations associées.
            $factures = Facture::orderBy('created_at', 'desc')
                ->with('prestations')
                ->get();

            return response()->json($factures, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la récupération des factures : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crée une facture à partir des prestations sélectionnées et renvoie le PDF.
     *
     * Payload attendu :
     * {
     *    "prestations": [1, 2, 3],
     *    "total_heures": 12.50,
     *    "total_ht": 250.00
     * }
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prestations'  => 'required|array',
            'prestations.*'=> 'integer|exists:prestations,id',
            'total_heures' => 'required|numeric|min:0',
            'total_ht'     => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Création de la facture
            $facture = Facture::create([
                'quantite_heures' => $validated['total_heures'],
                'total_ht'        => $validated['total_ht'],
                'is_paid'         => false,
            ]);

            // Rattacher les prestations à la facture
            Prestation::whereIn('id', $validated['prestations'])
                ->update(['facture_id' => $facture->id]);

            DB::commit();

            // Récupérer les prestations rattachées à cette facture
            $prestations = Prestation::where('facture_id', $facture->id)->get();

            // Générer le PDF à partir d'une vue dédiée
            $pdf = Pdf::loadView('invoices.pdf', [
                'facture' => $facture,
                'prestations' => $prestations,
                // Vous pouvez ajouter d'autres informations, par exemple le taux horaire
            ]);

            // Retourner le PDF pour téléchargement
            return $pdf->download("facture_{$facture->id}.pdf");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de la création de la facture : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprime une facture et détache les prestations associées.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $facture = Facture::findOrFail($id);

            // Optionnel : détacher les prestations de la facture en mettant facture_id à null
            Prestation::where('facture_id', $facture->id)
                ->update(['facture_id' => null]);

            $facture->delete();

            return response()->json(['message' => 'Facture supprimée avec succès.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Facture non trouvée.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression de la facture : ' . $e->getMessage()
            ], 500);
        }
    }
}
