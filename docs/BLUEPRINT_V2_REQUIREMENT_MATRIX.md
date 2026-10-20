# Blueprint V2 requirement comparison ? 5 October 2026

Source: `docs/specifications/MGS_API_Platform_Blueprint_V2.docx`. User approved Laravel 13; only those two stack entries were changed, with the supplied Word file archived at `docs/specifications/archive/MGS_API_Platform_Blueprint_V2_as_supplied.docx`. All body paragraphs and table cells were read; extracted text is retained beside it. Requirements repeated in the roadmap and conclusion map to their functional entries below. Source inspection only: **every implemented item still needs user-run verification**. ?Existing? describes code found, not an acceptance result. ?Added? describes this change set. ?Blocked? states the precise external/decision dependency. No tests or application commands were run.

## Sections 1?5: scope, pricing, actors, flow and stack

| Requirement | Comparison and source evidence | Current disposition |
|---|---|---|
| MGS Technologies Limited; MGS API; MGS API Platform | Blueprint establishes names; developer documentation now states company/product | Added accurate product wording; legal/company profile approval remains external |
| Developer/VTU/business/fintech users; virtual accounts and collections as primary product | Customer, VA, transaction, wallet APIs and Vue merchant/admin layouts | Existing core |
| Approved merchants create accounts, receive notifications, manage wallets, verify payments and receive webhooks | BusinessLifecycleService, VirtualAccountService, CollectionService, WalletService, MerchantWebhookService | Existing core plus missing events/routes added |
| Software/API layer over licensed partners | ProviderManager/factory/adapters; local provider production guard | Existing architecture; partner product/approval needed |
| No direct unlicensed banking, card processing or premature public launch | No card processor implemented; local accounts marked test; launch remains gated | Scope restriction, partner/legal/launch approval external |
| VA creation free | VirtualAccountService creates no fee/debit | Existing |
| KYC free or cost pass-through | KYCService verifies without local charge | Decision: approved partner unit cost and who pays |
| Collection tier ?100??200: ?5 | BlueprintPricingSeeder exact inactive draft; CollectionPricingService exact arithmetic | Added; awaiting activation/boundary decision |
| ?201??1,000: ?10 | Same | Added inactive draft |
| ?1,001??5,000: ?25 | Same | Added inactive draft |
| ?5,001??9,999: ?50 | Same | Added inactive draft |
| ?10,000+: ?100 | Same; ?50,000 gives ?49,900 net under activated draft | Added; regression written |
| Pricing versus provider cost; admin adjustments | AdminPricingController/Pricing.vue; report cost coverage | Added local rules; contracted costs and reconciliation statements missing |
| Separate payout fees to be decided | MerchantWithdrawalSetting/WithdrawalRuleService/admin Withdrawal Settings | Existing configurable policy; commercial amounts unapproved |
| Subscription tiers later | No V1 subscription billing | Explicit future scope |
| Super/finance/support admins, merchant/developer, paying customer, provider/bank actors | Admin/User/Merchant/Customer/Provider models; seeded roles; provider rails external | Existing plus report permission added |
| Registration and account verification | AuthController registration OTP, login, recovery; devices/MFA | Existing |
| Business profile and administrator approval | MerchantActivationController, BusinessLifecycleService, admin activations | Existing; private documents retained |
| API keys only for eligible businesses | ApiKeyService, ApiKeyController, active merchant/business middleware | Existing |
| Create customer ? NIN/BVN ? VA | CustomerController, KYCService, VirtualAccountService | Existing asynchronous gate; Blueprint verify alias added |
| Validate key/status/KYC before calling provider | ApiKeyAuth, EnsureActiveBusiness, VirtualAccountService | Existing; new bearer compatibility added |
| Return provider account name/number/bank | VirtualAccountResource, provider result validation | Existing; certification needs real provider fixtures |
| Customer payment ? signed provider webhook ? wallet ? merchant webhook | WebhookController, WebhookService, CollectionService, outbox | Existing plus canonical events/revenue added; provider acceptance blocked |
| Laravel 13 REST backend | User approved retaining installed Laravel ^13.8; both stack entries in Word specification updated | Decision closed; original Word archived, no dependency change |
| Vue 3 | package.json requires Vue ^3.5 | Existing |
| MariaDB | Laravel database configuration and prior upgrade migrations | Existing support; no live DB inspected or commands run this turn |
| Sanctum dashboard auth, API keys for public API | BrowserAuthentication/actor middleware/ApiKeyAuth | Existing; bearer-only lookup added for rotated keys |
| Laravel queues; Redis later | Jobs, queue configuration, retry scheduler and shared lock requirements | Existing; operator deployment needed |
| Ubuntu VPS/Nginx/PHP 8.3+ | composer PHP ^8.3; deployment/runbook instructions | Target host, access, TLS/proxy and process supervision missing |
| Local storage in development; cloud backup production | Private local uploads and new private backup command | Local implementation; cloud destination/retention/encryption key ownership missing |
| Monitoring/API logs/provider checks | LogDeveloperRequest, CheckProviderHealth, new CheckSystemHealth | Added application checks; host monitor installation external |

