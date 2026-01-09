<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')
                ->constrained('games', 'id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->integer('currency');
            $table->float('initial_price');
            $table->float('final_price');
            $table->integer('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
