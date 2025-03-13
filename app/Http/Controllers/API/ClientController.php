<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    private $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Liste des clients récupérée avec succès.',
            'clients' => ClientResource::collection($this->clientService->getAll()),
        ]);
    }

    public function store(ClientRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Client créé avec succès.',
            'client' => new ClientResource($this->clientService->create($request->validated())),
        ], 201);
    }

    public function update(Client $client, ClientRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Client mis à jour avec succès.',
            'client' => new ClientResource($this->clientService->update($client, $request->validated())),
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        return response()->json([
            'message' => 'Client supprimé avec succès.',
            'client' => new ClientResource($this->clientService->delete($client)),
        ]);
    }
}
