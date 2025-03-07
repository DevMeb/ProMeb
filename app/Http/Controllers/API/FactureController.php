<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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
     * Crée une facture à partir des prestations sélectionnées.
     *
     * Payload attendu :
     * {
     *    "prestations": [1, 2, 3],
     *    "total_heures": 12.50,
     *    "total_ht": 250.00
     * }
     *
     * Cette méthode se contente de créer la facture en BDD et d'associer les prestations.
     * Elle renvoie ensuite la facture créée au format JSON.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prestations'  => 'required|array',
            'prestations.*'=> 'integer|exists:prestations,id',
            'total_heures' => 'required|numeric|min:0',
            'total_ht'     => 'required|numeric|min:0',
        ], [
            'prestations.required'  => 'La sélection de prestations est obligatoire.',
            'total_heures.required' => 'Le total des heures est obligatoire.',
            'total_ht.required'     => 'Le total HT est obligatoire.',
        ]);

        DB::beginTransaction();
        try {
            $facture = Facture::create([
                'quantite_heures' => $validated['total_heures'],
                'total_ht'        => $validated['total_ht'],
                'is_paid'         => false,
            ]);

            Prestation::whereIn('id', $validated['prestations'])
                ->update(['facture_id' => $facture->id]);

            DB::commit();

            return response()->json([
                'data' => $facture,
                'message' => 'Facture créée avec succès.'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Erreur lors de la création de la facture : ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Génère et retourne le PDF d'une facture.
     *
     * @param  int  $id  L'ID de la facture
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function generatePdf($id)
    {
        try {
            $facture = Facture::findOrFail($id);
            $prestations = Prestation::where('facture_id', $facture->id)->get();

            $pdfPath = "facture_{$facture->id}.pdf";

            // Vérifier si le fichier PDF existe déjà
            if (Storage::disk('factures')->exists($pdfPath)) {
                $pdfContent = Storage::disk('factures')->get($pdfPath);
            } else {
                // Générer le PDF à partir de la vue dédiée
                $pdf = Pdf::loadView('invoices.pdf', [
                    'facture' => $facture,
                    'prestations' => $prestations,
                ]);

                $pdfContent = $pdf->output();
                // Stocker le PDF sur le disque factures (en privé)
                Storage::disk('factures')->put($pdfPath, $pdfContent);
            }

            // Retourner le PDF pour téléchargement
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, "facture_{$facture->id}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
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

            // Définir le chemin du fichier PDF
            $pdfPath = "facture_{$facture->id}.pdf";

            // Supprimer le PDF s'il existe
            if (Storage::disk('factures')->exists($pdfPath)) {
                Storage::disk('factures')->delete($pdfPath);
            }

            // Détacher les prestations associées (optionnel : remettre facture_id à null)
            Prestation::where('facture_id', $facture->id)
                ->update(['facture_id' => null]);

            // Supprimer la facture
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
