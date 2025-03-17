<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\FactureRequest;
use App\Http\Resources\FactureResource;
use App\Models\Facture;
use App\Services\FactureService;
use Illuminate\Http\JsonResponse;

class FactureController extends Controller
{
    
    public function __construct(
        private FactureService $factureService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Liste des factures récupérée avec succès.',
            'factures' => FactureResource::collection($this->factureService->getAll())
        ]);
    }

    public function store(FactureRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Facture créée avec succès.',
            'facture' => new FactureResource($this->factureService->create($request->validated()))
        ], 201);
    }

    public function destroy(Facture $facture): JsonResponse
    {
        return response()->json([
            'message' => 'Facture supprimée avec succès.',
            'facture' => new FactureResource($this->factureService->delete($facture)),
        ]);
    }

    public function pdf(Facture $facture)
    {
        $pdfContent = $this->factureService->getPdf($facture);

        return response()->streamDownload(function () use ($pdfContent) {
            echo $pdfContent;
        }, "facture_{$facture->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    function paid(Facture $facture) 
    {
        return response()->json([
            'message' => 'La facture a été payée avec succès',
            'facture' => new FactureResource($this->factureService->paid($facture)),
        ]);
    }

}
