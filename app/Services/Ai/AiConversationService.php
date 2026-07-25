<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiConversationService
{
    public function resolve(
        User $user,
        ?int $conversationId,
        string $scopeType,
        ?int $scopeId,
        string $question
    ): AiConversation {
        if ($conversationId) {
            $conversation = AiConversation::query()
                ->where('id', $conversationId)
                ->where('school_id', $user->school_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->firstOrFail();

            if (
                $conversation->scope_type !== $scopeType
                || (int) $conversation->scope_id
                    !== (int) $scopeId
            ) {
                $conversation->forceFill([
                    'scope_type' => $scopeType,
                    'scope_id' => $scopeId,
                ])->save();
            }

            return $conversation;
        }

        return AiConversation::query()->create([
            'school_id' => (int) $user->school_id,
            'user_id' => (int) $user->id,
            'title' => $this->titleFromQuestion($question),
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'status' => 'active',
            'last_message_at' => now(),
        ]);
    }

    public function addMessage(
        AiConversation $conversation,
        string $role,
        string $content,
        ?int $aiRunId = null,
        ?array $structured = null,
        string $status = 'completed',
        ?int $requestedSortOrder = null
    ): AiMessage {
        return DB::transaction(
            function () use (
                $conversation,
                $role,
                $content,
                $aiRunId,
                $structured,
                $status,
                $requestedSortOrder
            ): AiMessage {
                AiConversation::query()
                    ->where('id', $conversation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentMaximum = (int) AiMessage::query()
                    ->where(
                        'conversation_id',
                        $conversation->id
                    )
                    ->max('sort_order');

                $nextSortOrder = $currentMaximum + 10;

                $sortOrder = $requestedSortOrder
                    && $requestedSortOrder > $currentMaximum
                        ? $requestedSortOrder
                        : $nextSortOrder;

                $message = AiMessage::query()->create([
                    'conversation_id' => $conversation->id,
                    'ai_run_id' => $aiRunId,
                    'role' => $role,
                    'content' => $content,
                    'structured_json' => $structured,
                    'status' => $status,
                    'sort_order' => $sortOrder,
                ]);

                $this->touch($conversation);

                return $message;
            },
            3
        );
    }

    public function touch(
        AiConversation $conversation
    ): void {
        $conversation->forceFill([
            'last_message_at' => now(),
        ])->save();
    }

    public function titleFromQuestion(
        string $question
    ): string {
        $clean = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($question)
            )
        );

        if ($clean === '') {
            return 'Nueva conversación';
        }

        return Str::limit(
            $clean,
            (int) config(
                'schoolpass_ai.limits.conversation_title_chars',
                72
            ),
            '…'
        );
    }
}
