<?php

use App\Models\Facture;
use App\Services\FactureService;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;

it('journalise un message de paiement (pas de suppression) quand paid echoue', function () {
    Log::spy();
    // Log::spy() ne stubbe channel() : sans ça, channel('facture') renvoie null
    // (comportement par défaut d'un spy Mockery sur une méthode non déclarée),
    // et l'appel ->error(...) plante avant même d'atteindre notre assertion.
    Log::shouldReceive('channel')->andReturnSelf();

    // Facture dont l'update lève : on isole l'erreur sans dépendre de la DB.
    $facture = Mockery::mock(Facture::class)->makePartial();
    $facture->id = 42;
    $facture->shouldReceive('update')->andThrow(new RuntimeException('échec simulé'));

    // handleExceptions logge puis relance : on avale l'exception relancée.
    try {
        app(FactureService::class)->paid($facture);
    } catch (RuntimeException $e) {
        // attendu
    }

    // Le canal 'facture' a bien reçu un message parlant de PAIEMENT, pas de suppression.
    Log::shouldHaveReceived('channel')->with('facture');
    Log::shouldHaveReceived('error')->withArgs(function (string $message) {
        return str_contains($message, 'paiement') && ! str_contains($message, 'suppression');
    });
});