## Section 6: dashboards

| Module/requirement | Source comparison | Disposition |
|---|---|---|
| Merchant overview balance, today transactions, success rate, API status | Dashboard.vue now uses MerchantDashboardController full-business aggregate queries | Added; avoids totals from latest 100 ledger records |
| Merchant customers and KYC status | Customers.vue, CustomerController/kycStatus | Existing; identity mask supplied for new queued verifications |
| Merchant VA list/provider/status/customer | VirtualAccounts.vue and VirtualAccountResource | Existing |
| Pending/successful/failed/reversed transactions | Transactions.vue, TransactionService status filters | Existing; provider reversal semantics require contract |
| Wallet available/ledger/history | Wallet.vue, WalletController/WalletService | Existing |
| Payout request/history/bank management | Payouts.vue; new BankAccounts.vue and protected bank change endpoints | Added missing bank page/security |
| Live/test keys, rotation, IP whitelist | ApiKeys.vue/ApiKeyService; test operations fail closed | Existing live operations; sandbox contract/isolation unfinished |
| Webhook URL/secret/retry logs/failed deliveries | Webhooks.vue/MerchantWebhookController/outbox retries | Existing; event options expanded |
| API logs endpoint/status/IP/response code | New ApiLogs.vue and ApiLogController | Added business-scoped metadata, pagination and filters |
| Profile/password/security/PIN/2FA | Settings.vue/UserSecuritySettings.vue/AuthController | Existing |
| Admin totals merchants/collections/revenue/provider/failed webhooks | AdminDashboardController aggregates; provider/failure/health fields added | Existing plus missing summary fields; host acceptance pending |
| Admin merchant approve/suspend/documents/balances | AdminMerchantController/activation views/BusinessLifecycleService | Existing |
| Admin customers/KYC | AdminCustomerController/Customers.vue | Existing |
| Admin failed/suspicious KYC review | AdminKycController/KycRequests.vue | Added notes/retry. Manual fake verification never substitutes for provider approval; override policy requires compliance decision |
| Admin all virtual accounts by provider/status | AdminVirtualAccountController index and VirtualAccounts.vue | Added |
| Admin transaction search/filter/issues | AdminTransactionController/Transactions.vue and provider reconciliation controls | Existing; financial resolution depends on authoritative statements |
| High-risk/high-value payout approval | AdminPayoutController; thresholds and contextual device/IP hold | Existing plus contextual risk |
| Provider configurations | Providers.vue/controller/factory | Existing; new config writes encrypted, legacy readable |
| Collection ranges/payout fees | Pricing.vue/AdminPricingController; WithdrawalSettings.vue | Added collection editor; existing payout settings |
| Fee/provider cost/net profit reports | PlatformReportService/Reports.vue/revenues | Added separate collection fee evidence and cost coverage. Costs stay null until contracted accounting inputs supplied; no guessed profit |
| Support tickets | SupportController/AdminSupportController and views | Existing |
| Audit history | AdminAuditController/AuditLogs.vue, existing activity logs | Added actual security audit feed |
| Failed webhook dashboard | AdminWebhookLogController/WebhookLogs.vue | Added cross-business delivery metadata; existing automatic retries retained |
| Notification bell | MerchantNotificationController/MerchantLayout.vue | Replaced hardcoded merchant notices with stored owner-scoped records |

