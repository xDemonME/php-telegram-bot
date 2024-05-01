<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_callback_messages', function (Blueprint $table) {
            $table->id();
            $table->string('timestamp');
            $table->bigInteger('chat_id');
            $table->bigInteger('message_id');
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('bot_chat');
            $table->unique(['timestamp', 'chat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_callback_messages');
    }
};
