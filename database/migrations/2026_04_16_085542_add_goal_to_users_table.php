<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('goal', ['weight_loss', 'muscle_building', 'maintenance', 'strength', 'endurance', 'general_fitness'])->nullable()->after('role');
            $table->decimal('target_weight', 8, 2)->nullable()->after('goal');
            $table->enum('target_weight_unit', ['kg', 'lbs'])->default('kg')->after('target_weight');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['goal', 'target_weight', 'target_weight_unit']);
        });
    }
};