## Sections 7?9: schema, ledger and identity

| Requirement | Source comparison | Disposition |
|---|---|---|
| users/wallets/wallet_transactions | Existing tables/models; financial ownership explicitly merchant + business | Existing normalized schema differs from simplified users.id ERD intentionally |
| customers/customer_kyc_verifications | Existing customer lifecycle | Existing; forward status migration fixes processing exclusion |
| api_keys/public-secret-webhook-environment | Existing keys hash secret; new fingerprint is lookup only, still checked with password hash | Added lookup; no existing key rehashed/rotated |
| providers/virtual_accounts/transactions | Existing relational models/migrations | Existing |
| webhook_endpoints/webhook_logs | Existing business ownership/outbox fields/repair migration | Existing |
| payouts/bank_accounts | Existing business ownership/verification/reservation | Added destination hold and bank challenge table |
| pricing_rules | Existing table was unused by collections | Added calculation and management |
| revenues linked uniquely to transaction | Previously missing | Added table/model and atomic successful collection record |
| api_logs | Existing metadata logging | Added nullable business FK/index; historical null rows retained |
| admins/roles/permissions | Existing actor models/pivots/seeded roles | Existing; reports permission added |
| support_tickets/system_settings/audit_logs | Existing | Existing; maintenance enforcement/audit view added |
| ERD customers?KYC/VA, provider?VA, VA?transaction, wallet?ledger, bank?payout, merchant?keys/webhooks/logs | Existing model relations and foreign keys | Existing; revenue relation and log business FK added; do not rewrite ownership to simplified user IDs |
| Every merchant has wallet | Registration/business creation creates wallets per business | Existing stronger business isolation |
| Every balance mutation creates ledger evidence | WalletService credits/debits/reservation/capture/release | Existing; no balance repair/reset executed |
| Available versus ledger/held balances | Wallet fields available/ledger/reserved | Existing |
| ?50,000 gross ? ?100 = ?49,900 | Activated Blueprint draft calculation | Added unexecuted regression |
| Every credit reference | WalletService creates references and unique source semantics | Existing |
| Debit checks spendable balance | WalletService ensureAvailableBalance and business lock | Existing |
| Fees separately recorded | Successful collection revenue row, transaction fee | Added; payout fees already separate fields; cost policy external |
| Failed callbacks never credit | CollectionService requires successful provider verification | Existing; payment.failed outbox added |
| Duplicate references never double credit | Provider/reference uniqueness, transaction lock, wallet source idempotency | Existing; revenue/outbox repeat regressions written |
| Customer passes approved NIN or BVN before VA | VirtualAccountService verified-type checks and KYCProviderInterface | Existing local gate; approved provider/consent/spec/fixtures missing |
| Avoid raw NIN/BVN storage | Pending numbers encrypted for async calls; verified transient number now cleared | Added clearing/recursive redaction/masks; failed retry retention and legacy scrub need approved retention policy |
| Store verified result/mask/provider reference/name | KYCIdentity and verification result; new masked_identity metadata | Existing plus mask; provider-specific mapping external |
| Failed KYC blocks accounts | VirtualAccountService requirement gate; customer/business activation state | Existing |

