<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
                            {name : Le nom de l\'utilisateur}
                            {email : L\'adresse email de l\'utilisateur}
                            {password : Le mot de passe de l\'utilisateur (généré automatiquement si non fourni)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un nouvel utilisateur';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Récupérer les arguments de la commande
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password'); // Mot de passe aléatoire si non fourni

        // Validation des données
        $validator = Validator::make(compact('name', 'email', 'password'), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            $this->error('Les données fournies ne sont pas valides :');
            foreach ($validator->errors()->all() as $error) {
                $this->line("- $error");
            }
            
            $this->fail('Veuillez corriger les erreurs ci-dessus.');
        }

        // Créer l'utilisateur
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Retourner un message de succès
        $this->info("Utilisateur créé avec succès !");
        $this->line("Nom : {$user->name}");
        $this->line("Email : {$user->email}");
        $this->line("Mot de passe : {$password}"); // Si le mot de passe est généré automatiquement

        return Command::SUCCESS;
    }
}
