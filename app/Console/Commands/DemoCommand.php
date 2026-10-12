<?php

namespace App\Console\Commands;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DemoCommand extends Command
{
    protected $signature = 'relais:demo {--email= : Adresse du compte (sinon variable DEMO_EMAIL, sinon question)}';

    protected $description = 'Crée un compte de démonstration avec des données d\'exemple fictives';

    public function handle(): int
    {
        $email = $this->option('email') ?: env('DEMO_EMAIL') ?: $this->ask('Adresse e-mail du compte de démonstration');
        $password = env('DEMO_PASSWORD') ?: $this->secret('Mot de passe (12 caractères minimum)');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:12']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            $this->line('Renseignez les identifiants à la question ou par DEMO_EMAIL et DEMO_PASSWORD.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($email, $password) {
            $organization = Organization::create(['name' => 'Atelier Exemple']);

            $user = new User(['name' => 'Compte démo', 'email' => $email, 'password' => Hash::make($password)]);
            $user->forceFill([
                'organization_id' => $organization->id,
                'role' => OrganizationRole::Owner,
                'email_verified_at' => now(),
            ])->save();

            (new DemoSeeder)->seedFor($organization);
        });

        $this->info('Compte de démonstration créé avec des données d\'exemple fictives.');

        return self::SUCCESS;
    }
}
