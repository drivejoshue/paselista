<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'ai_runs',
            function (Blueprint $table): void {
                $table->foreignId('conversation_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('ai_conversations')
                    ->nullOnDelete();

                $table->foreignId('previous_run_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('ai_runs')
                    ->nullOnDelete();

                $table->string('request_type', 40)
                    ->default('question')
                    ->after('previous_run_id');

                $table->string('prompt_version', 80)
                    ->nullable()
                    ->after('request_type');

                $table->text('redacted_question')
                    ->nullable()
                    ->after('question');

                $table->char('context_hash', 64)
                    ->nullable()
                    ->after('redacted_question');

                $table->timestamp('context_generated_at')
                    ->nullable()
                    ->after('context_hash');

                $table->index([
                    'school_id',
                    'conversation_id',
                    'created_at',
                ], 'ai_runs_conversation_index');

                $table->index([
                    'school_id',
                    'request_type',
                    'created_at',
                ], 'ai_runs_request_type_index');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'ai_runs',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'ai_runs_conversation_index'
                );

                $table->dropIndex(
                    'ai_runs_request_type_index'
                );

                $table->dropForeign([
                    'conversation_id',
                ]);

                $table->dropForeign([
                    'previous_run_id',
                ]);

                $table->dropColumn([
                    'conversation_id',
                    'previous_run_id',
                    'request_type',
                    'prompt_version',
                    'redacted_question',
                    'context_hash',
                    'context_generated_at',
                ]);
            }
        );
    }
};
