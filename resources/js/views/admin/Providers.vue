<template>
    <div class="space-y-6">
        <!-- Header -->
        <section class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">
                    Payment infrastructure
                </p>

                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                    Providers
                </h2>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Manage PalmPay, 9PSB and other virtual-account or payout providers.
                    Configure credentials, priority, supported services and health status.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                    :disabled="loading"
                    @click="refreshPage"
                >
                    {{ loading ? 'Refreshing…' : '↻ Refresh' }}
                </button>

                <button
                    type="button"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
                    @click="openCreateModal"
                >
                    ＋ Add Provider
                </button>
            </div>
        </section>

        <!-- Statistics -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="summary in summaries"
                :key="summary.label"
                class="flex min-h-[125px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="summary.iconClass"
                >
                    {{ summary.icon }}
                </span>

                <span>
                    <span class="block text-2xl font-black text-slate-950 dark:text-white">
                        {{ summary.value }}
                    </span>

                    <span class="mt-1 block text-sm font-semibold text-slate-500">
                        {{ summary.label }}
                    </span>
                </span>
            </article>
        </section>

        <!-- Priority explanation -->
        <section class="rounded-3xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-900/50 dark:bg-indigo-950/30">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white">
                    ⇅
                </span>

                <div>
                    <h3 class="font-black text-indigo-950 dark:text-indigo-200">
                        Provider priority and fallback
                    </h3>

                    <p class="mt-1 text-sm leading-6 text-indigo-800 dark:text-indigo-300">
                        A lower priority number runs first. For example:
                        PalmPay priority 1, 9PSB priority 2. When the primary provider
                        fails, your integration can automatically try the next active,
                        healthy provider.
                    </p>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <form
                class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_210px_190px_190px_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                        ⌕
                    </span>

                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search PalmPay, 9PSB or another provider..."
                        class="provider-input pl-11"
                    />
                </div>

                <select v-model="filters.type" class="provider-input">
                    <option value="">All provider types</option>
                    <option value="virtual_account">Virtual Account</option>
                    <option value="payout">Payout</option>
                </select>

                <select v-model="filters.status" class="provider-input">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <select v-model="filters.health_status" class="provider-input">
                    <option value="">All health states</option>
                    <option value="healthy">Healthy</option>
                    <option value="degraded">Degraded</option>
                    <option value="unhealthy">Unhealthy</option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700"
                    >
                        Apply
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300"
                        @click="resetFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>
        </section>

        <section
            v-if="successMessage"
            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300"
        >
            {{ successMessage }}
        </section>

        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <!-- Loading -->
        <section v-if="loading" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="item in 6"
                :key="item"
                class="h-[390px] animate-pulse rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="h-14 w-14 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-5 h-6 w-1/2 rounded bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-3 h-4 w-1/3 rounded bg-slate-200 dark:bg-slate-800"></div>
            </article>
        </section>

        <!-- Empty -->
        <section
            v-else-if="providers.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950">
                ◎
            </span>

            <h3 class="mt-5 text-xl font-black">
                No providers found
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                Add PalmPay, 9PSB or another provider to begin.
            </p>

            <button
                type="button"
                class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white"
                @click="openCreateModal"
            >
                Add first provider
            </button>
        </section>

        <!-- Provider cards -->
        <section v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="provider in providers"
                :key="provider.id"
                class="flex min-h-[410px] flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-700 text-xl font-black text-white"
                    >
                        {{ providerInitials(provider) }}
                    </span>

                    <div class="flex flex-col items-end gap-2">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-black"
                            :class="provider.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                        >
                            {{ provider.is_active ? 'Active' : 'Disabled' }}
                        </span>

                        <span
                            class="rounded-full px-3 py-1 text-xs font-black"
                            :class="healthClass(provider.health_status)"
                        >
                            {{ formatHealth(provider.health_status) }}
                        </span>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        Priority #{{ provider.priority }}
                    </p>

                    <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">
                        {{ provider.name }}
                    </h3>

                    <p class="mt-1 font-mono text-xs text-slate-500">
                        {{ provider.slug }}
                    </p>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Type</dt>
                        <dd class="font-bold">{{ formatType(provider.type) }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Virtual Accounts</dt>
                        <dd class="font-black">{{ provider.virtual_accounts_count || 0 }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Transactions</dt>
                        <dd class="font-black">{{ provider.transactions_count || 0 }}</dd>
                    </div>

                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Last Health Check</dt>
                        <dd class="text-right font-semibold">
                            {{ formatDateTime(provider.last_health_check) }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span
                        v-for="capability in capabilities(provider)"
                        :key="capability"
                        class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ capability }}
                    </span>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-slate-400">Funding Charge</p>
                            <p class="mt-2 text-sm font-black">
                                {{ fundingChargeDescription(provider.funding_charge) }}
                            </p>
                        </div>

                        <span
                            class="rounded-full px-2.5 py-1 text-[10px] font-black"
                            :class="provider.funding_charge?.is_enabled
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                        >
                            {{ provider.funding_charge?.is_enabled ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>

                <div class="mt-auto grid grid-cols-2 gap-3 pt-6">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300"
                        @click="testProvider(provider)"
                    >
                        Test
                    </button>

                    <button
                        type="button"
                        class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-black text-white hover:bg-indigo-700"
                        @click="openDetails(provider)"
                    >
                        Manage
                    </button>
                </div>
            </article>
        </section>

        <!-- Pagination -->
        <section
            v-if="pagination.last_page > 1"
            class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:flex-row"
        >
            <p class="text-sm text-slate-500">
                Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }}
                of {{ pagination.total || 0 }} providers
            </p>

            <div class="flex gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchProviders(pagination.current_page - 1)"
                >
                    Previous
                </button>

                <span class="px-3 py-2 text-sm font-black">
                    {{ pagination.current_page }} / {{ pagination.last_page }}
                </span>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="fetchProviders(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>

        <!-- Create/edit modal -->
        <Teleport to="body">
            <div
                v-if="formModalOpen"
                class="fixed inset-0 z-[210] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeFormModal"
            >
                <div class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950">
                    <header class="flex items-start justify-between border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wider text-indigo-600">
                                Provider configuration
                            </p>

                            <h3 class="mt-1 text-2xl font-black">
                                {{ editingProvider ? 'Edit Provider' : 'Add Provider' }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeFormModal"
                        >
                            ✕
                        </button>
                    </header>

                    <form
                        class="flex-1 overflow-y-auto p-4 sm:p-7"
                        @submit.prevent="saveProvider"
                    >
                        <div
                            v-if="formError"
                            class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                        >
                            {{ formError }}
                        </div>

                        <section class="provider-section">
                            <h4 class="provider-section-title">Provider Information</h4>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <FormField label="Provider Name" :error="fieldErrors.name">
                                    <input
                                        v-model.trim="form.name"
                                        type="text"
                                        class="provider-input"
                                        placeholder="PalmPay"
                                        @input="handleNameInput"
                                    />
                                </FormField>

                                <FormField label="Provider Slug" :error="fieldErrors.slug">
                                    <input
                                        v-model.trim="form.slug"
                                        type="text"
                                        class="provider-input"
                                        placeholder="palmpay"
                                    />
                                </FormField>

                                <FormField label="Provider Type" :error="fieldErrors.type">
                                    <select v-model="form.type" class="provider-input">
                                        <option value="virtual_account">Virtual Account</option>
                                        <option value="payout">Payout</option>
                                    </select>
                                </FormField>

                                <FormField label="Priority" :error="fieldErrors.priority">
                                    <input
                                        v-model.number="form.priority"
                                        type="number"
                                        min="0"
                                        max="9999"
                                        class="provider-input"
                                    />
                                </FormField>
                            </div>

                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <ToggleField
                                    v-model="form.is_active"
                                    title="Provider Enabled"
                                    description="Allow the platform to use this provider."
                                />

                                <ToggleField
                                    v-model="form.sandbox"
                                    title="Sandbox Mode"
                                    description="Use the provider test environment."
                                />
                            </div>
                        </section>

                        <section class="provider-section mt-6">
                            <h4 class="provider-section-title">Connection and Credentials</h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Leave an existing secret field blank while editing to preserve its current value.
                            </p>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <FormField class="md:col-span-2" label="Base URL" :error="fieldErrors.base_url">
                                    <input
                                        v-model.trim="form.base_url"
                                        type="url"
                                        class="provider-input"
                                        placeholder="https://api.provider.com"
                                    />
                                </FormField>

                                <FormField label="API Key" :error="fieldErrors.api_key">
                                    <input
                                        v-model="form.api_key"
                                        type="password"
                                        class="provider-input"
                                        placeholder="Enter API key"
                                    />
                                </FormField>

                                <FormField label="Secret Key" :error="fieldErrors.secret_key">
                                    <input
                                        v-model="form.secret_key"
                                        type="password"
                                        class="provider-input"
                                        placeholder="Enter secret key"
                                    />
                                </FormField>

                                <FormField label="Username" :error="fieldErrors.username">
                                    <input
                                        v-model.trim="form.username"
                                        type="text"
                                        class="provider-input"
                                        placeholder="API username"
                                    />
                                </FormField>

                                <FormField label="Password" :error="fieldErrors.password">
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        class="provider-input"
                                        placeholder="API password"
                                    />
                                </FormField>

                                <FormField class="md:col-span-2" label="Webhook Secret" :error="fieldErrors.webhook_secret">
                                    <input
                                        v-model="form.webhook_secret"
                                        type="password"
                                        class="provider-input"
                                        placeholder="Webhook verification secret"
                                    />
                                </FormField>
                            </div>
                        </section>

                        <section class="provider-section mt-6">
                            <h4 class="provider-section-title">Capabilities</h4>

                            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <ToggleField
                                    v-model="form.supports_virtual_account"
                                    title="Virtual Accounts"
                                    description="Create dedicated customer accounts."
                                />

                                <ToggleField
                                    v-model="form.supports_payout"
                                    title="Payouts"
                                    description="Send merchant settlements."
                                />

                                <ToggleField
                                    v-model="form.supports_collection"
                                    title="Collections"
                                    description="Receive incoming payments."
                                />

                                <ToggleField
                                    v-model="form.supports_transfer"
                                    title="Transfers"
                                    description="Perform bank transfers."
                                />

                                <ToggleField
                                    v-model="form.supports_webhook"
                                    title="Webhooks"
                                    description="Receive provider event callbacks."
                                />

                                <FormField label="Request Timeout" :error="fieldErrors.timeout">
                                    <div class="relative">
                                        <input
                                            v-model.number="form.timeout"
                                            type="number"
                                            min="5"
                                            max="120"
                                            class="provider-input pr-20"
                                        />
                                        <span class="absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">
                                            seconds
                                        </span>
                                    </div>
                                </FormField>
                            </div>
                        </section>

                        <section class="provider-section mt-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h4 class="provider-section-title">Funding Charges</h4>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Configure the fee applied when this provider is used to fund a merchant wallet.
                                    </p>
                                </div>

                                <span
                                    class="w-fit rounded-full px-3 py-1 text-xs font-black"
                                    :class="form.funding_charge_enabled
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                >
                                    {{ form.funding_charge_enabled ? 'Charge Enabled' : 'No Charge' }}
                                </span>
                            </div>

                            <div class="mt-5">
                                <ToggleField
                                    v-model="form.funding_charge_enabled"
                                    title="Enable Funding Charge"
                                    description="Apply this provider-specific charge before payment confirmation."
                                />
                            </div>

                            <div v-if="form.funding_charge_enabled" class="mt-5 grid gap-5 md:grid-cols-2">
                                <FormField label="Charge Type" :error="fieldErrors.funding_charge_type">
                                    <select v-model="form.funding_charge_type" class="provider-input">
                                        <option value="percentage">Percentage</option>
                                        <option value="fixed">Fixed Amount</option>
                                    </select>
                                </FormField>

                                <FormField
                                    v-if="form.funding_charge_type === 'percentage'"
                                    label="Percentage Charge"
                                    :error="fieldErrors.funding_percentage"
                                >
                                    <div class="relative">
                                        <input
                                            v-model.number="form.funding_percentage"
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.0001"
                                            class="provider-input pr-14"
                                        />
                                        <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">%</span>
                                    </div>
                                </FormField>

                                <FormField
                                    v-else
                                    label="Fixed Charge"
                                    :error="fieldErrors.funding_fixed_amount"
                                >
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-4 flex items-center font-black text-slate-400">₦</span>
                                        <input
                                            v-model.number="form.funding_fixed_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="provider-input pl-10"
                                        />
                                    </div>
                                </FormField>

                                <FormField label="Maximum Charge Cap" :error="fieldErrors.funding_maximum_charge">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-4 flex items-center font-black text-slate-400">₦</span>
                                        <input
                                            v-model="form.funding_maximum_charge"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="provider-input pl-10"
                                            placeholder="Optional"
                                        />
                                    </div>
                                </FormField>

                                <FormField label="Minimum Charge" :error="fieldErrors.funding_minimum_charge">
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-4 flex items-center font-black text-slate-400">₦</span>
                                        <input
                                            v-model="form.funding_minimum_charge"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="provider-input pl-10"
                                            placeholder="Optional"
                                        />
                                    </div>
                                </FormField>

                                <FormField label="Charge Collection Mode" :error="fieldErrors.funding_collection_mode">
                                    <select v-model="form.funding_collection_mode" class="provider-input">
                                        <option value="add_on">Add charge to total payable</option>
                                        <option value="deduct">Deduct charge from wallet credit</option>
                                    </select>
                                </FormField>

                                <div class="md:col-span-2 rounded-2xl border border-indigo-200 bg-indigo-50 p-5 dark:border-indigo-900/50 dark:bg-indigo-950/30">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                        <FormField class="flex-1" label="Preview Funding Amount">
                                            <div class="relative">
                                                <span class="absolute inset-y-0 left-4 flex items-center font-black text-slate-400">₦</span>
                                                <input
                                                    v-model="fundingPreviewAmount"
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    class="provider-input pl-10"
                                                    placeholder="10000"
                                                />
                                            </div>
                                        </FormField>

                                        <button
                                            type="button"
                                            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                                            :disabled="previewingFunding"
                                            @click="calculateLocalFundingPreview"
                                        >
                                            {{ previewingFunding ? 'Calculating…' : 'Preview Charge' }}
                                        </button>
                                    </div>

                                    <p v-if="fundingPreviewError" class="mt-3 text-sm text-red-600">
                                        {{ fundingPreviewError }}
                                    </p>

                                    <div v-if="fundingPreviewResult" class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Funding Amount</p>
                                            <p class="mt-2 font-black">₦{{ formatCurrency(fundingPreviewResult.amount) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Charge</p>
                                            <p class="mt-2 font-black">₦{{ formatCurrency(fundingPreviewResult.charge) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Payable</p>
                                            <p class="mt-2 font-black">₦{{ formatCurrency(fundingPreviewResult.total_payable) }}</p>
                                        </div>
                                        <div class="rounded-xl bg-white p-4 dark:bg-slate-900">
                                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Wallet Credit</p>
                                            <p class="mt-2 font-black">₦{{ formatCurrency(fundingPreviewResult.wallet_credit) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300"
                                :disabled="saving"
                                @click="closeFormModal"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700 disabled:opacity-50"
                                :disabled="saving"
                            >
                                {{ saving ? 'Saving…' : editingProvider ? 'Update Provider' : 'Create Provider' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Details modal -->
        <Teleport to="body">
            <div
                v-if="detailsModalOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeDetails"
            >
                <div class="flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950">
                    <header class="flex items-start justify-between border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="selectedProvider.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                >
                                    {{ selectedProvider.is_active ? 'Active' : 'Disabled' }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="healthClass(selectedProvider.health_status)"
                                >
                                    {{ formatHealth(selectedProvider.health_status) }}
                                </span>
                            </div>

                            <h3 class="mt-3 text-2xl font-black">
                                {{ selectedProvider.name }}
                            </h3>

                            <p class="mt-1 font-mono text-xs text-slate-500">
                                {{ selectedProvider.slug }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeDetails"
                        >
                            ✕
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div v-if="detailsLoading" class="py-24 text-center text-slate-500">
                            Loading provider details…
                        </div>

                        <div v-else class="space-y-6">
                            <section class="grid gap-5 lg:grid-cols-2">
                                <article class="provider-section">
                                    <h4 class="provider-section-title">Provider Information</h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Name" :value="selectedProvider.name" />
                                        <DetailRow label="Type" :value="formatType(selectedProvider.type)" />
                                        <DetailRow label="Priority" :value="`#${selectedProvider.priority}`" />
                                        <DetailRow label="Environment" :value="selectedProvider.config?.sandbox ? 'Sandbox' : 'Live'" />
                                        <DetailRow label="Base URL" :value="selectedProvider.config?.base_url || 'Not configured'" />
                                    </dl>
                                </article>

                                <article class="provider-section">
                                    <h4 class="provider-section-title">Usage and Health</h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow label="Virtual Accounts" :value="selectedProvider.virtual_accounts_count || 0" />
                                        <DetailRow label="Transactions" :value="selectedProvider.transactions_count || 0" />
                                        <DetailRow label="Health" :value="formatHealth(selectedProvider.health_status)" />
                                        <DetailRow label="Last Check" :value="formatDateTime(selectedProvider.last_health_check)" />
                                        <DetailRow
                                            label="Response Time"
                                            :value="selectedProvider.metadata?.last_response_time_ms
                                                ? `${selectedProvider.metadata.last_response_time_ms} ms`
                                                : 'Not tested'"
                                        />
                                    </dl>
                                </article>
                            </section>

                            <section class="provider-section">
                                <h4 class="provider-section-title">Credential Status</h4>

                                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <CredentialStatus label="API Key" :configured="selectedProvider.config?.has_api_key" />
                                    <CredentialStatus label="Secret Key" :configured="selectedProvider.config?.has_secret_key" />
                                    <CredentialStatus label="Password" :configured="selectedProvider.config?.has_password" />
                                    <CredentialStatus label="Webhook Secret" :configured="selectedProvider.config?.has_webhook_secret" />
                                </div>
                            </section>

                            <section class="provider-section">
                                <h4 class="provider-section-title">Supported Services</h4>

                                <div class="mt-5 flex flex-wrap gap-2">
                                    <span
                                        v-for="capability in capabilities(selectedProvider)"
                                        :key="capability"
                                        class="rounded-full bg-indigo-100 px-3 py-1.5 text-xs font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ capability }}
                                    </span>

                                    <span
                                        v-if="capabilities(selectedProvider).length === 0"
                                        class="text-sm text-slate-500"
                                    >
                                        No capabilities configured.
                                    </span>
                                </div>
                            </section>

                            <section class="provider-section">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h4 class="provider-section-title">Funding Charges</h4>
                                        <p class="mt-1 text-sm text-slate-500">Provider-specific wallet funding fee configuration.</p>
                                    </div>

                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-black"
                                        :class="selectedProvider.funding_charge?.is_enabled
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300'"
                                    >
                                        {{ selectedProvider.funding_charge?.is_enabled ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>

                                <dl class="mt-5 space-y-4">
                                    <DetailRow label="Charge Type" :value="fundingChargeTypeLabel(selectedProvider.funding_charge)" />
                                    <DetailRow label="Charge Value" :value="fundingChargeValueLabel(selectedProvider.funding_charge)" />
                                    <DetailRow label="Minimum Charge" :value="moneyOrNone(selectedProvider.funding_charge?.minimum_charge)" />
                                    <DetailRow label="Maximum Charge" :value="moneyOrNone(selectedProvider.funding_charge?.maximum_charge)" />
                                    <DetailRow label="Collection Mode" :value="selectedProvider.funding_charge?.collection_mode === 'deduct' ? 'Deduct from wallet credit' : 'Add to total payable'" />
                                </dl>
                            </section>
                        </div>
                    </div>

                    <footer class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7">
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                class="rounded-xl px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300"
                                @click="closeDetails"
                            >
                                Close
                            </button>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="button"
                                    class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-black text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                    :disabled="processing"
                                    @click="deleteProvider"
                                >
                                    Delete
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700"
                                    :disabled="processing"
                                    @click="testProvider(selectedProvider)"
                                >
                                    Test Connection
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-black text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-300"
                                    :disabled="processing"
                                    @click="toggleProvider"
                                >
                                    {{ selectedProvider.is_active ? 'Disable' : 'Enable' }}
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-black text-white hover:bg-indigo-700"
                                    @click="openEditModal"
                                >
                                    Edit Provider
                                </button>
                            </div>
                        </div>
                    </footer>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script>
import {
    computed,
    defineComponent,
    h,
    onBeforeUnmount,
    onMounted,
    ref,
} from 'vue';

import axios from 'axios';
import { verificationModal } from '../../services/verificationModal';

const FormField = defineComponent({
    name: 'FormField',

    inheritAttrs: false,

    props: {
        label: {
            type: String,
            required: true,
        },

        error: {
            type: String,
            default: '',
        },
    },

    setup(props, { slots, attrs }) {
        return () =>
            h(
                'label',
                {
                    ...attrs,
                    class: attrs.class,
                },
                [
                    h(
                        'span',
                        {
                            class:
                                'mb-2 block text-sm font-black text-slate-700 dark:text-slate-300',
                        },
                        props.label
                    ),

                    slots.default?.(),

                    props.error
                        ? h(
                              'span',
                              {
                                  class:
                                      'mt-1.5 block text-sm text-red-600',
                              },
                              props.error
                          )
                        : null,
                ]
            );
    },
});

const ToggleField = defineComponent({
    name: 'ToggleField',

    props: {
        modelValue: {
            type: Boolean,
            default: false,
        },

        title: {
            type: String,
            required: true,
        },

        description: {
            type: String,
            default: '',
        },
    },

    emits: ['update:modelValue'],

    setup(props, { emit }) {
        return () =>
            h(
                'button',
                {
                    type: 'button',
                    class: [
                        'flex w-full items-center justify-between gap-4 rounded-2xl border p-4 text-left transition',
                        props.modelValue
                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                            : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-950',
                    ],
                    onClick: () =>
                        emit(
                            'update:modelValue',
                            !props.modelValue
                        ),
                },
                [
                    h('span', {}, [
                        h(
                            'span',
                            {
                                class:
                                    'block text-sm font-black',
                            },
                            props.title
                        ),

                        h(
                            'span',
                            {
                                class:
                                    'mt-1 block text-xs leading-5 text-slate-500',
                            },
                            props.description
                        ),
                    ]),

                    h(
                        'span',
                        {
                            class: [
                                'relative h-6 w-11 shrink-0 rounded-full transition',
                                props.modelValue
                                    ? 'bg-indigo-600'
                                    : 'bg-slate-300 dark:bg-slate-700',
                            ],
                        },
                        [
                            h('span', {
                                class: [
                                    'absolute top-1 h-4 w-4 rounded-full bg-white transition',
                                    props.modelValue
                                        ? 'left-6'
                                        : 'left-1',
                                ],
                            }),
                        ]
                    ),
                ]
            );
    },
});

const DetailRow = defineComponent({
    name: 'DetailRow',

    props: {
        label: {
            type: String,
            required: true,
        },

        value: {
            type: [String, Number],
            default: 'N/A',
        },
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'flex items-start justify-between gap-4 border-b border-slate-100 pb-3 last:border-0 dark:border-slate-800',
                },
                [
                    h(
                        'dt',
                        {
                            class: 'text-sm text-slate-500',
                        },
                        props.label
                    ),

                    h(
                        'dd',
                        {
                            class:
                                'max-w-[65%] break-words text-right text-sm font-bold',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

const CredentialStatus = defineComponent({
    name: 'CredentialStatus',

    props: {
        label: {
            type: String,
            required: true,
        },

        configured: {
            type: Boolean,
            default: false,
        },
    },

    setup(props) {
        return () =>
            h(
                'div',
                {
                    class:
                        'flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
                },
                [
                    h(
                        'span',
                        {
                            class: 'text-sm font-black',
                        },
                        props.label
                    ),

                    h(
                        'span',
                        {
                            class: [
                                'rounded-full px-2.5 py-1 text-xs font-black',
                                props.configured
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                            ],
                        },
                        props.configured
                            ? 'Configured'
                            : 'Not configured'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminProviders',

    components: {
        FormField,
        ToggleField,
        DetailRow,
        CredentialStatus,
    },

    setup() {
        const loading = ref(false);
        const saving = ref(false);
        const processing = ref(false);
        const detailsLoading = ref(false);

        const providers = ref([]);
        const selectedProvider = ref({});

        const formModalOpen = ref(false);
        const detailsModalOpen = ref(false);
        const editingProvider = ref(false);

        const successMessage = ref('');
        const errorMessage = ref('');
        const formError = ref('');
        const fieldErrors = ref({});

        const stats = ref({
            total: 0,
            active: 0,
            inactive: 0,
            healthy: 0,
            degraded: 0,
            unhealthy: 0,
            funding_charges_enabled: 0,
        });

        const filters = ref({
            search: '',
            type: '',
            status: '',
            health_status: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const emptyForm = () => ({
            name: '',
            slug: '',
            type: 'virtual_account',
            is_active: true,
            priority: 1,
            base_url: '',
            api_key: '',
            secret_key: '',
            username: '',
            password: '',
            webhook_secret: '',
            sandbox: false,
            supports_virtual_account: true,
            supports_payout: false,
            supports_collection: true,
            supports_transfer: false,
            supports_webhook: true,
            timeout: 15,
            funding_charge_enabled: false,
            funding_charge_type: 'percentage',
            funding_percentage: 0,
            funding_fixed_amount: 0,
            funding_maximum_charge: '',
            funding_minimum_charge: '',
            funding_collection_mode: 'add_on',
        });

        const form = ref(emptyForm());
        const fundingPreviewAmount = ref('');
        const fundingPreviewResult = ref(null);
        const fundingPreviewError = ref('');
        const previewingFunding = ref(false);

        const summaries = computed(() => [
            {
                label: 'Total Providers',
                value: stats.value.total,
                icon: '◎',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Active Providers',
                value: stats.value.active,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Healthy Providers',
                value: stats.value.healthy,
                icon: '♥',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'Funding Charges Enabled',
                value: stats.value.funding_charges_enabled,
                icon: '₦',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
        ]);

        const buildParams = (page = 1) => {
            const params = {
                page,
                per_page: 12,
            };

            Object.entries(filters.value).forEach(
                ([key, value]) => {
                    if (String(value || '').trim()) {
                        params[key] = String(value).trim();
                    }
                }
            );

            return params;
        };

        const fetchProviders = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/providers',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                providers.value = Array.isArray(paginator.data)
                    ? paginator.data
                    : [];

                pagination.value = {
                    current_page:
                        Number(paginator.current_page || 1),

                    last_page:
                        Number(paginator.last_page || 1),

                    from:
                        Number(paginator.from || 0),

                    to:
                        Number(paginator.to || 0),

                    total:
                        Number(paginator.total || 0),
                };
            } catch (error) {
                providers.value = [];

                errorMessage.value =
                    error.response?.data?.message ||
                    'The providers could not be loaded.';
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get(
                    '/api/v1/admin/providers/stats'
                );

                stats.value = {
                    ...stats.value,
                    ...(response.data?.data || {}),
                };
            } catch (error) {
                console.error(
                    'Provider statistics failed:',
                    error.response?.data || error
                );
            }
        };

        const refreshPage = async () => {
            await Promise.all([
                fetchProviders(pagination.value.current_page),
                fetchStats(),
            ]);
        };

        const applyFilters = async () => {
            await fetchProviders(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                type: '',
                status: '',
                health_status: '',
            };

            await fetchProviders(1);
        };

        const handleNameInput = () => {
            if (!editingProvider.value) {
                form.value.slug = form.value.name
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        };

        const openCreateModal = () => {
            editingProvider.value = false;
            form.value = emptyForm();
            formError.value = '';
            fieldErrors.value = {};
            fundingPreviewAmount.value = '';
            fundingPreviewResult.value = null;
            fundingPreviewError.value = '';
            formModalOpen.value = true;
            document.body.style.overflow = 'hidden';
        };

        const openEditModal = () => {
            const provider = selectedProvider.value;
            const metadata = provider.metadata || {};
            const config = provider.config || {};
            const fundingCharge = provider.funding_charge || {};

            editingProvider.value = true;

            form.value = {
                name: provider.name || '',
                slug: provider.slug || '',
                type: provider.type || 'virtual_account',
                is_active: Boolean(provider.is_active),
                priority: Number(provider.priority || 0),
                base_url: config.base_url || '',
                api_key: '',
                secret_key: '',
                username: config.username || '',
                password: '',
                webhook_secret: '',
                sandbox: Boolean(config.sandbox),
                supports_virtual_account: Boolean(
                    metadata.supports_virtual_account
                ),
                supports_payout: Boolean(
                    metadata.supports_payout
                ),
                supports_collection: Boolean(
                    metadata.supports_collection
                ),
                supports_transfer: Boolean(
                    metadata.supports_transfer
                ),
                supports_webhook: Boolean(
                    metadata.supports_webhook
                ),
                timeout: Number(metadata.timeout || 15),
                funding_charge_enabled: Boolean(fundingCharge.is_enabled),
                funding_charge_type: fundingCharge.charge_type || 'percentage',
                funding_percentage: Number(fundingCharge.percentage || 0),
                funding_fixed_amount: Number(fundingCharge.fixed_amount || 0),
                funding_maximum_charge: fundingCharge.maximum_charge ?? '',
                funding_minimum_charge: fundingCharge.minimum_charge ?? '',
                funding_collection_mode: fundingCharge.collection_mode || 'add_on',
            };

            formError.value = '';
            fieldErrors.value = {};
            detailsModalOpen.value = false;
            formModalOpen.value = true;
        };

        const closeFormModal = () => {
            if (saving.value) {
                return;
            }

            formModalOpen.value = false;
            editingProvider.value = false;
            form.value = emptyForm();
            formError.value = '';
            fieldErrors.value = {};
            fundingPreviewAmount.value = '';
            fundingPreviewResult.value = null;
            fundingPreviewError.value = '';
            document.body.style.overflow = '';
        };

        const saveProvider = async () => {
            saving.value = true;
            formError.value = '';
            fieldErrors.value = {};

            try {
                const payload = {
                    ...form.value,
                    priority: Number(form.value.priority),
                    timeout: Number(form.value.timeout),
                    funding_percentage: Number(form.value.funding_percentage || 0),
                    funding_fixed_amount: Number(form.value.funding_fixed_amount || 0),
                    funding_maximum_charge: nullableNumber(form.value.funding_maximum_charge),
                    funding_minimum_charge: nullableNumber(form.value.funding_minimum_charge),
                };

                const response = editingProvider.value
                    ? await axios.put(
                          `/api/v1/admin/providers/${selectedProvider.value.id}`,
                          payload
                      )
                    : await axios.post(
                          '/api/v1/admin/providers',
                          payload
                      );

                successMessage.value =
                    response.data?.message ||
                    'Provider saved successfully.';

                closeFormModal();

                await Promise.all([
                    fetchProviders(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                const response = error.response?.data;

                formError.value =
                    response?.message ||
                    'The provider could not be saved.';

                const errors = response?.errors || {};
                const normalized = {};

                Object.entries(errors).forEach(
                    ([field, messages]) => {
                        normalized[field] = Array.isArray(messages)
                            ? messages[0]
                            : String(messages);
                    }
                );

                fieldErrors.value = normalized;
            } finally {
                saving.value = false;
            }
        };

        const openDetails = async (provider) => {
            selectedProvider.value = provider;
            detailsModalOpen.value = true;
            detailsLoading.value = true;
            errorMessage.value = '';
            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/providers/${provider.id}`
                );

                selectedProvider.value =
                    response.data?.data || provider;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The provider details could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeDetails = () => {
            if (processing.value) {
                return;
            }

            detailsModalOpen.value = false;
            selectedProvider.value = {};
            document.body.style.overflow = '';
        };

        const toggleProvider = async () => {
            if (!selectedProvider.value?.id) {
                return;
            }

            processing.value = true;
            errorMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/providers/${selectedProvider.value.id}/toggle`
                );

                selectedProvider.value =
                    response.data?.data ||
                    selectedProvider.value;

                successMessage.value =
                    response.data?.message ||
                    'Provider status updated.';

                await Promise.all([
                    fetchProviders(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The provider status could not be updated.';
            } finally {
                processing.value = false;
            }
        };

        const testProvider = async (provider) => {
            if (!provider?.id || processing.value) {
                return;
            }

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/providers/${provider.id}/test`
                );

                const responseTime =
                    response.data?.data?.response_time_ms;

                successMessage.value =
                    response.data?.message +
                    (responseTime
                        ? ` Response time: ${responseTime} ms.`
                        : '');

                if (
                    selectedProvider.value?.id === provider.id
                ) {
                    const detailResponse = await axios.get(
                        `/api/v1/admin/providers/${provider.id}`
                    );

                    selectedProvider.value =
                        detailResponse.data?.data ||
                        selectedProvider.value;
                }

                await Promise.all([
                    fetchProviders(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The provider connection test failed.';

                await Promise.all([
                    fetchProviders(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } finally {
                processing.value = false;
            }
        };

        const deleteProvider = async () => {
            const provider = selectedProvider.value;

            if (!provider?.id || processing.value) {
                return;
            }

            const confirmed = await verificationModal.confirm({
                title: 'Delete provider?',
                message: `Delete ${provider.name}? This is only allowed when the provider has no virtual accounts or transactions.`,
                confirmText: 'Delete provider',
                danger: true,
            });
            if (!confirmed) return;

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.delete(
                    `/api/v1/admin/providers/${provider.id}`
                );

                successMessage.value =
                    response.data?.message ||
                    'Provider deleted successfully.';

                closeDetails();

                await Promise.all([
                    fetchProviders(1),
                    fetchStats(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The provider could not be deleted.';
            } finally {
                processing.value = false;
            }
        };

        const providerInitials = (provider) => {
            return String(provider?.name || 'P')
                .split(' ')
                .filter(Boolean)
                .map((word) => word.charAt(0))
                .join('')
                .slice(0, 2)
                .toUpperCase();
        };

        const formatType = (type) => {
            return type === 'payout'
                ? 'Payout'
                : 'Virtual Account';
        };

        const formatHealth = (health) => {
            const labels = {
                healthy: 'Healthy',
                degraded: 'Degraded',
                unhealthy: 'Unhealthy',
            };

            return labels[health] || 'Unknown';
        };

        const healthClass = (health) => {
            if (health === 'healthy') {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (health === 'unhealthy') {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const capabilities = (provider) => {
            const metadata = provider?.metadata || {};
            const values = [];

            if (metadata.supports_virtual_account) {
                values.push('Virtual Accounts');
            }

            if (metadata.supports_payout) {
                values.push('Payouts');
            }

            if (metadata.supports_collection) {
                values.push('Collections');
            }

            if (metadata.supports_transfer) {
                values.push('Transfers');
            }

            if (metadata.supports_webhook) {
                values.push('Webhooks');
            }

            return values;
        };

        const formatDateTime = (value) => {
            if (!value) {
                return 'Not checked';
            }

            return new Date(value).toLocaleString(
                'en-NG',
                {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }
            );
        };

        const calculateLocalFundingPreview = async () => {
            fundingPreviewError.value = '';
            fundingPreviewResult.value = null;

            const amount = Number(fundingPreviewAmount.value || 0);

            if (amount <= 0) {
                fundingPreviewError.value = 'Enter a valid funding amount.';
                return;
            }

            previewingFunding.value = true;

            try {
                if (editingProvider.value && selectedProvider.value?.id) {
                    const response = await axios.post(
                        `/api/v1/admin/providers/${selectedProvider.value.id}/funding-charge/preview`,
                        { amount }
                    );

                    fundingPreviewResult.value = response.data?.data || null;
                    return;
                }

                const charge = calculateChargeFromForm(amount);
                const addOn = form.value.funding_collection_mode === 'add_on';

                fundingPreviewResult.value = {
                    amount,
                    charge,
                    total_payable: addOn ? amount + charge : amount,
                    wallet_credit: addOn ? amount : Math.max(amount - charge, 0),
                };
            } catch (error) {
                fundingPreviewError.value =
                    error.response?.data?.message ||
                    'The funding charge preview could not be calculated.';
            } finally {
                previewingFunding.value = false;
            }
        };

        const calculateChargeFromForm = (amount) => {
            if (!form.value.funding_charge_enabled) {
                return 0;
            }

            let charge = form.value.funding_charge_type === 'fixed'
                ? Number(form.value.funding_fixed_amount || 0)
                : amount * (Number(form.value.funding_percentage || 0) / 100);

            const maximum = nullableNumber(form.value.funding_maximum_charge);
            const minimum = nullableNumber(form.value.funding_minimum_charge);

            if (maximum !== null) charge = Math.min(charge, maximum);
            if (minimum !== null) charge = Math.max(charge, minimum);

            return Math.max(Number(charge.toFixed(2)), 0);
        };

        const fundingChargeDescription = (charge) => {
            if (!charge?.is_enabled) return 'No funding charge';

            if (charge.charge_type === 'fixed') {
                return `₦${formatCurrency(charge.fixed_amount)} fixed`;
            }

            const cap = charge.maximum_charge !== null && charge.maximum_charge !== undefined
                ? ` · Max ₦${formatCurrency(charge.maximum_charge)}`
                : '';

            return `${Number(charge.percentage || 0)}%${cap}`;
        };

        const fundingChargeTypeLabel = (charge) => {
            if (!charge?.is_enabled) return 'Disabled';
            return charge.charge_type === 'fixed' ? 'Fixed Amount' : 'Percentage';
        };

        const fundingChargeValueLabel = (charge) => {
            if (!charge?.is_enabled) return 'No charge';
            return charge.charge_type === 'fixed'
                ? `₦${formatCurrency(charge.fixed_amount)}`
                : `${Number(charge.percentage || 0)}%`;
        };

        const moneyOrNone = (value) => {
            return value === null || value === undefined || value === ''
                ? 'None'
                : `₦${formatCurrency(value)}`;
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        };

        const nullableNumber = (value) => {
            return value === '' || value === null || value === undefined
                ? null
                : Number(value);
        };

        const handleEscape = (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            if (formModalOpen.value) {
                closeFormModal();
                return;
            }

            if (detailsModalOpen.value) {
                closeDetails();
            }
        };

        onMounted(async () => {
            window.addEventListener(
                'keydown',
                handleEscape
            );

            await Promise.all([
                fetchProviders(),
                fetchStats(),
            ]);
        });

        onBeforeUnmount(() => {
            window.removeEventListener(
                'keydown',
                handleEscape
            );

            document.body.style.overflow = '';
        });

        return {
            loading,
            saving,
            processing,
            detailsLoading,
            providers,
            selectedProvider,
            formModalOpen,
            detailsModalOpen,
            editingProvider,
            successMessage,
            errorMessage,
            formError,
            fieldErrors,
            stats,
            filters,
            pagination,
            form,
            fundingPreviewAmount,
            fundingPreviewResult,
            fundingPreviewError,
            previewingFunding,
            summaries,
            fetchProviders,
            refreshPage,
            applyFilters,
            resetFilters,
            handleNameInput,
            openCreateModal,
            openEditModal,
            closeFormModal,
            saveProvider,
            openDetails,
            closeDetails,
            toggleProvider,
            testProvider,
            deleteProvider,
            providerInitials,
            formatType,
            formatHealth,
            healthClass,
            capabilities,
            calculateLocalFundingPreview,
            fundingChargeDescription,
            fundingChargeTypeLabel,
            fundingChargeValueLabel,
            moneyOrNone,
            formatCurrency,
            formatDateTime,
        };
    },
};
</script>

<style scoped>
.provider-input {
    width: 100%;
    border-radius: 1rem;
    border: 1px solid rgb(226 232 240);
    background: white;
    padding: 0.75rem 1rem;
    color: rgb(15 23 42);
    outline: none;
    transition:
        border-color 150ms ease,
        box-shadow 150ms ease;
}

.provider-input:focus {
    border-color: rgb(99 102 241);
    box-shadow: 0 0 0 4px rgb(99 102 241 / 0.1);
}

.provider-section {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
}

.provider-section-title {
    font-size: 1rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .provider-input {
    border-color: rgb(51 65 85);
    background: rgb(2 6 23);
    color: white;
}

:global(.dark) .provider-section {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .provider-section-title {
    color: white;
}
</style>