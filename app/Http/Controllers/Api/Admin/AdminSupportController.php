<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'open',
                    'in_progress',
                    'resolved',
                    'closed',
                ]),
            ],

            'priority' => [
                'nullable',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $query = SupportTicket::query()
            ->with([
                'merchant.user',
                'user',
            ])
            ->latest('id');

        if (! empty($validated['status'])) {
            $query->where(
                'status',
                $validated['status']
            );
        }

        if (! empty($validated['priority'])) {
            $query->where(
                'priority',
                $validated['priority']
            );
        }

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where(
                        'ticket_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'subject',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'message',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'user',
                        function ($userQuery) use ($search): void {
                            $userQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'phone',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    )
                    ->orWhereHas(
                        'merchant',
                        function ($merchantQuery) use ($search): void {
                            $merchantQuery
                                ->where(
                                    'business_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'business_email',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'business_phone',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->paginate(
                    (int) ($validated['per_page'] ?? 20)
                )
                ->withQueryString(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total' => SupportTicket::query()->count(),

                'open' => SupportTicket::query()
                    ->where('status', 'open')
                    ->count(),

                'in_progress' => SupportTicket::query()
                    ->where('status', 'in_progress')
                    ->count(),

                'resolved' => SupportTicket::query()
                    ->where('status', 'resolved')
                    ->count(),

                'closed' => SupportTicket::query()
                    ->where('status', 'closed')
                    ->count(),

                'urgent' => SupportTicket::query()
                    ->where('priority', 'urgent')
                    ->whereNotIn('status', [
                        'resolved',
                        'closed',
                    ])
                    ->count(),

                'high_priority' => SupportTicket::query()
                    ->whereIn('priority', [
                        'high',
                        'urgent',
                    ])
                    ->whereNotIn('status', [
                        'resolved',
                        'closed',
                    ])
                    ->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->with([
                'merchant.user',
                'user',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function reply(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'min:2',
                'max:5000',
            ],

            'mark_resolved' => [
                'nullable',
                'boolean',
            ],
        ], [
            'message.required' =>
                'Please enter a reply.',

            'message.min' =>
                'The reply must contain at least 2 characters.',
        ]);

        $ticket = SupportTicket::query()
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'A closed ticket cannot receive new replies.',
            ], 422);
        }

        $reply = trim($validated['message']);

        /*
         * The current database design stores the conversation inside the
         * ticket message. This preserves compatibility until a dedicated
         * support_ticket_replies table is introduced.
         */
        $conversation = trim((string) $ticket->message);

        $conversation .= sprintf(
            "\n\n--- ADMIN REPLY [%s] ---\n%s",
            now()->format('Y-m-d H:i:s'),
            $reply
        );

        $ticket->update([
            'message' => $conversation,

            'status' => ! empty($validated['mark_resolved'])
                ? 'resolved'
                : 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => ! empty($validated['mark_resolved'])
                ? 'Reply sent and ticket marked as resolved.'
                : 'Reply sent successfully.',

            'data' => $ticket->fresh([
                'merchant.user',
                'user',
            ]),
        ]);
    }

    public function resolve(int $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'A closed ticket cannot be marked as resolved.',
            ], 422);
        }

        if ($ticket->status === 'resolved') {
            return response()->json([
                'success' => false,
                'message' => 'This ticket is already resolved.',
            ], 422);
        }

        $ticket->update([
            'status' => 'resolved',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket marked as resolved.',
            'data' => $ticket->fresh([
                'merchant.user',
                'user',
            ]),
        ]);
    }

    public function reopen(int $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->findOrFail($id);

        if (! in_array(
            $ticket->status,
            [
                'resolved',
                'closed',
            ],
            true
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Only resolved or closed tickets can be reopened.',
            ], 422);
        }

        $ticket->update([
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket reopened successfully.',
            'data' => $ticket->fresh([
                'merchant.user',
                'user',
            ]),
        ]);
    }

    public function close(int $id): JsonResponse
    {
        $ticket = SupportTicket::query()
            ->findOrFail($id);

        if ($ticket->status === 'closed') {
            return response()->json([
                'success' => false,
                'message' => 'This ticket is already closed.',
            ], 422);
        }

        $ticket->update([
            'status' => 'closed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed successfully.',
            'data' => $ticket->fresh([
                'merchant.user',
                'user',
            ]),
        ]);
    }
}