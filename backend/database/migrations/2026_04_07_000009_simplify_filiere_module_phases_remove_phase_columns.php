<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('filiere_module_phases')) {
            return;
        }

        Schema::table('filiere_module_phases', function (Blueprint $table) {
            try {
                $table->dropIndex('filiere_module_phases_order_index');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            if (Schema::hasColumn('filiere_module_phases', 'phase_number')) {
                $table->dropColumn('phase_number');
            }

            if (Schema::hasColumn('filiere_module_phases', 'position_in_phase')) {
                $table->dropColumn('position_in_phase');
            }

            try {
                $table->index(['id_filiere', 'id_previous_module'], 'filiere_module_phases_prev_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('filiere_module_phases')) {
            return;
        }

        Schema::table('filiere_module_phases', function (Blueprint $table) {
            if (!Schema::hasColumn('filiere_module_phases', 'phase_number')) {
                $table->unsignedInteger('phase_number')->default(1);
            }

            if (!Schema::hasColumn('filiere_module_phases', 'position_in_phase')) {
                $table->unsignedInteger('position_in_phase')->default(1);
            }

            try {
                $table->dropIndex('filiere_module_phases_prev_index');
            } catch (\Throwable $e) {
                // Ignore if index does not exist.
            }

            try {
                $table->index(['id_filiere', 'phase_number', 'position_in_phase'], 'filiere_module_phases_order_index');
            } catch (\Throwable $e) {
                // Ignore if index already exists.
            }
        });
    }
};
