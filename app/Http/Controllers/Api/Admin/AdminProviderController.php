<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\ProviderFundingCharge;
use App\Models\ProviderIncident;
use App\Models\ProviderOperationAttempt;
use App\Models\Transaction;
use App\Services\FundingChargeService;
use App\Services\ProviderManager;
use App\Services\ProviderOperationsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AdminProviderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'type' => [
                'nullable',
                Rule::in([
                    Provider::TYPE_VIRTUAL_ACCOUNT,
                    Provider::TYPE_PAYOUT,
                ]),
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'health_status' => [
                'nullable',
                Rule::in([
                    Provider::HEALTH_HEALTHY,
                    Provider::HEALTH_DEGRADED,
                    Provider::HEALTH_UNHEALTHY,
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

        $query = Provider::query()
            ->with('fundingCharge')
            ->withCount([
                'virtualAccounts',
                'transactions',
            ])
            ->orderBy('priority')
            ->orderBy('name');

        if (! empty($validated['search'])) {
            $search = trim($validated['search']);

            $query->where(function (
                Builder $builder
            ) use ($search): void {
                $builder
                    ->where(
                        'name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'slug',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'type',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        if (! empty($validated['type'])) {
            $query->where(
                'type',
                $validated['type']
            );
        }

        if (! empty($validated['status'])) {
            $query->where(
                'is_active',
                $validated['status'] === 'active'
            );
        }

        if (! empty($validated['health_status'])) {
            $query->where(
                'health_status',
                $validated['health_status']
            );
        }

        $paginator = $query->paginate(
            (int) ($validated['per_page'] ?? 12)
        );

        $paginator
            ->getCollection()
            ->transform(
                fn (Provider $provider): array =>
                    $this->serializeProvider(
                        $provider,
                        false
                    )
            );

        return response()->json([
            'success' => true,
            'data' => $paginator,
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'data' => [
                'total' => Provider::query()->count(),

                'active' => Provider::query()
                    ->where('is_active', true)
                    ->count(),

                'inactive' => Provider::query()
                    ->where('is_active', false)
                    ->count(),

                'healthy' => Provider::query()
                    ->where(
                        'health_status',
                        Provider::HEALTH_HEALTHY
                    )
                    ->count(),

                'degraded' => Provider::query()
                    ->where(
                        'health_status',
                        Provider::HEALTH_DEGRADED
                    )
                    ->count(),

                'unhealthy' => Provider::query()
                    ->where(
                        'health_status',
                        Provider::HEALTH_UNHEALTHY
                    )
                    ->count(),

                'funding_charges_enabled' =>
                    ProviderFundingCharge::query()
                        ->where('is_enabled', true)
                        ->count(),
            ],
        ]);
    }

    public function show(
        Provider $provider
    ): JsonResponse {
        $provider->load([
            'fundingCharge',
        ]);

        $provider->loadCount([
            'virtualAccounts',
            'transactions',
        ]);

        return response()->json([
            'success' => true,

            'data' => $this->serializeProvider(
                $provider,
                true
            ),
        ]);
    }

    public function store(
        Request $request
    ): JsonResponse {
        $validated = $this->validateProvider(
            $request
        );

        $provider = DB::transaction(
            function () use ($validated): Provider {
                $provider = Provider::create([
                    'name' => trim(
                        $validated['name']
                    ),

                    'slug' => Str::lower(
                        trim($validated['slug'])
                    ),

                    'type' => $validated['type'],

                    'is_active' =>
                        (bool) $validated['is_active'],

                    'priority' =>
                        (int) $validated['priority'],

                    'config' =>
                        $this->buildConfig(
                            $validated
                        ),

                    'metadata' =>
                        $this->buildMetadata(
                            $validated
                        ),

                    'health_status' =>
                        Provider::HEALTH_HEALTHY,

                    'last_health_check' => null,
                ]);

                $this->saveFundingCharge(
                    $provider,
                    $validated
                );

                return $provider;
            }
        );

        $provider->load('fundingCharge');

        $provider->loadCount([
            'virtualAccounts',
            'transactions',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider created successfully.',

            'data' => $this->serializeProvider(
                $provider,
                true
            ),
        ], 201);
    }

    public function update(
        Request $request,
        Provider $provider
    ): JsonResponse {
        $validated = $this->validateProvider(
            $request,
            $provider
        );

        DB::transaction(
            function () use (
                $validated,
                $provider
            ): void {
                $provider->update([
                    'name' => trim(
                        $validated['name']
                    ),

                    'slug' => Str::lower(
                        trim($validated['slug'])
                    ),

                    'type' => $validated['type'],

                    'is_active' =>
                        (bool) $validated['is_active'],

                    'priority' =>
                        (int) $validated['priority'],

                    'config' =>
                        $this->buildConfig(
                            $validated,
                            $provider->config ?? []
                        ),

                    'metadata' =>
                        $this->buildMetadata(
                            $validated
                        ),
                ]);

                $this->saveFundingCharge(
                    $provider,
                    $validated
                );
            }
        );

        $provider = $provider->fresh([
            'fundingCharge',
        ]);

        $provider->loadCount([
            'virtualAccounts',
            'transactions',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Provider updated successfully.',

            'data' => $this->serializeProvider(
                $provider,
                true
            ),
        ]);
    }

    public function fundingCharge(
        Provider $provider
    ): JsonResponse {
        $provider->load('fundingCharge');

        return response()->json([
            'success' => true,

            'data' => $this->serializeFundingCharge(
                $provider->fundingCharge
            ),
        ]);
    }

    public function updateFundingCharge(
        Request $request,
        Provider $provider
    ): JsonResponse {
        $validated = $request->validate(
            $this->fundingChargeRules()
        );

        $fundingCharge = DB::transaction(
            function () use (
                $provider,
                $validated
            ): ProviderFundingCharge {
                return $provider
                    ->fundingCharge()
                    ->updateOrCreate(
                        [
                            'provider_id' =>
                                $provider->id,
                        ],
                        $this->fundingChargePayload(
                            $validated
                        )
                    );
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Provider funding charges updated successfully.',

            'data' =>
                $this->serializeFundingCharge(
                    $fundingCharge
                ),
        ]);
    }

    public function previewFundingCharge(
        Request $request,
        Provider $provider,
        FundingChargeService $fundingChargeService
    ): JsonResponse {
        $validated = $request->validate([
            'amount' => [
                new \App\Rules\ExactAmount,
                'required',
                'numeric',
                'min:0.01',
                'max:999999999999.99',
            ],
        ]);

        $provider->load('fundingCharge');

        $preview = $fundingChargeService->calculate(
            $provider,
            $validated['amount']
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Funding charge calculated successfully.',

            'data' => $preview,
        ]);
    }

    public function toggle(
        Provider $provider
    ): JsonResponse {
        $provider->update([
            'is_active' =>
                ! $provider->is_active,
        ]);

        $provider->load('fundingCharge');

        $provider->loadCount([
            'virtualAccounts',
            'transactions',
        ]);

        return response()->json([
            'success' => true,

            'message' => $provider->is_active
                ? 'Provider enabled successfully.'
                : 'Provider disabled successfully.',

            'data' => $this->serializeProvider(
                $provider,
                true
            ),
        ]);
    }

    public function test(
        Provider $provider
    ): JsonResponse {
        $config = $provider->config ?? [];

        $baseUrl = trim(
            (string) (
                $config['base_url'] ?? ''
            )
        );

        if ($baseUrl === '') {
            return response()->json([
                'success' => false,

                'message' =>
                    'This provider does not have a base URL.',
            ], 422);
        }

        if (
            ! filter_var(
                $baseUrl,
                FILTER_VALIDATE_URL
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'The provider base URL is invalid.',
            ], 422);
        }

        $scheme = Str::lower(
            (string) parse_url(
                $baseUrl,
                PHP_URL_SCHEME
            )
        );

        if (
            ! in_array(
                $scheme,
                [
                    'http',
                    'https',
                ],
                true
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'Only HTTP and HTTPS provider URLs are supported.',
            ], 422);
        }

        $startedAt = microtime(true);

        try {
            $response = Http::connectTimeout(5)
                ->timeout(
                    max(
                        5,
                        min(
                            (int) (
                                $provider->metadata[
                                    'timeout'
                                ] ?? 15
                            ),
                            30
                        )
                    )
                )
                ->acceptJson()
                ->get($baseUrl);

            $duration = (int) round(
                (
                    microtime(true) -
                    $startedAt
                ) * 1000
            );

            $healthy =
                $response->successful() ||
                $response->redirect() ||
                $response->clientError();

            $provider->update([
                'health_status' => $healthy
                    ? Provider::HEALTH_HEALTHY
                    : Provider::HEALTH_DEGRADED,

                'last_health_check' => now(),

                'metadata' => array_merge(
                    $provider->metadata ?? [],
                    [
                        'last_response_time_ms' =>
                            $duration,

                        'last_status_code' =>
                            $response->status(),
                    ]
                ),
            ]);

            return response()->json([
                'success' => $healthy,

                'message' => $healthy
                    ? 'Provider connection test completed successfully.'
                    : 'The provider responded with a server error.',

                'data' => [
                    'status_code' =>
                        $response->status(),

                    'response_time_ms' =>
                        $duration,

                    'health_status' =>
                        $provider->health_status,
                ],
            ], $healthy ? 200 : 422);
        } catch (
            ConnectionException $exception
        ) {
            $provider->update([
                'health_status' =>
                    Provider::HEALTH_UNHEALTHY,

                'last_health_check' => now(),
            ]);

            return response()->json([
                'success' => false,

                'message' =>
                    'The provider could not be reached.',

                'data' => [
                    'health_status' =>
                        Provider::HEALTH_UNHEALTHY,
                ],
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            $provider->update([
                'health_status' =>
                    Provider::HEALTH_UNHEALTHY,

                'last_health_check' => now(),
            ]);

            return response()->json([
                'success' => false,

                'message' =>
                    'The provider connection test failed.',
            ], 500);
        }
    }

    public function destroy(
        Provider $provider
    ): JsonResponse {
        $virtualAccountsCount = $provider
            ->virtualAccounts()
            ->count();

        $transactionsCount = $provider
            ->transactions()
            ->count();

        if (
            $virtualAccountsCount > 0 ||
            $transactionsCount > 0
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'This provider is already in use and cannot be deleted. Disable it instead.',

                'data' => [
                    'virtual_accounts' =>
                        $virtualAccountsCount,

                    'transactions' =>
                        $transactionsCount,
                ],
            ], 422);
        }

        DB::transaction(
            function () use ($provider): void {
                $provider
                    ->fundingCharge()
                    ->delete();

                $provider->delete();
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'Provider deleted successfully.',
        ]);
    }

    public function healthHistory(Request $request, Provider $provider): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $history = $provider->healthChecks()
            ->latest('checked_at')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json(['success' => true, 'data' => $history]);
    }

    public function resetCircuit(Provider $provider, ProviderManager $manager): JsonResponse
    {
        $provider = $manager->resetCircuit($provider);

        return response()->json([
            'success' => true,
            'message' => 'Provider circuit breaker reset successfully.',
            'data' => $this->serializeProvider($provider->load('fundingCharge'), true),
        ]);
    }

    public function performance(Request $request, Provider $provider): JsonResponse
    {
        $validated = $request->validate(['hours' => ['nullable', 'integer', 'min:1', 'max:2160']]);
        $since = now()->subHours((int) ($validated['hours'] ?? 24));
        $query = ProviderOperationAttempt::query()->where('provider_id', $provider->id)->where('attempted_at', '>=', $since);
        $total = (clone $query)->count();
        $successful = (clone $query)->where('status', 'success')->count();

        return response()->json(['success' => true, 'data' => [
            'provider_id' => $provider->id,
            'period_start' => $since,
            'attempts' => $total,
            'successful' => $successful,
            'failed' => max(0, $total - $successful),
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : null,
            'average_response_time_ms' => (int) round((float) ((clone $query)->whereNotNull('duration_ms')->avg('duration_ms') ?? 0)),
            'operations' => (clone $query)->selectRaw('operation, status, COUNT(*) as total')->groupBy('operation', 'status')->get(),
        ]]);
    }

    public function incidents(Request $request, Provider $provider): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([ProviderIncident::STATUS_OPEN, ProviderIncident::STATUS_RESOLVED])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $query = $provider->incidents()->latest('opened_at');
        if (! empty($validated['status'])) $query->where('status', $validated['status']);
        return response()->json(['success' => true, 'data' => $query->paginate((int) ($validated['per_page'] ?? 25))]);
    }

    public function resolveIncident(Provider $provider, ProviderIncident $incident): JsonResponse
    {
        abort_unless((int) $incident->provider_id === (int) $provider->id, 404);
        $incident->update(['status' => ProviderIncident::STATUS_RESOLVED, 'resolved_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Provider incident resolved.', 'data' => $incident->fresh()]);
    }

    public function reconcileTransaction(Transaction $transaction, ProviderOperationsService $operations): JsonResponse
    {
        abort_unless($transaction->type === Transaction::TYPE_COLLECTION, 422, 'Only collection transactions can be reconciled here.');
        $transaction = $operations->reconcileCollection($transaction);
        return response()->json(['success' => true, 'message' => 'Transaction reconciliation completed.', 'data' => $transaction]);
    }

    private function validateProvider(
        Request $request,
        ?Provider $provider = null
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9][a-z0-9\-]*$/',

                Rule::unique(
                    'providers',
                    'slug'
                )->ignore($provider?->id),
            ],

            'type' => [
                'required',

                Rule::in([
                    Provider::TYPE_VIRTUAL_ACCOUNT,
                    Provider::TYPE_PAYOUT,
                ]),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'priority' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'base_url' => [
                'nullable',
                'url',
                'max:500',
            ],

            'api_key' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'secret_key' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'password' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'webhook_secret' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'sandbox' => [
                'required',
                'boolean',
            ],

            'supports_virtual_account' => [
                'required',
                'boolean',
            ],

            'supports_payout' => [
                'required',
                'boolean',
            ],

            'supports_collection' => [
                'required',
                'boolean',
            ],

            'supports_transfer' => [
                'required',
                'boolean',
            ],

            'supports_webhook' => [
                'required',
                'boolean',
            ],

            'timeout' => [
                'required',
                'integer',
                'min:5',
                'max:120',
            ],

            ...$this->fundingChargeRules(),
        ], [
            'slug.regex' =>
                'The provider slug may contain only lowercase letters, numbers and hyphens.',
        ]);
    }

    private function fundingChargeRules(): array
    {
        return [
            'funding_charge_enabled' => [
                'required',
                'boolean',
            ],

            'funding_charge_type' => [
                'required',

                Rule::in([
                    ProviderFundingCharge::TYPE_PERCENTAGE,
                    ProviderFundingCharge::TYPE_FIXED,
                ]),
            ],

            'funding_percentage' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'funding_fixed_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'funding_maximum_charge' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'funding_minimum_charge' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],

            'funding_collection_mode' => [
                'required',

                Rule::in([
                    ProviderFundingCharge::MODE_ADD_ON,
                    ProviderFundingCharge::MODE_DEDUCT,
                ]),
            ],
        ];
    }

    private function saveFundingCharge(
        Provider $provider,
        array $validated
    ): ProviderFundingCharge {
        return $provider
            ->fundingCharge()
            ->updateOrCreate(
                [
                    'provider_id' =>
                        $provider->id,
                ],

                $this->fundingChargePayload(
                    $validated
                )
            );
    }

    private function fundingChargePayload(
        array $validated
    ): array {
        $enabled = (bool) (
            $validated[
                'funding_charge_enabled'
            ] ?? false
        );

        $type = $validated[
            'funding_charge_type'
        ] ?? ProviderFundingCharge::TYPE_PERCENTAGE;

        return [
            'is_enabled' => $enabled,

            'charge_type' => $type,

            'percentage' =>
                $enabled &&
                $type ===
                    ProviderFundingCharge::TYPE_PERCENTAGE
                    ? (string) \Brick\Math\BigDecimal::of(
                            $validated[
                                'funding_percentage'
                            ] ?? 0
                        )->toScale(4, \Brick\Math\RoundingMode::HalfUp)
                    : 0,

            'fixed_amount' =>
                $enabled &&
                $type ===
                    ProviderFundingCharge::TYPE_FIXED
                    ? (string) \Brick\Math\BigDecimal::of(
                            $validated[
                                'funding_fixed_amount'
                            ] ?? 0
                        )->toScale(2, \Brick\Math\RoundingMode::HalfUp)
                    : 0,

            'maximum_charge' =>
                $enabled
                    ? $this->nullableMoney(
                        $validated[
                            'funding_maximum_charge'
                        ] ?? null
                    )
                    : null,

            'minimum_charge' =>
                $enabled
                    ? $this->nullableMoney(
                        $validated[
                            'funding_minimum_charge'
                        ] ?? null
                    )
                    : null,

            'collection_mode' =>
                $validated[
                    'funding_collection_mode'
                ] ??
                ProviderFundingCharge::MODE_ADD_ON,
        ];
    }

    private function buildConfig(
        array $validated,
        array $existing = []
    ): array {
        $config = [
            'base_url' =>
                $validated['base_url'] ?? null,

            'username' =>
                $validated['username'] ?? null,

            'sandbox' =>
                (bool) $validated['sandbox'],
        ];

        foreach (
            [
                'api_key',
                'secret_key',
                'password',
                'webhook_secret',
            ] as $secretKey
        ) {
            $newValue = trim(
                (string) (
                    $validated[$secretKey] ?? ''
                )
            );

            $config[$secretKey] =
                $newValue !== ''
                    ? $newValue
                    : (
                        $existing[$secretKey]
                        ?? null
                    );
        }

        return array_filter(
            $config,

            static fn ($value): bool =>
                $value !== null
        );
    }

    private function buildMetadata(
        array $validated
    ): array {
        return [
            'supports_virtual_account' =>
                (bool) $validated[
                    'supports_virtual_account'
                ],

            'supports_payout' =>
                (bool) $validated[
                    'supports_payout'
                ],

            'supports_collection' =>
                (bool) $validated[
                    'supports_collection'
                ],

            'supports_transfer' =>
                (bool) $validated[
                    'supports_transfer'
                ],

            'supports_webhook' =>
                (bool) $validated[
                    'supports_webhook'
                ],

            'timeout' =>
                (int) $validated['timeout'],
        ];
    }

    private function serializeProvider(
        Provider $provider,
        bool $includeConfiguration
    ): array {
        $config = $provider->config ?? [];

        $provider->loadMissing(
            'fundingCharge'
        );

        $data = [
            'id' => $provider->id,
            'name' => $provider->name,
            'slug' => $provider->slug,
            'type' => $provider->type,

            'is_active' =>
                (bool) $provider->is_active,

            'priority' =>
                (int) $provider->priority,

            'health_status' =>
                $provider->health_status,

            'last_health_check' =>
                $provider->last_health_check,

            'metadata' =>
                $provider->metadata ?? [],

            'funding_charge' =>
                $this->serializeFundingCharge(
                    $provider->fundingCharge
                ),

            'virtual_accounts_count' =>
                (int) (
                    $provider
                        ->virtual_accounts_count
                    ?? 0
                ),

            'transactions_count' =>
                (int) (
                    $provider
                        ->transactions_count
                    ?? 0
                ),

            'created_at' =>
                $provider->created_at,

            'updated_at' =>
                $provider->updated_at,
        ];

        if ($includeConfiguration) {
            $data['config'] = [
                'base_url' =>
                    $config['base_url'] ?? '',

                'username' =>
                    $config['username'] ?? '',

                'sandbox' =>
                    (bool) (
                        $config['sandbox'] ?? false
                    ),

                'has_api_key' =>
                    ! empty($config['api_key']),

                'has_secret_key' =>
                    ! empty(
                        $config['secret_key']
                    ),

                'has_password' =>
                    ! empty($config['password']),

                'has_webhook_secret' =>
                    ! empty(
                        $config[
                            'webhook_secret'
                        ]
                    ),
            ];
        }

        return $data;
    }

    private function serializeFundingCharge(
        ?ProviderFundingCharge $charge
    ): array {
        if (! $charge) {
            return [
                'is_enabled' => false,
                'charge_type' =>
                    ProviderFundingCharge::TYPE_PERCENTAGE,

                'percentage' => 0,
                'fixed_amount' => 0,
                'maximum_charge' => null,
                'minimum_charge' => null,

                'collection_mode' =>
                    ProviderFundingCharge::MODE_ADD_ON,
            ];
        }

        return [
            'id' => $charge->id,

            'is_enabled' =>
                (bool) $charge->is_enabled,

            'charge_type' =>
                $charge->charge_type,

            'percentage' =>
                (float) $charge->percentage,

            'fixed_amount' =>
                (float) $charge->fixed_amount,

            'maximum_charge' =>
                $charge->maximum_charge !== null
                    ? (float) $charge->maximum_charge
                    : null,

            'minimum_charge' =>
                $charge->minimum_charge !== null
                    ? (float) $charge->minimum_charge
                    : null,

            'collection_mode' =>
                $charge->collection_mode,
        ];
    }

    private function nullableMoney(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        return (string) \Brick\Math\BigDecimal::of($value)->toScale(2, \Brick\Math\RoundingMode::HalfUp);
    }
}
