<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiator_id')
                ->constrained('users', 'id')
                ->restrictOnDelete();
            $table->unsignedBigInteger('steam_id')->unique();
            $table->string('name');
            $table->text('description');
            $table->integer('steam_rating');
            $table->text('trailer_url');
            $table->timestamp('added_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
