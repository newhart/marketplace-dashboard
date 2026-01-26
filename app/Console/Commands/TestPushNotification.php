<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestPushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:test 
                            {--user-id= : ID de l\'utilisateur à qui envoyer la notification}
                            {--email= : Email de l\'utilisateur à qui envoyer la notification}
                            {--transporters : Envoyer à tous les transporteurs actifs}
                            {--title=Test de notification : Titre de la notification}
                            {--body=Ceci est un test de notification push : Corps de la notification}
                            {--data= : Données supplémentaires au format JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tester l\'envoi de notifications push via Firebase Cloud Messaging';

    protected FirebaseNotificationService $firebaseService;

    /**
     * Create a new command instance.
     */
    public function __construct(FirebaseNotificationService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Test de notification push Firebase');
        $this->newLine();

        $title = $this->option('title');
        $body = $this->option('body');
        $data = $this->parseDataOption();

        // Option: Envoyer à tous les transporteurs
        if ($this->option('transporters')) {
            return $this->sendToTransporters($title, $body, $data);
        }

        // Option: Envoyer à un utilisateur spécifique
        $userId = $this->option('user-id');
        $email = $this->option('email');

        if (!$userId && !$email) {
            $this->error('❌ Vous devez spécifier soit --user-id, soit --email, soit --transporters');
            return Command::FAILURE;
        }

        $user = $this->findUser($userId, $email);

        if (!$user) {
            return Command::FAILURE;
        }

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Trouver un utilisateur par ID ou email
     */
    protected function findUser(?string $userId, ?string $email): ?User
    {
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("❌ Utilisateur avec l'ID {$userId} introuvable");
                return null;
            }
            return $user;
        }

        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("❌ Utilisateur avec l'email {$email} introuvable");
                return null;
            }
            return $user;
        }

        return null;
    }

    /**
     * Envoyer une notification à un utilisateur
     */
    protected function sendToUser(User $user, string $title, string $body, array $data): int
    {
        $this->info("📤 Envoi de la notification à l'utilisateur:");
        $this->line("   ID: {$user->id}");
        $this->line("   Nom: {$user->name}");
        $this->line("   Email: {$user->email}");
        $this->line("   Token FCM: " . ($user->fcm_token ? '✅ Présent' : '❌ Absent'));
        $this->newLine();

        if (empty($user->fcm_token)) {
            $this->error("❌ L'utilisateur n'a pas de token FCM enregistré.");
            $this->warn("   L'utilisateur doit d'abord se connecter à l'application mobile pour enregistrer son token.");
            return Command::FAILURE;
        }

        $this->info("📝 Contenu de la notification:");
        $this->line("   Titre: {$title}");
        $this->line("   Corps: {$body}");
        if (!empty($data)) {
            $this->line("   Données: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        $this->newLine();

        $this->info("⏳ Envoi en cours...");

        try {
            $success = $this->firebaseService->sendToUser($user, $title, $body, $data);

            if ($success) {
                $this->newLine();
                $this->info("✅ Notification envoyée avec succès!");
                $this->line("   Vérifiez l'appareil de l'utilisateur pour confirmer la réception.");
                return Command::SUCCESS;
            } else {
                $this->newLine();
                $this->error("❌ Échec de l'envoi de la notification.");
                $this->warn("   Consultez les logs pour plus de détails: storage/logs/laravel.log");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Erreur lors de l'envoi:");
            $this->error("   " . $e->getMessage());
            $this->warn("   Consultez les logs pour plus de détails: storage/logs/laravel.log");
            return Command::FAILURE;
        }
    }

    /**
     * Envoyer une notification à tous les transporteurs
     */
    protected function sendToTransporters(string $title, string $body, array $data): int
    {
        $this->info("📤 Envoi de la notification à tous les transporteurs actifs...");
        $this->newLine();

        $transporters = User::where('type', User::TYPE_TRANSPORTER)
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        if ($transporters->isEmpty()) {
            $this->warn("⚠️  Aucun transporteur actif avec un token FCM trouvé.");
            return Command::SUCCESS;
        }

        $this->info("📊 Transporteurs trouvés: {$transporters->count()}");
        $this->newLine();

        $this->info("📝 Contenu de la notification:");
        $this->line("   Titre: {$title}");
        $this->line("   Corps: {$body}");
        if (!empty($data)) {
            $this->line("   Données: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        $this->newLine();

        $this->info("⏳ Envoi en cours...");

        try {
            $successCount = $this->firebaseService->sendToAllTransporters($title, $body, $data);

            $this->newLine();
            if ($successCount > 0) {
                $this->info("✅ {$successCount} notification(s) envoyée(s) avec succès sur {$transporters->count()} transporteur(s)!");
            } else {
                $this->warn("⚠️  Aucune notification n'a pu être envoyée.");
                $this->warn("   Consultez les logs pour plus de détails: storage/logs/laravel.log");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Erreur lors de l'envoi:");
            $this->error("   " . $e->getMessage());
            $this->warn("   Consultez les logs pour plus de détails: storage/logs/laravel.log");
            return Command::FAILURE;
        }
    }

    /**
     * Parser l'option --data en tableau
     */
    protected function parseDataOption(): array
    {
        $dataOption = $this->option('data');
        
        if (empty($dataOption)) {
            return [];
        }

        $decoded = json_decode($dataOption, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("⚠️  Le format JSON des données est invalide. Utilisation d'un tableau vide.");
            return [];
        }

        return $decoded ?? [];
    }
}
