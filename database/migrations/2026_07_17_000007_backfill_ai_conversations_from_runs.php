<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_runs')
            ->whereNull('conversation_id')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($runs): void {
                    foreach ($runs as $run) {
                        $timestamp = $run->created_at
                            ?: now();

                        $conversationId = DB::table(
                            'ai_conversations'
                        )->insertGetId([
                            'school_id' => $run->school_id,
                            'user_id' => $run->user_id,
                            'title' => Str::limit(
                                trim(
                                    (string) $run->question
                                ),
                                72,
                                '…'
                            ) ?: 'Análisis de SchoolPass IA',
                            'scope_type' => $run->scope_type
                                ?: 'school',
                            'scope_id' => $run->scope_id,
                            'status' => 'active',
                            'last_message_at' => $run->completed_at
                                ?: $timestamp,
                            'created_at' => $timestamp,
                            'updated_at' => $run->updated_at
                                ?: $timestamp,
                        ]);

                        DB::table('ai_runs')
                            ->where('id', $run->id)
                            ->update([
                                'conversation_id' =>
                                    $conversationId,

                                'request_type' =>
                                    'legacy_question',

                                'prompt_version' =>
                                    'v1-mvp',
                            ]);

                        DB::table('ai_messages')->insert([
                            'conversation_id' =>
                                $conversationId,
                            'ai_run_id' => $run->id,
                            'role' => 'user',
                            'content' => (string) $run->question,
                            'structured_json' => null,
                            'status' => 'completed',
                            'sort_order' => 10,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ]);

                        if (
                            $run->status === 'success'
                            && $run->response_json
                        ) {
                            $decoded = json_decode(
                                $run->response_json,
                                true
                            );

                            $answer = is_array($decoded)
                                ? trim(
                                    (string) (
                                        $decoded['answer']
                                        ?? 'Respuesta generada por SchoolPass IA.'
                                    )
                                )
                                : 'Respuesta generada por SchoolPass IA.';

                            DB::table('ai_messages')->insert([
                                'conversation_id' =>
                                    $conversationId,
                                'ai_run_id' => $run->id,
                                'role' => 'assistant',
                                'content' => $answer,
                                'structured_json' =>
                                    $run->response_json,
                                'status' => 'completed',
                                'sort_order' => 20,
                                'created_at' => $run->completed_at
                                    ?: $timestamp,
                                'updated_at' => $run->updated_at
                                    ?: $timestamp,
                            ]);
                        } elseif (
                            $run->status === 'error'
                        ) {
                            DB::table('ai_messages')->insert([
                                'conversation_id' =>
                                    $conversationId,
                                'ai_run_id' => $run->id,
                                'role' => 'system_notice',
                                'content' =>
                                    'El análisis no pudo completarse.',
                                'structured_json' => null,
                                'status' => 'error',
                                'sort_order' => 20,
                                'created_at' => $run->completed_at
                                    ?: $timestamp,
                                'updated_at' => $run->updated_at
                                    ?: $timestamp,
                            ]);
                        }
                    }
                }
            );
    }

    public function down(): void
    {
        /*
         * No se eliminan conversaciones ni mensajes durante un rollback
         * individual para evitar pérdida de historial. Las migraciones de
         * estructura posteriores eliminan las tablas cuando corresponda.
         */
    }
};
