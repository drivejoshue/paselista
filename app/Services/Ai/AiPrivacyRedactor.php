<?php

namespace App\Services\Ai;

use App\Models\AiMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiPrivacyRedactor
{
    public function conversationHistory(
        int $schoolId,
        int $conversationId,
        int $currentRunId
    ): array {
        $students = DB::table('students')
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->get([
                'id',
                'student_code',
                'first_name',
                'last_name',
            ]);

        $aliases = [];
        $replacements = [];

        foreach ($students as $student) {
            $alias = sprintf(
                'ALU-%04d',
                (int) $student->id
            );

            $fullName = trim(
                $student->first_name
                .' '
                .$student->last_name
            );

            $aliases[$alias] = $fullName;

            if ($fullName !== '') {
                $replacements[$fullName] = $alias;
            }

            if (
                trim(
                    (string) $student->student_code
                ) !== ''
            ) {
                $replacements[
                    (string) $student->student_code
                ] = $alias;
            }
        }

        uksort(
            $replacements,
            fn (string $a, string $b): int =>
                mb_strlen($b)
                <=> mb_strlen($a)
        );

        $messages = AiMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('status', 'completed')
            ->whereIn('role', [
                'user',
                'assistant',
            ])
            ->where(
                function ($query) use (
                    $currentRunId
                ): void {
                    $query
                        ->whereNull('ai_run_id')
                        ->orWhere(
                            'ai_run_id',
                            '!=',
                            $currentRunId
                        );
                }
            )
            ->orderByDesc('id')
            ->limit(
                (int) config(
                    'schoolpass_ai.limits.history_messages',
                    8
                )
            )
            ->get([
                'role',
                'content',
            ])
            ->reverse()
            ->values()
            ->map(
                function (AiMessage $message) use (
                    $replacements
                ): array {
                    return [
                        'role' => $message->role,
                        'content' => $this->redact(
                            text: $message->content,
                            replacements: $replacements
                        ),
                    ];
                }
            )
            ->all();

        return [
            'messages' => $messages,
            'aliases' => $aliases,
        ];
    }

    private function redact(
        string $text,
        array $replacements
    ): string {
        $clean = trim(
            preg_replace(
                '/\s+/u',
                ' ',
                strip_tags($text)
            )
        );

        $clean = preg_replace(
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu',
            '[CORREO-OCULTO]',
            $clean
        );

        $clean = preg_replace(
            '/(?:\+?52[\s\-]?)?(?:\d[\s\-]?){10,13}/',
            '[TELÉFONO-OCULTO]',
            (string) $clean
        );

        if ($replacements !== []) {
            $clean = str_ireplace(
                array_keys($replacements),
                array_values($replacements),
                (string) $clean
            );
        }

        return mb_substr(
            (string) $clean,
            0,
            (int) config(
                'schoolpass_ai.limits.history_message_chars',
                2200
            )
        );
    }
}
