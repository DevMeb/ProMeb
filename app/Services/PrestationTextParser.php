<?php

namespace App\Services;

class PrestationTextParser
{
    /**
     * Analyse le relevé mensuel d'un freelance.
     *
     * Format d'une ligne : `13/06: 9h15 11h45-15h 2V 18h45-2h`
     *  - 1ʳᵉ valeur = total d'heures facturées du jour (`9h15` → 9.25),
     *  - le reste = plages horaires (les marqueurs `V` sont ignorés),
     *  - les lignes vides et la ligne « TOTAL » sont ignorées.
     *
     * @return array{rows: array<int, array{date:string, heures:float, horaires:string, source:string}>, total: float, errors: array<int, string>}
     */
    public function parse(string $text, int $year): array
    {
        $rows = [];
        $errors = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^total\b/i', $line)) {
                continue;
            }

            if (! preg_match('/^(\d{1,2})\/(\d{1,2})\s*:\s*(.+)$/', $line, $m)) {
                $errors[] = $line;
                continue;
            }

            [$dd, $mm, $rest] = [(int) $m[1], (int) $m[2], trim($m[3])];
            $tokens = preg_split('/\s+/', $rest);

            $heures = $this->parseHours(array_shift($tokens));
            if ($heures === null) {
                $errors[] = $line;
                continue;
            }

            // On retire les marqueurs "V" (ex. 2V, 1V) des plages horaires.
            $shifts = array_values(array_filter($tokens, fn ($t) => ! preg_match('/^\d*v$/i', $t)));

            $rows[] = [
                'date'     => sprintf('%04d-%02d-%02d', $year, $mm, $dd),
                'heures'   => $heures,
                'horaires' => implode(' ', $shifts),
                'source'   => sprintf('%02d/%02d', $dd, $mm),
            ];
        }

        return [
            'rows'   => $rows,
            'total'  => round(array_sum(array_column($rows, 'heures')), 2),
            'errors' => $errors,
        ];
    }

    /** Convertit « 6h15 » → 6.25, « 9h » → 9.0. Renvoie null si le format est invalide. */
    private function parseHours(string $token): ?float
    {
        if (! preg_match('/^(\d+)h(\d{1,2})?$/', $token, $m)) {
            return null;
        }

        $minutes = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;

        return round((int) $m[1] + $minutes / 60, 2);
    }
}
