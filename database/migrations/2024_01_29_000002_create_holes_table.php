<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golf_course_id')->constrained()->onDelete('cascade');
            $table->integer('hole_number');
            $table->integer('par')->nullable();
            $table->integer('length_yards')->nullable();
            $table->timestamps();
            
            $table->unique(['golf_course_id', 'hole_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holes');
    }
};
