<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE exercise_media MODIFY COLUMN type ENUM('image', 'video_url', 'video') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exercise_media MODIFY COLUMN type ENUM('image', 'video_url') NOT NULL");
    }
};
