<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prestation;
use App\Http\Requests\PrestationRequest;
use App\Http\Resources\PrestationResource;
use App\Services\PrestationService;
use Illuminate\Http\JsonResponse;

class PrestationController extends Controller
{
    private $prestationService;

    public function __construct(PrestationService $prestationService)
    {
        $this->prestationService = $prestationService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Liste des prestations récupérée avec succès.',
            'prestations' => PrestationResource::collection($this->prestationService->getAll())
        ]);
    }

    public function store(PrestationRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Prestation créée avec succès.',
            'prestation' => new PrestationResource($this->prestationService->create($request->validated()))
        ], 201);
    }

    public function update(Prestation $prestation, PrestationRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Prestation mise à jour avec succès.',
            'prestation' => new PrestationResource($this->prestationService->update($prestation, $request->validated()))
        ]);
    }

    public function destroy(Prestation $prestation): JsonResponse
    {
        return response()->json([
            'message' => 'Prestation supprimée avec succès.',
            'prestation' => new PrestationResource($this->prestationService->delete($prestation)),
        ]);
    }
}
