<?php

use Illuminate\Support\Facades\DB;

// La CI fait tourner la suite sur MySQL exprès (voir .github/workflows/ci.yml
// et docs/tests.md) : SQLite résout les cascades et les contraintes
// d'intégrité différemment, et a déjà laissé passer deux bugs de perte de
// données (cf. PR #22). Le step `db:show` du workflow ne suffit pas à
// garantir ça : il tourne dans un processus séparé de PHPUnit, alors que
// c'est phpunit.xml (à l'intérieur du processus PHPUnit) qui décide de la
// connexion réellement utilisée par les tests. Si quelqu'un ajoute
// `force="true"` à `<env name="DB_CONNECTION" value="sqlite"/>`, `db:show`
// resterait vert (il voit MySQL) alors que PHPUnit tournerait, lui, sur
// SQLite : la CI serait verte et aveugle.
//
// Ce test vérifie donc le moteur *depuis l'intérieur du processus PHPUnit*,
// la seule preuve qui vaille.
//
// Condition de déclenchement : la variable `CI`, que GitHub Actions définit
// toujours à `true` pour tout job — jamais touchée par phpunit.xml. On ne
// peut pas se fier à `DB_CONNECTION` pour décider si on est censé tester
// MySQL : c'est précisément la variable que le scénario attaqué (force="true")
// détourne, donc l'utiliser comme condition de déclenchement la rendrait
// aveugle à l'attaque qu'elle est censée détecter. `CI` reste vraie même si
// PHPUnit est piégé par un `force="true"` sur DB_CONNECTION.
it('tourne bien sur MySQL en CI, jamais un fallback SQLite silencieux', function () {
    if (getenv('CI') !== 'true') {
        $this->markTestSkipped('Hors CI : le développement local tourne sciemment sur SQLite (voir docs/tests.md).');
    }

    expect(DB::connection()->getDriverName())->toBe('mysql');
});
