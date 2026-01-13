<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_river', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('river_id')->constrained('rivers')->cascadeOnDelete();
            // Only keep a single pivot attribute as requested
            $table->unsignedInteger('bridge_count')->default(0);
            $table->timestamps();
            $table->unique(['city_id', 'river_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_river');
    }
};
