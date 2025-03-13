<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class BaseService
{
    /**
     * Gestion centralisée des exceptions.
     *
     * @param callable $callback Fonction anonyme contenant la logique métier.
     * @param string $errorMessage Message d'erreur à retourner.
     * @param string $logChannel Canal de log spécifique (ex: 'prestation', 'facture').
     * @return mixed
     */
    protected function handleExceptions(callable $callback, string $errorMessage, string $logChannel = 'stack')
    {
        try {
            return $callback();
        } catch (ModelNotFoundException $e) {
            Log::channel($logChannel)->warning("$errorMessage - Ressource non trouvée : " . $e->getMessage());
            return response()->json(['error' => 'Ressource non trouvée'], 404);
        } catch (ValidationException $ve) {
            Log::channel($logChannel)->info("$errorMessage - Validation échouée : " . json_encode($ve->errors()));
            return response()->json(['errors' => $ve->errors()], 422);
        } catch (Exception $e) {
            Log::channel($logChannel)->error("$errorMessage - " . $e->getMessage());
            return response()->json(['error' => $errorMessage], 500);
        }
    }
}
