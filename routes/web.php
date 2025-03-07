<?php

use App\Models\Facture;
use App\Models\Prestation;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    // Récupère la première facture en BDD
    $facture = Facture::first();
    
    // Si aucune facture n'est trouvée, affiche un message
    if (!$facture) {
        return "Aucune facture trouvée en base de données.";
    }
    
    // Récupère les prestations associées à cette facture
    $prestations = Prestation::where('facture_id', $facture->id)->get();
    
    // Transmet la facture et les prestations à la vue
    return view('invoices.pdf', compact('facture', 'prestations'));
});

Route::get('/{any}', function () {
    return view('app'); // Charge Vue.js
})->where('any', '^(?!api).*$'); // Exclure /api/*


