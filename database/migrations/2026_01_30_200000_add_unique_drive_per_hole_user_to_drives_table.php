<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drives', function (Blueprint $table) {
            $table->unique(['hole_id', 'user_id'], 'drives_hole_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('drives', function (Blueprint $table) {
            $table->dropUnique('drives_hole_user_unique');
        });
    }
};
