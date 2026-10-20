<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPayoutJob;
use App\Models\Payout;
use App\Services\PayoutService;
use App\Services\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class AdminPayoutController extends Controller
{
    private const SUCCESS_STATUSES = [
        Payout::STATUS_COMPLETED,
        Payout::STATUS_SUCCESSFUL,
    ];

    private const FAILURE_STATUSES = [
        Payout::STATUS_REJECTED,
        Payout::STATUS_FAILED,
        Payout::STATUS_CANCELLED,
    ];

    public function __construct(
        protected PayoutService $payoutService,
        protected WalletService $walletService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],

            'status' => [
                'nullable',
                Rule::in([
                    Payout::STATUS_PENDING,
                    Payout::STATUS_PROCESSING,
                    Payout::STATUS_APPROVED,
                    Payout::STATUS_COMPLETED,
                    Payout::STATUS_SUCCESSFUL,
                    Payout::STATUS_REJECTED,
                    Payout::STATUS_FAILED,
                    Payout::STATUS_CANCELLED,
                ]),
            ],

            'processing_mode' => [
                'nullable',
                Rule::in([
                    Payout::MODE_AUTOMATIC,
                    Payout::MODE_MANUAL,
                ]),
            ],

            'merchant_type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'period' => [
                'nullable',
                Rule::in([
                    'today',
                    'yesterday',
                    'week',
                    'month',
                ]),
            ],

            'requires_attention' => [
                'nullable',
                'boolean',
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

        $query = Payout::query()
            ->with([
                'merchant.user',
                'business',
                'bankAccount',
                'withdrawalSetting',
            ])
            ->latest('id');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhere('provider_reference', 'like', "%{$search}%")
                    ->orWhereHas(
                        'bankAccount',
                        function (Builder $bankQuery) use ($search): void {
                            $bankQuery
                                ->where('bank_name', 'like', "%{$search}%")
                                ->orWhere('account_number', 'like', "%{$search}%")
                                ->orWhere('account_name', 'like', "%{$search}%");
                        }
                    )
                    ->orWhereHas(
                        'business',
                        function (Builder $businessQuery) use ($search): void {
                            $businessQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('business_name', 'like', "%{$search}%");
                        }
                    )
                    ->orWhereHas(
                        'merchant',
                        function (Builder $merchantQuery) use ($search): void {
                            $merchantQuery
                                ->where('business_name', 'like', "%{$search}%")
                                ->orWhere('business_email', 'like', "%{$search}%")
                                ->orWhere('business_phone', 'like', "%{$search}%")
                                ->orWhereHas(
                                    'user',
                                    function (Builder $userQuery) use ($search): void {
                                        $userQuery
                                            ->where('name', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%");
                                    }
                                );
                        }
                    );
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['processing_mode'])) {
            $query->where(
                'processing_mode',
                $validated['processing_mode']
            );
        }

        if (! empty($validated['merchant_type'])) {
            $query->whereHas(
                'withdrawalSetting',
                fn (Builder $settingQuery) =>
                    $settingQuery->where(
                        'merchant_type',
                        $validated['merchant_type']
                    )
            );
        }

        if (! empty($validated['requires_attention'])) {
            $query->whereNotNull('metadata->reconciliation_escalated_at');
        }

        if (! empty($validated['period'])) {
            match ($validated['period']) {
                'today' => $query->whereDate('created_at', today()),

                'yesterday' => $query->whereDate(
                    'created_at',
                    today()->subDay()
                ),

                'week' => $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]),

                'month' => $query
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month),

                default => null,
            };
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->paginate((int) ($validated['per_page'] ?? 20))
                ->withQueryString(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'data' => [
                'total' => Payout::query()->count(),

                'pending_review' => Payout::query()
                    ->where('status', Payout::STATUS_PENDING)
                    ->where('processing_mode', Payout::MODE_MANUAL)
                    ->count(),

                'processing' => Payout::query()
                    ->where('status', Payout::STATUS_PROCESSING)
                    ->count(),

                'requires_attention' => Payout::query()
                    ->where('status', Payout::STATUS_PROCESSING)
                    ->whereNotNull('metadata->reconciliation_escalated_at')
                    ->count(),

                'successful' => Payout::query()
                    ->whereIn('status', self::SUCCESS_STATUSES)
                    ->count(),

                'failed' => Payout::query()
                    ->whereIn('status', self::FAILURE_STATUSES)
                    ->count(),

                'automatic' => Payout::query()
                    ->where('processing_mode', Payout::MODE_AUTOMATIC)
                    ->count(),

                'manual' => Payout::query()
                    ->where('processing_mode', Payout::MODE_MANUAL)
                    ->count(),

                'total_amount' => (float) Payout::query()->sum('amount'),
                'total_fees' => (float) Payout::query()->sum('fee'),

                'pending_amount' => (float) Payout::query()
                    ->whereIn('status', [
                        Payout::STATUS_PENDING,
                        Payout::STATUS_PROCESSING,
                    ])
                    ->sum('amount'),

                'successful_amount' => (float) Payout::query()
                    ->whereIn('status', self::SUCCESS_STATUSES)
                    ->sum('amount'),

                'today_amount' => (float) Payout::query()
                    ->whereDate('created_at', today())
                    ->sum('amount'),

                'refunded_amount' => (float) Payout::query()
                    ->whereNotNull('refunded_at')
                    ->get()
                    ->sum(
                        fn (Payout $payout): float =>
                            $this->walletDebit($payout)
                    ),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success' => true,

            'data' => Payout::query()
                ->with([
                    'merchant.user',
                    'business',
                    'bankAccount',
                    'withdrawalSetting',
                ])
                ->findOrFail($id),
        ]);
    }

    /**
     * Approve and queue a manual-review payout for processing.
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $payout = DB::transaction(function () use ($id): Payout {
                $lockedPayout = Payout::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (in_array($lockedPayout->status, self::SUCCESS_STATUSES, true)) {
                    throw new \RuntimeException(
                        'This payout has already been approved or completed.'
                    );
                }

                if (in_array($lockedPayout->status, self::FAILURE_STATUSES, true)) {
                    throw new \RuntimeException(
                        'A rejected, failed or cancelled payout cannot be approved.'
                    );
                }

                if ($lockedPayout->refunded_at) {
                    throw new \RuntimeException(
                        'This payout has already had its reserved funds returned.'
                    );
                }

                if ($lockedPayout->status !== Payout::STATUS_PENDING) {
                    throw new \RuntimeException('Only pending payouts can be approved.');
                }

                $lockedPayout->forceFill([
                    'status' => Payout::STATUS_APPROVED,
                    'approved_at' => now(),
                    'approved_by_admin_id' => request()->user()?->id,
                    'failure_reason' => null,
                ])->save();

                app(\App\Services\AuditService::class)->log(request()->user()?->id,'payout.approved',$lockedPayout,null,['reference'=>$lockedPayout->reference]);
                return $lockedPayout->fresh();
            }, 5);

            ProcessPayoutJob::dispatch($payout->id)->afterCommit();

            return response()->json([
                'success' => true,
                'message' => 'Payout approved and queued for processing.',
                'data' => $payout->fresh([
                    'merchant.user',
                    'business',
                    'bankAccount',
                    'withdrawalSetting',
                ]),
            ], 202);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to approve this payout.',
            ], 500);
        }
    }

    public function reject(
        Request $request,
        int $id
    ): JsonResponse {
        $validated = $request->validate([
            'reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ], [
            'reason.required' =>
                'Please provide a reason for rejecting this payout.',

            'reason.min' =>
                'The rejection reason must contain at least 5 characters.',
        ]);

        try {
            $payout = DB::transaction(function () use (
                $id,
                $validated
            ): Payout {
                $lockedPayout = Payout::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if (
                    in_array(
                        $lockedPayout->status,
                        self::SUCCESS_STATUSES,
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'An approved or successful payout cannot be rejected.'
                    );
                }

                if ($lockedPayout->status === Payout::STATUS_REJECTED) {
                    throw new \RuntimeException(
                        'This payout has already been rejected.'
                    );
                }

                if (
                    in_array(
                        $lockedPayout->status,
                        [
                            Payout::STATUS_FAILED,
                            Payout::STATUS_CANCELLED,
                        ],
                        true
                    )
                ) {
                    throw new \RuntimeException(
                        'A failed or cancelled payout cannot be rejected.'
                    );
                }

                if ($lockedPayout->status !== Payout::STATUS_PENDING) {
                    throw new \RuntimeException('Only a pending payout can be reviewed. Processing payouts require reconciliation.');
                }

                if (! $lockedPayout->funds_reserved_at) {
                    throw new \RuntimeException(
                        'This payout does not have reserved funds to return.'
                    );
                }

                if ($lockedPayout->refunded_at) {
                    throw new \RuntimeException(
                        'The reserved funds for this payout have already been returned.'
                    );
                }

                $walletDebit = $this->walletDebit($lockedPayout);

                $this->walletService->releaseReservedFunds(
                    $lockedPayout->merchant_id,
                    $lockedPayout->business_id,
                    Payout::class,
                    $lockedPayout->id,
                    'Payout rejected: ' . $lockedPayout->reference
                );

                $lockedPayout->update([
                    'status' => Payout::STATUS_REJECTED,
                    'failure_reason' => trim($validated['reason']),
                    'refunded_at' => now(),
                ]);

                app(\App\Services\MerchantWebhookService::class)->sendPayout($lockedPayout);
                app(\App\Services\AuditService::class)->log(request()->user()?->id,'payout.rejected',$lockedPayout,null,['reference'=>$lockedPayout->reference,'reason'=>$validated['reason']]);
                app(\App\Services\NotificationService::class)->notifyUser($lockedPayout->merchant->user_id,'payout_rejected','Payout rejected','Your payout '.$lockedPayout->reference.' was rejected and reserved funds released.');
                return $lockedPayout->fresh([
                    'merchant.user',
                    'business',
                    'bankAccount',
                    'withdrawalSetting',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' =>
                    'Payout rejected and reserved funds returned successfully.',
                'data' => $payout,
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                    'The payout could not be rejected or refunded.',
            ], 500);
        }
    }


    public function retryProcessing(int $id): JsonResponse
    {
        try {
            $payout = DB::transaction(function () use ($id): Payout {
                $locked = Payout::query()->lockForUpdate()->findOrFail($id);

                if ($locked->isFinal()) {
                    throw new \RuntimeException('A finalised payout cannot be retried.');
                }

                if (! in_array($locked->status, [Payout::STATUS_APPROVED, Payout::STATUS_PROCESSING], true)) {
                    throw new \RuntimeException('Only approved or processing payouts can be retried.');
                }

                $metadata = $locked->metadata ?? [];
                unset(
                    $metadata['reconciliation_escalated_at'],
                    $metadata['reconciliation_escalation_reason']
                );
                $metadata['manual_retry_requested_at'] = now()->toIso8601String();
                $metadata['manual_retry_requested_by'] = request()->user()?->id;

                $locked->forceFill([
                    'status' => $locked->status,
                    'failure_reason' => null,
                    'metadata' => $metadata,
                ])->save();

                return $locked->fresh();
            }, 5);

            ProcessPayoutJob::dispatch($payout->id)->afterCommit();

            return response()->json([
                'success' => true,
                'message' => 'Payout queued for another processing attempt.',
                'data' => $payout,
            ], 202);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retry this payout.',
            ], 500);
        }
    }

    private function walletDebit(Payout $payout): float
    {
        return round(
            (float) (
                $payout->metadata['wallet_debit']
                ?? (
                    (float) $payout->amount
                    + (float) $payout->fee
                )
            ),
            2
        );
    }
}