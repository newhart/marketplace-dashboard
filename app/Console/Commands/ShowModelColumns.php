<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ShowModelColumns extends Command
{
    protected $signature = 'model:columns {model}';
    protected $description = 'Affiche les colonnes de la table associée à un modèle';

    public function handle()
    {
        $modelClass = $this->argument('model');

        if (!class_exists($modelClass)) {
            $this->error("Le modèle '$modelClass' n'existe pas.");
            return;
        }

        $model = new $modelClass;
        $table = $model->getTable();

        if (!Schema::hasTable($table)) {
            $this->error("La table '$table' n'existe pas dans la base de données.");
            return;
        }

        $columns = Schema::getColumnListing($table);

        $this->info("Colonnes de la table '$table' :");
        foreach ($columns as $column) {
            $this->line(" - $column");
        }
    }
}