## Sections 10?14: API security, endpoints, callbacks, withdrawals and providers

| Requirement | Source comparison | Disposition |
|---|---|---|
| Public/secret/webhook keys | ApiKeyService one-time secret, hashed storage, scoped keys | Existing |
| Bearer secret authentication | Previously required X-MGS-Public-Key too | Added bearer-only new-key fingerprint lookup; legacy keys require public header until rotation |
| All public requests logged | Logging wraps key authentication/rate/signature pipeline; metadata only | Added denied-key logging. Anonymous failures have no owner association; dashboard-only denials do not expose credentials |
| Rate limits | RateLimitApi, named auth/money/provider throttles | Existing |
| Optional IP whitelist | ApiKeyAuth exact source allowlist | Existing |
| HMAC raw body + timestamp with secret key | Existing middleware used nonce/webhook secret | Added Blueprint scheme; published nonce scheme retained; five-minute freshness/replay cache |
| Key rotation dashboard | ApiKeys.vue/ApiKeyService atomic revoke/replace | Existing; notification and fingerprint added |
| Test/live separation | Live business gate; test keys explicitly unavailable | Blocked: selected provider sandbox contract/fixtures and isolated test-ledger/provisioning policy. Do not enable test keys against operational wallets |
| POST /api/v1/customers | CustomerController store | Existing |
| POST /api/v1/customers/verify | Missing exact alias | Added id/type/bvn-or-nin delegation, scoped KYC and idempotency |
| POST /api/v1/virtual-accounts | VirtualAccountController store | Existing |
| GET /api/v1/virtual-accounts/{id} | VirtualAccountController show | Existing |
| GET /api/v1/transactions | TransactionController index | Existing |
| GET /api/v1/transactions/verify/{reference} | Only /transactions/{reference} existed | Added scoped local settlement-state alias; not a provider success claim |
| GET /api/v1/wallet/balance | WalletController balance | Existing |
| Provider?MGS signature verified and incoming logged | WebhookController/WebhookService/processing jobs | Existing; authentic raw provider signature fixtures required |
| Duplicate check?transaction?wallet?outgoing webhook | CollectionService locks and transactional outbox | Existing plus unique revenue entry |
| MGS?merchant signed and logged | MerchantWebhookService HMAC timestamp.raw body; WebhookLog | Existing; safe URL/DNS controls retained |
| payment.success | Previously collection.success | Added canonical alias, one selected success event per endpoint; legacy subscribers retained |
| payment.failed | Missing outgoing event | Added at verified failure, no credit/revenue |
| payout.success | Missing outgoing event | Added atomic terminal transition event and merchant notification |
| payout.failed | Missing outgoing event | Added provider failure/admin rejection event with fund-release transition |
| virtual_account.created | Missing outgoing event | Added within account creation transaction; skip test accounts |
| Merchant verifies signature | Replaced hardcoded docs with exact current request/webhook protocols | Added |
| Approved merchant before payout | PayoutService/EnsureActiveBusiness | Existing |
| Provider name enquiry before saving bank | PayoutController provider verification, not browser supplied name | Existing; contracted name-enquiry protocol/credentials missing |
| Withdrawal PIN | OTP send validates stored hash | Existing |
| Email OTP or authenticator | WithdrawalOtpController bound amount/account/user/business | Existing email OTP; SMTP acceptance pending |
| New device/strange IP extra verification | Device lifecycle existed, payout context not checked | Added untrusted/new/IP-changed manual-review hold in addition to mandatory OTP |
| Bank change OTP and 24-hour delay | Previously absent | Added password-confirmed challenge send, exact destination binding, attempt cap/expiry/single use, new/reverified destination hold |
| Daily verification-tier limits | WithdrawalRuleService business type settings + lock/aggregate | Existing; approved threshold values needed |
| Large/suspicious holds | Setting thresholds + new PayoutRiskService | Existing amounts, added contextual holds; richer score weights need risk policy |
| Admin high-value review | Pending manual payouts and AdminPayoutController | Existing |
| Audit every payout action | Request/terminal/admin approval/rejection events added | Added; external reconciliation decisions need evidence |
| Balance?PIN/OTP?risk/limit?provider/review?status/notification | PayoutService reservations, OTP consumption, jobs, original-provider reconciliation | Existing plus missing risk/events/notifications |
| No direct provider API in controllers | Controllers delegate service/manager/factory | Existing |
| Best active provider priority/health | ProviderManager cached priority/health/circuit; health command | Existing |
| OPay/PalmPay/Monnify services | Separate adapters exist | Monnify mapping exists; all real integrations require product docs/credentials/fixtures/certification |
| PayVessel service | Exact provider adapter missing; generic fallback is not certification | Blocked: selected PayVessel product/version/auth/VA/payout/status/signature contract; do not invent endpoints |
| Other approved providers | NinePsb and generic guarded adapter exist | Contract and partner selection required |
| Provider failure rates/availability | ProviderHealthCheck/operations/incidents/circuit state | Existing |
| Failover next provider | VA failover; uncertain payout never resubmitted blindly | Existing safe policy; contract-specific timeout/reversal rules missing |

