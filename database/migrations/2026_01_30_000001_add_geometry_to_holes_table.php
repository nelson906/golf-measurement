<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holes', function (Blueprint $table) {
            $table->json('tee_points')->nullable();
            $table->json('green_point')->nullable();
            $table->json('centerline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('holes', function (Blueprint $table) {
            $table->dropColumn(['tee_points', 'green_point', 'centerline']);
        });
    }
};
