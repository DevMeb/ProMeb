<?php

namespace App\Http\Controllers\API;

use App\Enums\FactureStatut;
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
     * Liste toutes les factures avec leurs prestations associées.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        try {
            $factures = Facture::orderBy('created_at', 'desc')
                ->with('prestations')
                ->get();

            return response()->json($factures, 200);
        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la récupération des factures", $e);
        }
    }

    /**
     * Crée une facture à partir d'une liste de prestations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'prestations'  => 'required|array',
            'prestations.*'=> 'integer|exists:prestations,id',
        ], [
            'prestations.required'  => 'La sélection de prestations est obligatoire.',
            'prestations.*.exists'    => 'Une ou plusieurs prestations sélectionnées n\'existent pas.',
        ]);

        DB::beginTransaction();
        try {
            $prestations = Prestation::whereIn('id', $validated['prestations'])->get();

            $heuresTotal = $prestations->sum('nombre_heures');

            $tauxHoraire = env('TAUX_HORAIRE');
            $montantTotal = $heuresTotal * $tauxHoraire;

            $facture = Facture::create([
                'heures_total'  => $heuresTotal,
                'montant_total' => $montantTotal,
                'statut'        => FactureStatut::EnAttenteEnvoi,
                'paye_le'       => null,
                'envoye_le'     => null,
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
            return $this->errorResponse("Erreur lors de la création de la facture", $e);
        }
    }

    /**
     * Génère et retourne le PDF d'une facture.
     *
     * @param int $id L'ID de la facture
     * @return \Symfony\Component\HttpFoundation\StreamedJsonResponse|\Illuminate\Http\JsonResponse
     */
    public function generatePdf($id): \Symfony\Component\HttpFoundation\StreamedJsonResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $facture = $this->getFactureWithPrestations($id);
            $pdfPath = "facture_{$facture->id}.pdf";

            $pdfContent = $this->getOrGeneratePdf($facture, $pdfPath);

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, "facture_{$facture->id}.pdf", [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la génération du PDF", $e);
        }
    }

    /**
     * Supprime une facture et détache les prestations associées.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            $facture = Facture::findOrFail($id);
            $pdfPath = "facture_{$facture->id}.pdf";

            if (Storage::disk('factures')->exists($pdfPath)) {
                Storage::disk('factures')->delete($pdfPath);
            }

            Prestation::where('facture_id', $facture->id)
                ->update(['facture_id' => null]);

            $facture->delete();

            return response()->json(['message' => 'Facture supprimée avec succès.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Facture non trouvée.'], 404);
        } catch (\Exception $e) {
            return $this->errorResponse("Erreur lors de la suppression de la facture", $e);
        }
    }

    /**
     * Récupère une facture avec ses prestations associées.
     *
     * @param int $id
     * @return Facture
     */
    private function getFactureWithPrestations($id): Facture
    {
        return Facture::with('prestations')->findOrFail($id);
    }

    /**
     * Retourne le contenu PDF d'une facture.
     * Si le PDF existe déjà sur le disque, il est récupéré, sinon il est généré et stocké.
     *
     * @param Facture $facture
     * @param string $pdfPath
     * @return string
     */
    private function getOrGeneratePdf(Facture $facture, $pdfPath): string
    {
        if (Storage::disk('factures')->exists($pdfPath)) {
            return Storage::disk('factures')->get($pdfPath);
        }

        $prestations = Prestation::where('facture_id', $facture->id)->get();
        $pdf = Pdf::loadView('invoices.pdf', [
            'facture' => $facture,
            'prestations' => $prestations,
        ]);

        $pdfContent = $pdf->output();
        Storage::disk('factures')->put($pdfPath, $pdfContent);

        return $pdfContent;
    }

    /**
     * Génère une réponse JSON d'erreur centralisée.
     *
     * @param string $message
     * @param \Exception $e
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($message, \Exception $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => $message . ' : ' . $e->getMessage()
        ], 500);
    }
}
