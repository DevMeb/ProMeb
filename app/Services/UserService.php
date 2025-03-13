<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserService extends BaseService
{
    public function update(array $data): ?User
    {
        return $this->handleExceptions(function () use ($data) {
            $user = Auth::user();
            
            if (!$user) {
                throw new \Exception("Aucun utilisateur connecté.");
            }

            $user->update($data);
            return $user;
        }, "Erreur lors de la mise à jour de l'utilisateur (ID: " . Auth::id() . ")", "user");
    }
}
