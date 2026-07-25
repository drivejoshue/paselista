<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'ai_run_events',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('ai_run_id')
                    ->constrained('ai_runs')
                    ->cascadeOnDelete();

                $table->string('stage_key', 80);
                $table->string('label', 180);

                $table->string('status', 30)
                    ->default('pending');

                $table->text('public_detail')
                    ->nullable();

                $table->json('metadata_json')
                    ->nullable();

                $table->unsignedSmallInteger('sort_order')
                    ->default(0);

                $table->timestamp('started_at')
                    ->nullable();

                $table->timestamp('completed_at')
                    ->nullable();

                $table->timestamps();

                $table->unique([
                    'ai_run_id',
                    'stage_key',
                ], 'ai_run_events_stage_unique');

                $table->index([
                    'ai_run_id',
                    'sort_order',
                ], 'ai_run_events_timeline_index');
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_run_events');
    }
};
