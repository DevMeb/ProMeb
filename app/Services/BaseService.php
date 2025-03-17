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
        } catch (Exception $e) {
            Log::channel($logChannel)->error("$errorMessage - " . $e->getMessage());
            throw $e;
        }
    }
}
