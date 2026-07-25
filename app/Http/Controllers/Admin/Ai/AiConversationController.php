<?php

namespace App\Http\Controllers\Admin\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\Ai\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiConversationController extends Controller
{
    public function __construct(
        private readonly AiConversationService $conversationService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        $status = $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'archived',
                ]),
            ],
        ])['status'] ?? 'active';

        $conversations = AiConversation::query()
            ->where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->where('status', $status)
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->limit(
                (int) config(
                    'schoolpass_ai.limits.recent_conversations',
                    50
                )
            )
            ->get([
                'id',
                'title',
                'scope_type',
                'scope_id',
                'status',
                'last_message_at',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'ok' => true,
            'conversations' => $conversations,
        ]);
    }

    public function store(
        Request $request
    ): JsonResponse|RedirectResponse {
        $user = $request->user();

        $validated = $request->validate([
            'title' => [
                'nullable',
                'string',
                'max:180',
            ],

            'scope_type' => [
                'nullable',
                Rule::in([
                    'school',
                    'group',
                    'student',
                ]),
            ],

            'scope_id' => [
                'nullable',
                'integer',
            ],
        ]);

        $conversation = AiConversation::query()->create([
            'school_id' => (int) $user->school_id,
            'user_id' => (int) $user->id,
            'title' => trim(
                (string) (
                    $validated['title']
                    ?? 'Nueva conversación'
                )
            ) ?: 'Nueva conversación',
            'scope_type' => $validated['scope_type']
                ?? 'school',
            'scope_id' => ! empty(
                $validated['scope_id']
            )
                ? (int) $validated['scope_id']
                : null,
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'conversation' => $conversation,
            ], 201);
        }

        return redirect()->route(
            'admin.ai.index',
            [
                'conversation' =>
                    $conversation->id,
            ]
        );
    }

    public function show(
        Request $request,
        int $conversation
    ): JsonResponse {
        $row = $this->conversation(
            $request,
            $conversation
        );

        $row->load([
            'messages' => function ($query): void {
                $query
                    ->orderBy('sort_order')
                    ->orderBy('id');
            },
        ]);

        return response()->json([
            'ok' => true,
            'conversation' => $row,
        ]);
    }

    public function rename(
        Request $request,
        int $conversation
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:180',
            ],
        ]);

        $row = $this->conversation(
            $request,
            $conversation
        );

        $row->forceFill([
            'title' => trim(
                $validated['title']
            ),
        ])->save();

        return $this->success(
            $request,
            $row,
            'Conversación renombrada.'
        );
    }

    public function archive(
        Request $request,
        int $conversation
    ): JsonResponse|RedirectResponse {
        $row = $this->conversation(
            $request,
            $conversation
        );

        $row->forceFill([
            'status' => 'archived',
        ])->save();

        return $this->success(
            $request,
            $row,
            'Conversación archivada.'
        );
    }

    public function destroy(
        Request $request,
        int $conversation
    ): JsonResponse|RedirectResponse {
        $row = $this->conversation(
            $request,
            $conversation
        );

        $row->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' =>
                    'Conversación eliminada.',
            ]);
        }

        return redirect()
            ->route('admin.ai.index')
            ->with(
                'success',
                'Conversación eliminada.'
            );
    }

    private function conversation(
        Request $request,
        int $conversation
    ): AiConversation {
        return AiConversation::query()
            ->where('id', $conversation)
            ->where(
                'school_id',
                $request->user()->school_id
            )
            ->where(
                'user_id',
                $request->user()->id
            )
            ->firstOrFail();
    }

    private function success(
        Request $request,
        AiConversation $conversation,
        string $message
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'conversation' => $conversation,
            ]);
        }

        return redirect()
            ->route('admin.ai.index')
            ->with('success', $message);
    }
}
