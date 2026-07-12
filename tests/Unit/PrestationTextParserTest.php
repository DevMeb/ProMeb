<?php

use App\Services\PrestationTextParser;

function parseText(string $text, int $year = 2026): array
{
    return (new PrestationTextParser())->parse($text, $year);
}

it('parse une journée simple (date, heures décimales, horaires)', function () {
    $result = parseText('02/06: 6h15 18h30-00h45');

    expect($result['rows'])->toHaveCount(1);
    expect($result['rows'][0])->toMatchArray([
        'date'     => '2026-06-02',
        'heures'   => 6.25,
        'horaires' => '18h30-00h45',
        'source'   => '02/06',
    ]);
    expect($result['total'])->toBe(6.25);
    expect($result['errors'])->toBeEmpty();
});

it('concatène plusieurs plages dans horaires et convertit les heures pleines', function () {
    $result = parseText('03/06: 9h 11h45-14h45 18h15-00h15');

    expect($result['rows'][0]['heures'])->toBe(9.0);
    expect($result['rows'][0]['horaires'])->toBe('11h45-14h45 18h15-00h15');
});

it('ignore les marqueurs V dans les horaires et ne les compte pas', function () {
    $result = parseText('13/06: 9h15 11h45-15h 2V 18h45-2h');

    expect($result['rows'][0]['heures'])->toBe(9.25);
    expect($result['rows'][0]['horaires'])->toBe('11h45-15h 18h45-2h');
});

it('ignore les lignes vides et la ligne TOTAL', function () {
    $result = parseText("02/06: 6h15 18h30-00h45\n\nTOTAL: 244h");

    expect($result['rows'])->toHaveCount(1);
    expect($result['errors'])->toBeEmpty();
});

it('signale les lignes non interprétables sans planter', function () {
    $result = parseText("02/06: 6h15 18h30-00h45\nligne cassée sans date");

    expect($result['rows'])->toHaveCount(1);
    expect($result['errors'])->toHaveCount(1);
});

it('parse le mois de juin complet et totalise 238 heures', function () {
    $result = parseText(juinAdjusté());

    expect($result['rows'])->toHaveCount(25);
    expect($result['errors'])->toBeEmpty();
    expect($result['total'])->toBe(238.0);
});

/** Texte de juin 2026 ajusté à 238 h (13/06→9h15, 16/06→9h, 30/06→7h ; V retirés). */
function juinAdjusté(): string
{
    return <<<TXT
    02/06: 6h15 18h30-00h45
    03/06: 9h 11h45-14h45 18h15-00h15
    04/06: 10h15 11h45-14h45 18h15-1h30
    05/06: 6h30 11h45-14h 20h-2h15
    06/06: 7h 18h45-1h45
    08/06: 10h 11h45-14h30 18h45-00h45
    09/06: 10h 11h45-14h30 18h15-1h15
    11/06: 6h30 18h45-1h15
    12/06: 11h15 11h45-15h45 18h45-2h
    13/06: 9h15 11h45-15h 18h45-2h
    14/06: 11h 11h45-15h45 18h45-1h45
    15/06: 10h 11h45-14h45 18h45-1h45
    16/06: 9h 11h45-14h45 18h15-1h
    17/06: 11h 11h45-15h45 18h45-1h45
    18/06: 12h 11h45-15h45 18h15-1h45
    19/06: 10h45 11h45-15h15 18h45-2h00
    20/06: 10h15 11h45-14h30 18h15-1h45
    21/06: 7h 18h15-1h15
    23/06: 8h 11h45-14h45 18h15-23h15
    24/06: 10h15 11h45-14h45 18h15-1h15
    26/06: 10h 12h35-14h45 18h45-1h45
    27/06: 12h 11h45-17h 18h45-2h
    28/06: 12h15 11h45-16h45 18h45-1h
    29/06: 11h30 11h45-16h45 18h45-1h
    30/06: 7h 11h45-14h45 18h45-00h00
    TXT;
}
