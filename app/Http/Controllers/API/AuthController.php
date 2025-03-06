<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if(Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return response()->noContent();
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials are incorrect.'
        ]);
    }

    public function logout(Request $request)
    {
        // Déconnecte l'utilisateur
        Auth::logout();

        // Invalide la session actuelle et régénère le token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
