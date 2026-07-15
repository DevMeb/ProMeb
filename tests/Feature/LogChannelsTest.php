<?php

it('les canaux metier ne sont pas figes sur le driver single', function () {
    // Avant : driver 'single' en dur → fichiers invisibles dans docker logs.
    foreach (['prestation', 'facture', 'client'] as $canal) {
        expect(config("logging.channels.$canal.driver"))->not->toBe('single');
    }
});

it('les canaux metier suivent le canal par defaut de l\'environnement', function () {
    // En prod (LOG_CHANNEL=stderr), la config est relue a chaud (pas de config:cache,
    // cf. deploy.sh qui appelle config:clear avant config:cache). On simule ce cas en
    // changeant la variable d'env reellement lue par config/logging.php, puis en
    // rechargeant le fichier — un simple config()->set('logging.default', ...) ne
    // suffirait pas : le tableau 'channels' des canaux metier est deja resolu par
    // explode(',', env('LOG_CHANNEL', ...)) au moment du chargement du fichier.
    $original = getenv('LOG_CHANNEL');
    putenv('LOG_CHANNEL=stderr');
    $_ENV['LOG_CHANNEL'] = 'stderr';
    $_SERVER['LOG_CHANNEL'] = 'stderr';

    try {
        $channels = (require config_path('logging.php'))['channels'];

        foreach (['prestation', 'facture', 'client'] as $canal) {
            // Le canal métier délègue au canal par défaut : sa cible effective est stderr.
            expect($channels[$canal]['channels'])->toContain('stderr');
        }
    } finally {
        if ($original === false) {
            putenv('LOG_CHANNEL');
            unset($_ENV['LOG_CHANNEL'], $_SERVER['LOG_CHANNEL']);
        } else {
            putenv("LOG_CHANNEL=$original");
            $_ENV['LOG_CHANNEL'] = $original;
            $_SERVER['LOG_CHANNEL'] = $original;
        }
    }
});
