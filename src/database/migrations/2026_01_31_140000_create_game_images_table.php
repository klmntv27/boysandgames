<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')
                ->constrained('games', 'id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_images');
    }
};
