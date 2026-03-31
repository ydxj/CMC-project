<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('groupes') && !Schema::hasColumn('groupes', 'id_etablissement')) {
            Schema::table('groupes', function (Blueprint $table) {
                $table->integer('id_etablissement')->nullable()->after('id_filiere');
                $table->index('id_etablissement', 'groupes_id_etablissement_index');
            });
        }

        // Best effort backfill from filiere -> pole -> etablissement.
        if (Schema::hasTable('groupes') && Schema::hasColumn('groupes', 'id_etablissement')) {
            DB::statement("
                UPDATE groupes g
                INNER JOIN filieres f ON g.id_filiere = f.id
                LEFT JOIN poles p ON f.id_pole = p.id
                SET g.id_etablissement = p.id_etablissement
                WHERE g.id_etablissement IS NULL
            ");

            try {
                DB::statement('ALTER TABLE groupes ADD CONSTRAINT groupes_id_etablissement_foreign FOREIGN KEY (id_etablissement) REFERENCES etablissements(id) ON DELETE SET NULL ON UPDATE CASCADE');
            } catch (\Throwable $e) {
                // ignore if FK already exists or differs by environment
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('groupes') && Schema::hasColumn('groupes', 'id_etablissement')) {
            Schema::table('groupes', function (Blueprint $table) {
                try {
                    $table->dropForeign('groupes_id_etablissement_foreign');
                } catch (\Throwable $e) {
                    // ignore when foreign key name differs
                }

                try {
                    $table->dropIndex('groupes_id_etablissement_index');
                } catch (\Throwable $e) {
                    // ignore when index missing
                }

                $table->dropColumn('id_etablissement');
            });
        }
    }
};
