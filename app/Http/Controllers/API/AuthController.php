<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Nom du limiteur nommé défini dans AppServiceProvider::boot()
     * et référencé par le middleware `throttle:login` (routes/api.php).
     */
    public const THROTTLE_LIMITER = 'login';

    /**
     * Clé de limitation du login : couple email + IP.
     * Source unique de vérité, utilisée par le limiteur (AppServiceProvider)
     * ET par la remise à zéro ci-dessous. Les deux DOIVENT rester identiques.
     */
    public static function cleThrottle(Request $request): string
    {
        return Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if(Auth::attempt($credentials)) {
            // Le middleware `throttle:login` hache la clé du limiteur nommé avec
            // md5(nom_du_limiteur . cle) avant de la stocker en cache (voir
            // Illuminate\Routing\Middleware\ThrottleRequests::handleRequestUsingNamedLimiter).
            // RateLimiter::clear() ne fait, lui, aucun hachage : pour cibler la même
            // entrée de cache, il faut reproduire exactement cette transformation ici.
            RateLimiter::clear(
                md5(self::THROTTLE_LIMITER . self::cleThrottle($request))
            );

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

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
