<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Prestation;
use App\Models\TauxHoraire;
use App\Services\PrestationTextParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;

class ImportPrestations extends Command
{
    protected $signature = 'prestations:import
        {--client= : ID du client (sinon sélection interactive)}
        {--taux= : ID du taux horaire (sinon sélection interactive)}
        {--year= : Année des prestations (défaut : année courante)}
        {--file= : Chemin d\'un fichier texte (sinon collage interactif)}
        {--adresse=PARIS : Adresse appliquée à toutes les prestations}
        {--force : Ne pas demander de confirmation}';

    protected $description = 'Importe en masse des prestations mensuelles depuis le relevé texte d\'un freelance.';

    public function handle(PrestationTextParser $parser): int
    {
        $client = $this->resolveClient();
        if (! $client) {
            $this->error('Client introuvable.');

            return self::FAILURE;
        }

        $taux = $this->resolveTaux($client);
        if (! $taux) {
            $this->error('Taux horaire introuvable ou n\'appartenant pas au même utilisateur que le client.');

            return self::FAILURE;
        }

        $year = (int) ($this->option('year') ?: now()->year);
        $text = $this->resolveText();
        if (trim($text) === '') {
            $this->error('Aucun texte à importer.');

            return self::FAILURE;
        }

        $result = $parser->parse($text, $year);

        if (! empty($result['errors'])) {
            $this->error('Lignes non interprétables (import annulé) :');
            foreach ($result['errors'] as $line) {
                $this->line("  • {$line}");
            }

            return self::FAILURE;
        }

        $this->table(
            ['Date', 'Heures', 'Horaires'],
            array_map(fn ($r) => [$r['date'], number_format($r['heures'], 2, ',', ' '), $r['horaires']], $result['rows'])
        );
        $this->info(sprintf(
            '%d prestations · %s h · client « %s » · taux %s €/h · adresse « %s »',
            count($result['rows']),
            number_format($result['total'], 2, ',', ' '),
            $client->nom,
            $taux->taux,
            $this->option('adresse'),
        ));

        if (! $this->option('force') && ! confirm('Créer ces prestations ?', default: false)) {
            $this->comment('Annulé.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($result, $client, $taux) {
            foreach ($result['rows'] as $row) {
                Prestation::create([
                    'date'            => $row['date'],
                    'heures'          => $row['heures'],
                    'horaires'        => $row['horaires'],
                    'adresse'         => $this->option('adresse'),
                    'client_id'       => $client->id,
                    'taux_horaire_id' => $taux->id,
                    'user_id'         => $client->user_id,
                ]);
            }
        });

        $this->info(sprintf('✅ %d prestations créées.', count($result['rows'])));

        return self::SUCCESS;
    }

    private function resolveClient(): ?Client
    {
        if ($id = $this->option('client')) {
            return Client::find($id);
        }

        $clients = Client::orderBy('nom')->get();
        if ($clients->isEmpty()) {
            return null;
        }

        $id = select('Client ?', $clients->pluck('nom', 'id')->all());

        return $clients->firstWhere('id', $id);
    }

    private function resolveTaux(Client $client): ?TauxHoraire
    {
        if ($id = $this->option('taux')) {
            $taux = TauxHoraire::find($id);

            return $taux && $taux->user_id === $client->user_id ? $taux : null;
        }

        $tauxList = TauxHoraire::where('user_id', $client->user_id)->orderBy('taux')->get();
        if ($tauxList->isEmpty()) {
            return null;
        }

        $id = select('Taux horaire ?', $tauxList->mapWithKeys(fn ($t) => [$t->id => "{$t->taux} €/h"])->all());

        return $tauxList->firstWhere('id', $id);
    }

    private function resolveText(): string
    {
        if ($file = $this->option('file')) {
            return is_readable($file) ? (string) file_get_contents($file) : '';
        }

        return textarea(
            label: 'Collez le relevé du freelance',
            placeholder: "02/06: 6h15 18h30-00h45\n03/06: 9h 11h45-14h45 18h15-00h15\n...",
            rows: 15,
        );
    }
}
