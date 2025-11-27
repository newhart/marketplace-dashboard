<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin
        {--name=Super Admin : Le nom du super admin}
        {--email=admin@marketplace.test : L\'email du super admin}
        {--password=password : Le mot de passe du super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée un utilisateur super admin pour l\'application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        // Vérifier si l'utilisateur existe déjà
        if (User::where('email', $email)->exists()) {
            $this->error("Un utilisateur avec l'email {$email} existe déjà !");
            return Command::FAILURE;
        }

        // Créer le super admin
        $superAdmin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $this->info("✓ Super Admin créé avec succès !");
        $this->info("  Nom    : {$superAdmin->name}");
        $this->info("  Email  : {$superAdmin->email}");
        $this->info("  Rôle   : {$superAdmin->role}");
        $this->info("  ID     : {$superAdmin->id}");

        return Command::SUCCESS;
    }
}
