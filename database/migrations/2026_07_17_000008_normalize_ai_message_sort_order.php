<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_conversations')
            ->select('id')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($conversations): void {
                    foreach ($conversations as $conversation) {
                        $messages = DB::table('ai_messages')
                            ->where(
                                'conversation_id',
                                $conversation->id
                            )
                            ->orderBy('created_at')
                            ->orderBy('id')
                            ->get(['id']);

                        $sortOrder = 10;

                        foreach ($messages as $message) {
                            DB::table('ai_messages')
                                ->where('id', $message->id)
                                ->update([
                                    'sort_order' => $sortOrder,
                                ]);

                            $sortOrder += 10;
                        }
                    }
                }
            );
    }

    public function down(): void
    {
        // El orden cronológico corregido se conserva.
    }
};
