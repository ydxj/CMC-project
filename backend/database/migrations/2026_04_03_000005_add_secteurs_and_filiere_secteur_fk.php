<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('secteurs')) {
            Schema::create('secteurs', function (Blueprint $table) {
                $table->id();
                $table->string('nom_secteur', 150)->unique();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (Schema::hasTable('filieres') && !Schema::hasColumn('filieres', 'id_secteur')) {
            Schema::table('filieres', function (Blueprint $table) {
                $table->unsignedBigInteger('id_secteur')->nullable()->after('id_etablissement');
                $table->index('id_secteur', 'filieres_id_secteur_index');
                $table->foreign('id_secteur', 'filieres_id_secteur_foreign')
                    ->references('id')
                    ->on('secteurs')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('filieres') && Schema::hasColumn('filieres', 'id_secteur')) {
            Schema::table('filieres', function (Blueprint $table) {
                $table->dropForeign('filieres_id_secteur_foreign');
                $table->dropIndex('filieres_id_secteur_index');
                $table->dropColumn('id_secteur');
            });
        }

        Schema::dropIfExists('secteurs');
    }
};
