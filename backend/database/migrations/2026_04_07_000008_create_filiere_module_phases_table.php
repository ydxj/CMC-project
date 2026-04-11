<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('filiere_module_phases')) {
            return;
        }

        Schema::create('filiere_module_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_filiere');
            $table->unsignedBigInteger('id_module');
            $table->unsignedBigInteger('id_previous_module')->nullable();
            $table->timestamps();

            $table->unique(['id_filiere', 'id_module'], 'filiere_module_phases_unique_filiere_module');
            $table->index(['id_filiere', 'id_previous_module'], 'filiere_module_phases_prev_index');

            $table->foreign('id_filiere', 'filiere_module_phases_filiere_fk')
                ->references('id')
                ->on('filieres')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_module', 'filiere_module_phases_module_fk')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('id_previous_module', 'filiere_module_phases_prev_module_fk')
                ->references('id')
                ->on('modules')
                ->nullOnDelete()
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('filiere_module_phases')) {
            return;
        }

        Schema::dropIfExists('filiere_module_phases');
    }
};
