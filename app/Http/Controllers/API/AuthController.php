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
     *
     * Exécutée par le middleware `throttle:login` AVANT la validation du
     * contrôleur : `email` peut donc arriver ici sous n'importe quelle forme
     * (tableau, null, etc.), pas seulement une chaîne. Ne jamais faire
     * `(string) $request->input('email')` sans vérifier le type d'abord —
     * un tableau déclenche un `E_WARNING: Array to string conversion` promu
     * en `ErrorException` (500), et comme l'exception part avant que le
     * `Limit` ne soit retourné, `hit()` n'est jamais appelé : la tentative
     * échappe au throttle.
     *
     * Partie IP : n'est fiable que si `bootstrap/app.php` déclare les
     * proxies de confiance (`trustProxies`). Sans ça, en prod derrière le
     * Nginx du VPS, `$request->ip()` renvoie l'IP du proxy (constante pour
     * tous les clients) et la clé dégénère de fait en `email` seul — voir
     * docs/securite/throttle-vraie-ip-client.md pour la mise à niveau.
     */
    public static function cleThrottle(Request $request): string
    {
        $email = $request->input('email');
        $email = is_string($email) ? Str::lower($email) : '';

        return $email . '|' . $request->ip();
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
            //
            // ATTENTION : ce md5(THROTTLE_LIMITER . cle) reproduit un détail d'implémentation
            // interne de Laravel, non contractuel (non documenté, non garanti stable entre
            // versions). Si une future version de Laravel change ce hachage, cette remise à
            // zéro échouera silencieusement en production (le compteur ne sera plus effacé,
            // sans erreur ni log). Seul le test « une connexion reussie remet le compteur a
            // zero » (tests/Feature/LoginThrottleTest.php) peut détecter cette régression :
            // si ce test casse après une montée de version de Laravel, c'est ici qu'il faut
            // regarder en premier.
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
