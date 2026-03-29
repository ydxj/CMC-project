<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('filieres')) {
            if (Schema::hasColumn('filieres', 'id_etablissement')) {
                DB::statement('ALTER TABLE filieres MODIFY id_etablissement INT NULL');
            }

            try {
                DB::statement('ALTER TABLE filieres DROP INDEX filieres_id_etablissement_nom_filiere_unique');
            } catch (\Throwable $e) {
                // ignore when index name differs by environment
            }

            try {
                DB::statement('ALTER TABLE filieres ADD UNIQUE INDEX filieres_nom_filiere_unique (nom_filiere)');
            } catch (\Throwable $e) {
                // ignore when unique index already exists
            }
        }

        if (Schema::hasTable('emploi_du_temps') && !Schema::hasColumn('emploi_du_temps', 'id_module')) {
            DB::statement('ALTER TABLE emploi_du_temps ADD COLUMN id_module INT NULL AFTER id_module_groupe_formateur');
            DB::statement('ALTER TABLE emploi_du_temps ADD INDEX emploi_du_temps_id_module_index (id_module)');
            DB::statement('ALTER TABLE emploi_du_temps ADD CONSTRAINT emploi_du_temps_id_module_foreign FOREIGN KEY (id_module) REFERENCES modules(id) ON DELETE SET NULL ON UPDATE CASCADE');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('emploi_du_temps') && Schema::hasColumn('emploi_du_temps', 'id_module')) {
            try {
                DB::statement('ALTER TABLE emploi_du_temps DROP FOREIGN KEY emploi_du_temps_id_module_foreign');
            } catch (\Throwable $e) {
                // ignore when foreign key name differs
            }

            try {
                DB::statement('ALTER TABLE emploi_du_temps DROP INDEX emploi_du_temps_id_module_index');
            } catch (\Throwable $e) {
                // ignore when index name differs
            }

            DB::statement('ALTER TABLE emploi_du_temps DROP COLUMN id_module');
        }

        if (Schema::hasTable('filieres')) {
            try {
                DB::statement('ALTER TABLE filieres DROP INDEX filieres_nom_filiere_unique');
            } catch (\Throwable $e) {
                // ignore when index does not exist
            }

            try {
                DB::statement('ALTER TABLE filieres ADD UNIQUE INDEX filieres_id_etablissement_nom_filiere_unique (id_etablissement, nom_filiere)');
            } catch (\Throwable $e) {
                // ignore when unique index already exists
            }

            if (Schema::hasColumn('filieres', 'id_etablissement')) {
                DB::statement('ALTER TABLE filieres MODIFY id_etablissement INT NOT NULL');
            }
        }
    }
};
