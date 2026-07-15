<?php

it('n\'autorise pas le joker comme origine CORS', function () {
    // Une origine unique et explicite, jamais '*' : coupler '*' et
    // supports_credentials laisse n'importe quel site lire les réponses authentifiées.
    expect(config('cors.allowed_origins'))->not->toContain('*');
});

it('reflete uniquement l\'origine configuree, pas une origine etrangere', function () {
    config()->set('cors.allowed_origins', ['https://promeb.example']);

    $reponse = $this->call('OPTIONS', '/api/auth/login', [], [], [], [
        'HTTP_ORIGIN'                         => 'https://evil.com',
        'HTTP_ACCESS_CONTROL_REQUEST_METHOD'  => 'POST',
    ]);

    // L'origine étrangère ne doit PAS être renvoyée dans l'en-tête.
    expect($reponse->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://evil.com');
});
