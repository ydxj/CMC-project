<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('module_groupe_formateur')) {
            Schema::table('module_groupe_formateur', function (Blueprint $table) {
                if (!Schema::hasColumn('module_groupe_formateur', 'id_etablissement')) {
                    // Add id_etablissement as nullable first since existing rows might not have data
                    $table->integer('id_etablissement')->nullable()->after('id_formateur');

                    // Add foreign key
                    $table->foreign('id_etablissement')
                        ->references('id')
                        ->on('etablissements')
                        ->onDelete('cascade')
                        ->onUpdate('cascade');

                    // Add index
                    $table->index('id_etablissement');
                }
            });

            // Populate id_etablissement from formateur for existing records
            DB::statement('
                UPDATE module_groupe_formateur mgf
                SET id_etablissement = (
                    SELECT f.id_etablissement
                    FROM formateurs f
                    WHERE f.id = mgf.id_formateur
                    LIMIT 1
                )
                WHERE id_etablissement IS NULL
            ');

            // Now make id_etablissement NOT NULL
            Schema::table('module_groupe_formateur', function (Blueprint $table) {
                $table->integer('id_etablissement')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('module_groupe_formateur')) {
            Schema::table('module_groupe_formateur', function (Blueprint $table) {
                if (Schema::hasColumn('module_groupe_formateur', 'id_etablissement')) {
                    $table->dropForeign(['id_etablissement']);
                    $table->dropIndex('module_groupe_formateur_id_etablissement_index');
                    $table->dropColumn('id_etablissement');
                }
            });
        }
    }
};