## Sections 15?20: communications, roles, operations, roadmap and decisions

| Requirement | Comparison | Disposition |
|---|---|---|
| Payment-received email | WalletCredited event/listener + NotificationService | Existing; SMTP/worker delivery acceptance missing |
| Payout-success email | New terminal transition notification | Added |
| API-reset email | New key-rotation notification | Added |
| Login alert | New login notification | Added |
| SMS/OTP payout/login/reset | Email OTP/reset supported; no contracted SMS provider | Blocked: SMS supplier, sender ID/templates, cost/consent policy and credentials; email alternative retained |
| In-app important events | Stored notification service and new real merchant bell | Added owner-scoped feed/mark-read |
| VA/transaction/payout/API support cases | Existing support tickets/categories/replies/admin actions | Existing |
| Login/password/PIN/bank/payout/key/admin audit | Existing auth/device/security logs plus new bank/pricing/payout audits and AuditLogs.vue | Added missing coverage; see existing ActivityLogService/AuditService |
| Super admin full access | Admin/RequirePermission role logic | Existing |
| Finance transactions/wallets/payouts/revenue/settlement reports | Seeded finance permissions; reports.view added | Added permission; operator must apply reviewed role seed/update |
| Support merchant/customer/ticket/basic transactions | support_admin seeded read/support permissions | Existing |
| Merchant own dashboard/data/keys/payouts | Actor checks, business scopes/policies | Existing; new endpoints preserve scopes |
| Merchant staff limited access later | No V1 staff implementation | Explicit future scope |
| Daily DB backup | BackupDatabase body was empty | Added consistent private SQLite or MariaDB dump and daily schedule; binary permissions/backup restore user acceptance needed |
| Weekly full-server backup | Application cannot configure an unknown VPS backup provider | Blocked: target server/backup facility/access, retention and encryption policy |
| Cloud backup copy | No destination configured | Blocked: off-server target/credentials, encryption key custody/restore procedure |
| Provider callback/delivery retries | Existing reconciliation/outbox scheduler | Existing; deployed failure/crash/retry acceptance needed |
| Failed delivery dashboard | New admin metadata view | Added |
| CPU/RAM/disk/API error monitoring | New app DB/storage/free space/API errors/process-memory checks | Added app telemetry; host CPU/RAM agent and alert routing need server infrastructure |
| Emergency maintenance | Stored switch previously unenforced | Added middleware; admin and signed provider callbacks retained to permit recovery/settlement |
| Incident logs/admin alerts | ProviderIncident, NotificationService; new backup/health alerts | Existing plus local operational alerts; out-of-band alerting external |
| Phase 1 planning: Blueprint/ERD/flow/pricing/security/API | Blueprint supplies conceptual flows/ERD; this matrix maps actual normalized ownership | Read and compared; commercial gaps explicit |
| Phase 2 backend foundation | Existing auth/users/wallets/keys/providers/logs | Existing plus security/log additions |
| Phase 3 core API | Existing customer/KYC/VA/transaction APIs | Exact aliases added; provider acceptance blocked |
| Phase 4 dashboards | Existing layouts/views | Missing pages/real feeds/aggregates added; browser acceptance pending |
| Phase 5 first sandbox/live provider | No certified external integration evidence | Provider choice/contract/credentials/allowed fixtures missing |
| Phase 6 callbacks/wallet/merchant events | Existing verification/ledger/outbox | Missing events/revenue added; crash acceptance pending |
| Phase 7 PIN/OTP/payout/review | Existing local controls | Bank protection/contextual risk/audit added |
| Phase 8 tests/security/live pilot | Regression files and isolated execution configuration | User owns execution; no new verification claim; pilot requires provider/launch approvals |
| Phase 9 deployment/monitoring/support/scaling | Existing runbooks; local monitor/backup added | Target host/domain, provider partnership and operations approvals missing |
| Confirm company/product names | Supplied document is authoritative planning input | MGS Technologies Limited / MGS API recorded |
| Confirm developer domain | Not specified | Exact domain/subdomain, DNS/TLS ownership and deployment access missing |
| Confirm first provider | Alternatives listed, none selected | Exact approved partner/product missing |
| Confirm KYC provider/approach | Generic HTTP contract placeholder exists | Approved verifier/product/consent/contract missing |
| Review pricing/provider fees | Five drafts implemented | Boundary/low-amount/payer/cost decisions missing |
| Review payout security rules | Compared all ten named layers; local gaps implemented | Approved thresholds/device trust/risk/retention policy and sandbox acceptance missing |
| Review schema/relationships | Compared all 21 table entries and ERD relationships | Three additive forward migrations prepared, not applied |
| Review menus/user flow | Compared every named module | New pages/routes/notifications/docs written; build/browser checks pending |
| Partnership proposal/company profile | No approved commercial materials supplied | Registered details, authorized contacts, provider target and approved proposal content missing |
| Conclusion first version scope and no premature banking claims | Core scope maps above; future items excluded | No production readiness or licensed status asserted |

