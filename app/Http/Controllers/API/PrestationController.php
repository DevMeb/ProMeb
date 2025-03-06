<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Prestation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrestationController extends Controller
{
    /**
     * Affiche la liste de toutes les prestations.
     */
    public function index()
    {
        try {
            $prestations = Prestation::all();
            return response()->json($prestations);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des prestations : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des prestations'], 500);
        }
    }

    /**
     * Stocke une nouvelle prestation.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'date_prestation' => 'required|date',
                'nombre_heures'   => 'required|numeric|min:0',
                'adresse'         => 'required|string|max:255',
            ]);

            $prestation = Prestation::create($validated);
            return response()->json($prestation, 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Renvoie les erreurs de validation
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la prestation : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la création de la prestation'], 500);
        }
    }

    /**
     * Affiche les détails d'une prestation spécifique.
     */
    public function show($id)
    {
        try {
            $prestation = Prestation::findOrFail($id);
            return response()->json($prestation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Prestation non trouvée'], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la prestation : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue'], 500);
        }
    }

    /**
     * Met à jour une prestation existante.
     */
    public function update(Request $request, $id)
    {
        try {
            $prestation = Prestation::findOrFail($id);

            $validated = $request->validate([
                'date_prestation' => 'sometimes|required|date',
                'nombre_heures'   => 'sometimes|required|numeric|min:0',
                'adresse'         => 'sometimes|required|string|max:255',
            ]);

            $prestation->update($validated);
            return response()->json($prestation);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Prestation non trouvée'], 404);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la prestation : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la mise à jour de la prestation'], 500);
        }
    }

    /**
     * Supprime une prestation.
     */
    public function destroy($id)
    {
        try {
            $prestation = Prestation::findOrFail($id);
            $prestation->delete();

            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Prestation non trouvée'], 404);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la prestation : ' . $e->getMessage());
            return response()->json(['error' => 'Une erreur est survenue lors de la suppression de la prestation'], 500);
        }
    }
}
