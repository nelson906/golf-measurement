<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drive_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['width', 'hazard', 'green']);
            $table->decimal('point_a_lat', 10, 7);
            $table->decimal('point_a_lng', 10, 7);
            $table->decimal('point_b_lat', 10, 7);
            $table->decimal('point_b_lng', 10, 7);
            $table->decimal('distance_yards', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurements');
    }
};
