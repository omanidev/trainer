<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table) {
            $table->decimal('weight', 8, 2)->nullable()->after('completed_at');
            $table->enum('weight_unit', ['kg', 'lbs'])->default('kg')->after('weight');
            $table->json('sets_data')->nullable()->after('weight_unit'); // stores array of {reps, weight} for each set
        });
    }

    public function down(): void
    {
        Schema::table('exercise_logs', function (Blueprint $table) {
            $table->dropColumn(['weight', 'weight_unit', 'sets_data']);
        });
    }
};