## Exact unresolved inputs

1. Collection amounts below ?100, ?200.01??200.99, ?1,000.01??1,000.99, ?5,000.01??5,000.99 and ?9,999.01??9,999.99 have no written tier. Confirm how to charge them and any cap/refund/charge-payer policy. New draft rules remain inactive; active uncovered ranges fail rather than guess.
2. Framework decision closed: user approved Laravel 13 on 5 October 2026. Both Word stack entries now specify Laravel 13; original supplied Word is archived.
3. Select the first partner and product. Supply approved versioned authentication, account/KYC/name-enquiry/payout/verification contracts, status codes, idempotency/failover rules, raw signed webhook fixtures, timeout/reversal cases, statement fields, permitted sandbox identities/beneficiaries and callback registration. Supply credentials only in the isolated staging configuration, never in chat/source.
4. Decide whether test mode is a provider sandbox or an explicitly approved simulator. Supply sandbox endpoints/fixtures and isolation policy before test key operations are enabled.
5. Supply contracted per-collection/per-payout/KYC costs, settlement/reversal statements and approved fee ownership before computing profit or importing costs.
6. Supply deployment domain/host/topology, PHP/dump binary, queue/shared cache supervision, SMTP/SMS provider setup, CPU/RAM monitor/alerts and encrypted off-server/weekly backup target/access/retention. User must execute snapshot/restore acceptance.
7. Supply approved partnership/company profile, policy/consent/retention text, device trust/risk thresholds, reconciled opening balances and launch authorization.
