<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TauxHoraireRequest;
use App\Http\Resources\TauxHoraireResource;
use App\Models\TauxHoraire;
use App\Services\TauxHoraireService;
use Illuminate\Http\JsonResponse;

class TauxHoraireController extends Controller
{
    private $tauxHoraireService;

    public function __construct(TauxHoraireService $tauxHoraireService)
    {
        $this->tauxHoraireService = $tauxHoraireService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Liste des taux horaires récupérée avec succès.',
            'taux_horaires' => TauxHoraireResource::collection($this->tauxHoraireService->getAll())
        ]);
    }

    public function store(TauxHoraireRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Taux horaire créé avec succès.',
            'taux_horaire' => new TauxHoraireResource($this->tauxHoraireService->create($request->validated()))
        ], 201);
    }

    public function update(TauxHoraire $tauxHoraire, TauxHoraireRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Taux horaire mis à jour avec succès.',
            'taux_horaire' => new TauxHoraireResource($this->tauxHoraireService->update($tauxHoraire, $request->validated()))
        ]);
    }

    public function destroy(TauxHoraire $tauxHoraire): JsonResponse
    {
        return response()->json([
            'message' => 'Taux horaire supprimée avec succès.',
            'taux_horaire' => new TauxHoraireResource($this->tauxHoraireService->delete($tauxHoraire)),
        ]);
    }
}
