<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_messages',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('conversation_id')
                    ->constrained('ai_conversations')
                    ->cascadeOnDelete();

                $table->foreignId('ai_run_id')
                    ->nullable()
                    ->constrained('ai_runs')
                    ->nullOnDelete();

                $table->string('role', 30);
                $table->longText('content');

                $table->json('structured_json')
                    ->nullable();

                $table->string('status', 30)
                    ->default('completed');

                $table->unsignedInteger('sort_order')
                    ->default(0);

                $table->timestamps();

                $table->index([
                    'conversation_id',
                    'id',
                ], 'ai_messages_conversation_index');

                $table->index([
                    'ai_run_id',
                    'role',
                ], 'ai_messages_run_role_index');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
