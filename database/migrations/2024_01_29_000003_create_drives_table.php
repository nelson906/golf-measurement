<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hole_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('tee_lat', 10, 7);
            $table->decimal('tee_lng', 10, 7);
            $table->decimal('total_distance_meters', 10, 2);
            $table->decimal('total_distance_yards', 10, 2);
            $table->integer('num_shots');
            $table->json('shots'); // array di {lat, lng, distance_meters, distance_yards}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drives');
    }
};
