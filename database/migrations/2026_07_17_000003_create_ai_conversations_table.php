<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_conversations',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained('schools')
                    ->cascadeOnDelete();

                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('title', 180);

                $table->string('scope_type', 30)
                    ->default('school');

                $table->unsignedBigInteger('scope_id')
                    ->nullable();

                $table->string('status', 30)
                    ->default('active');

                $table->timestamp('last_message_at')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index([
                    'school_id',
                    'user_id',
                    'status',
                    'last_message_at',
                ], 'ai_conversations_sidebar_index');

                $table->index([
                    'school_id',
                    'scope_type',
                    'scope_id',
                ], 'ai_conversations_scope_index');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
