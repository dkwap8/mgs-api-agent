<template>
    <div class="space-y-6">
        <!-- Heading -->
        <section
            class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"
        >
            <div>
                <p
                    class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400"
                >
                    Account management
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Merchants
                </h2>

                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    Review merchant accounts, monitor wallets, inspect their
                    businesses and control live platform access.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                :disabled="loading"
                @click="fetchMerchants(pagination.current_page)"
            >
                <span>↻</span>

                {{ loading ? 'Refreshing…' : 'Refresh merchants' }}
            </button>
        </section>

        <!-- Summary -->
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
                    <span
                        class="block text-2xl font-black text-slate-950 dark:text-white"
                    >
                        {{ summary.value }}
                    </span>

                    <span
                        class="mt-1 block text-sm font-semibold text-slate-500"
                    >
                        {{ summary.label }}
                    </span>
                </span>
            </article>
        </section>

        <!-- Filters -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
        >
            <form
                class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_190px_auto]"
                @submit.prevent="applyFilters"
            >
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
                    >
                        ⌕
                    </span>

                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search merchant, owner, email, phone or business code..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                >
                    <option value="">
                        All statuses
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="submitted">
                        Submitted
                    </option>

                    <option value="under_review">
                        Under review
                    </option>

                    <option value="approved">
                        Approved
                    </option>

                    <option value="verified">
                        Verified
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="rejected">
                        Rejected
                    </option>

                    <option value="suspended">
                        Suspended
                    </option>
                </select>

                <select
                    v-model="filters.live_access"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                >
                    <option value="">
                        All access levels
                    </option>

                    <option value="yes">
                        Live access enabled
                    </option>

                    <option value="no">
                        Live access disabled
                    </option>
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
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        @click="resetFilters"
                    >
                        Clear
                    </button>
                </div>
            </form>
        </section>

        <!-- Feedback -->
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
        <section
            v-if="loading"
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="item in 6"
                :key="item"
                class="h-[360px] animate-pulse rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="h-12 w-12 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-5 h-5 w-2/3 rounded bg-slate-200 dark:bg-slate-800"></div>
                <div class="mt-3 h-4 w-1/2 rounded bg-slate-200 dark:bg-slate-800"></div>
            </article>
        </section>

        <!-- Empty -->
        <section
            v-else-if="merchants.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <span
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
            >
                🏪
            </span>

            <h3 class="mt-5 text-xl font-black">
                No merchants found
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                No merchant account matches the selected filters.
            </p>

            <button
                type="button"
                class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                @click="resetFilters"
            >
                Clear filters
            </button>
        </section>

        <!-- Merchant cards -->
        <section
            v-else
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="merchant in merchants"
                :key="merchant.id"
                class="flex min-h-[390px] flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ merchantInitials(merchant) }}
                    </span>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-black"
                        :class="statusClass(merchant.verification_status)"
                    >
                        {{ formatStatus(merchant.verification_status) }}
                    </span>
                </div>

                <div class="mt-5">
                    <h3
                        class="line-clamp-2 text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ merchant.business_name || 'Unnamed Merchant' }}
                    </h3>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ merchant.user?.name || 'Account owner unavailable' }}
                    </p>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Email
                        </dt>

                        <dd class="max-w-[65%] truncate text-right font-semibold">
                            {{
                                merchant.business_email ||
                                merchant.user?.email ||
                                'N/A'
                            }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Phone
                        </dt>

                        <dd class="font-semibold">
                            {{
                                merchant.business_phone ||
                                merchant.user?.phone ||
                                'N/A'
                            }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Businesses
                        </dt>

                        <dd class="font-semibold">
                            {{
                                merchant.businesses_count ??
                                merchant.businesses?.length ??
                                0
                            }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Wallet
                        </dt>

                        <dd class="font-black text-slate-950 dark:text-white">
                            ₦{{ formatCurrency(walletBalance(merchant)) }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Live Access
                        </dt>

                        <dd
                            class="font-bold"
                            :class="
                                merchant.live_access
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-amber-600 dark:text-amber-400'
                            "
                        >
                            {{
                                merchant.live_access
                                    ? 'Enabled'
                                    : 'Disabled'
                            }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto pt-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
                        @click="openMerchant(merchant)"
                    >
                        View merchant
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
                of {{ pagination.total || 0 }} merchants
            </p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchMerchants(pagination.current_page - 1)"
                >
                    Previous
                </button>

                <span class="px-3 text-sm font-bold">
                    {{ pagination.current_page }}
                    /
                    {{ pagination.last_page }}
                </span>

                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:opacity-40 dark:border-slate-700"
                    :disabled="
                        pagination.current_page >=
                        pagination.last_page
                    "
                    @click="fetchMerchants(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>

        <!-- Merchant modal -->
        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeModal"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <header
                        class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="
                                        statusClass(
                                            selectedMerchant.verification_status
                                        )
                                    "
                                >
                                    {{
                                        formatStatus(
                                            selectedMerchant.verification_status
                                        )
                                    }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="
                                        selectedMerchant.live_access
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                    "
                                >
                                    {{
                                        selectedMerchant.live_access
                                            ? 'Live access'
                                            : 'Test access'
                                    }}
                                </span>
                            </div>

                            <h3 class="mt-3 truncate text-2xl font-black">
                                {{
                                    selectedMerchant.business_name ||
                                    'Unnamed Merchant'
                                }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Merchant ID: {{ selectedMerchant.id }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeModal"
                        >
                            ✕
                        </button>
                    </header>

                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div
                            v-if="detailsLoading"
                            class="py-24 text-center text-slate-500"
                        >
                            Loading merchant details…
                        </div>

                        <div v-else class="space-y-6">
                            <!-- Account and wallet -->
                            <section class="grid gap-5 lg:grid-cols-2">
                                <article class="detail-card">
                                    <h4 class="detail-heading">
                                        Account Owner
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Full Name"
                                            :value="
                                                selectedMerchant.user?.name
                                            "
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="
                                                selectedMerchant.user?.email
                                            "
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="
                                                selectedMerchant.user?.phone
                                            "
                                        />

                                        <DetailRow
                                            label="Account Status"
                                            :value="
                                                selectedMerchant.user?.status
                                            "
                                        />

                                        <DetailRow
                                            label="Joined"
                                            :value="
                                                formatDateTime(
                                                    selectedMerchant.created_at
                                                )
                                            "
                                        />
                                    </dl>
                                </article>

                                <article class="detail-card">
                                    <h4 class="detail-heading">
                                        Wallet Summary
                                    </h4>

                                    <div class="mt-5 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 p-5 text-white">
                                        <p class="text-sm text-indigo-100">
                                            Available Balance
                                        </p>

                                        <p class="mt-2 text-3xl font-black">
                                            ₦{{
                                                formatCurrency(
                                                    selectedMerchant.wallet
                                                        ?.available_balance
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Ledger Balance"
                                            :value="
                                                `₦${formatCurrency(
                                                    selectedMerchant.wallet
                                                        ?.ledger_balance
                                                )}`
                                            "
                                        />

                                        <DetailRow
                                            label="Reserved Balance"
                                            :value="
                                                `₦${formatCurrency(
                                                    selectedMerchant.wallet
                                                        ?.reserved_balance
                                                )}`
                                            "
                                        />

                                        <DetailRow
                                            label="Currency"
                                            :value="
                                                selectedMerchant.wallet
                                                    ?.currency || 'NGN'
                                            "
                                        />

                                        <DetailRow
                                            label="Wallet Status"
                                            :value="
                                                selectedMerchant.wallet
                                                    ?.status || 'N/A'
                                            "
                                        />
                                    </dl>
                                </article>
                            </section>

                            <!-- Contact -->
                            <article class="detail-card">
                                <h4 class="detail-heading">
                                    Merchant Information
                                </h4>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <DetailBox
                                        label="Business Name"
                                        :value="
                                            selectedMerchant.business_name
                                        "
                                    />

                                    <DetailBox
                                        label="Business Email"
                                        :value="
                                            selectedMerchant.business_email
                                        "
                                    />

                                    <DetailBox
                                        label="Business Phone"
                                        :value="
                                            selectedMerchant.business_phone
                                        "
                                    />

                                    <DetailBox
                                        label="CAC Number"
                                        :value="
                                            selectedMerchant.cac_number ||
                                            'Not provided'
                                        "
                                    />

                                    <DetailBox
                                        class="md:col-span-2"
                                        label="Business Address"
                                        :value="
                                            selectedMerchant.business_address ||
                                            'Not provided'
                                        "
                                    />
                                </div>
                            </article>

                            <!-- Businesses -->
                            <article class="detail-card">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="detail-heading">
                                            Businesses
                                        </h4>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Businesses under this merchant account.
                                        </p>
                                    </div>

                                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                        {{
                                            selectedMerchant.businesses
                                                ?.length || 0
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        selectedMerchant.businesses?.length
                                    "
                                    class="mt-5 grid gap-4 md:grid-cols-2"
                                >
                                    <div
                                        v-for="business in selectedMerchant.businesses"
                                        :key="business.id"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="truncate font-black">
                                                    {{ business.name }}
                                                </p>

                                                <p class="mt-1 font-mono text-xs text-slate-500">
                                                    {{
                                                        business.business_code ||
                                                        'No code'
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="
                                                    statusClass(
                                                        business.verification_status
                                                    )
                                                "
                                            >
                                                {{
                                                    formatStatus(
                                                        business.verification_status
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="mt-5 rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500 dark:bg-slate-950"
                                >
                                    This merchant does not have any businesses.
                                </p>
                            </article>
                        </div>
                    </div>

                    <!-- Actions -->
                    <footer
                        class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                class="rounded-xl px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="processing"
                                @click="closeModal"
                            >
                                Close
                            </button>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    v-if="
                                        selectedMerchant.verification_status !==
                                        'rejected'
                                    "
                                    type="button"
                                    class="rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-black text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                    :disabled="processing"
                                    @click="performAction('reject')"
                                >
                                    Reject
                                </button>

                                <button
                                    v-if="
                                        selectedMerchant.verification_status !==
                                        'suspended'
                                    "
                                    type="button"
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm font-black text-amber-700 hover:bg-amber-100 disabled:opacity-50 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
                                    :disabled="processing"
                                    @click="performAction('suspend')"
                                >
                                    Suspend
                                </button>

                                <button
                                    v-if="
                                        ![
                                            'approved',
                                            'verified',
                                            'active',
                                        ].includes(
                                            selectedMerchant.verification_status
                                        )
                                    "
                                    type="button"
                                    class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 disabled:opacity-50"
                                    :disabled="processing"
                                    @click="performAction('approve')"
                                >
                                    {{
                                        processing
                                            ? 'Processing…'
                                            : 'Approve merchant'
                                    }}
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

const DetailBox = defineComponent({
    name: 'DetailBox',

    inheritAttrs: false,

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

    setup(props, { attrs }) {
        return () =>
            h(
                'div',
                {
                    ...attrs,
                    class: [
                        'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
                        attrs.class,
                    ],
                },
                [
                    h(
                        'p',
                        {
                            class:
                                'text-[11px] font-black uppercase tracking-wider text-slate-400',
                        },
                        props.label
                    ),

                    h(
                        'p',
                        {
                            class:
                                'mt-2 break-words text-sm font-bold',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminMerchants',

    components: {
        DetailRow,
        DetailBox,
    },

    setup() {
        const loading = ref(false);
        const detailsLoading = ref(false);
        const processing = ref(false);
        const modalOpen = ref(false);

        const merchants = ref([]);
        const selectedMerchant = ref({});

        const successMessage = ref('');
        const errorMessage = ref('');

        const filters = ref({
            search: '',
            status: '',
            live_access: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const summaries = computed(() => {
            const approved = merchants.value.filter((merchant) =>
                [
                    'approved',
                    'verified',
                    'active',
                ].includes(merchant.verification_status)
            ).length;

            const pending = merchants.value.filter((merchant) =>
                [
                    'pending',
                    'submitted',
                    'under_review',
                ].includes(merchant.verification_status)
            ).length;

            const suspended = merchants.value.filter(
                (merchant) =>
                    merchant.verification_status === 'suspended'
            ).length;

            return [
                {
                    label: 'Total Merchants',
                    value: pagination.value.total,
                    icon: '🏪',
                    iconClass:
                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                },
                {
                    label: 'Approved on Page',
                    value: approved,
                    icon: '✓',
                    iconClass:
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                },
                {
                    label: 'Pending on Page',
                    value: pending,
                    icon: '◷',
                    iconClass:
                        'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                },
                {
                    label: 'Suspended on Page',
                    value: suspended,
                    icon: '!',
                    iconClass:
                        'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
                },
            ];
        });

        const buildParams = (page) => {
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

        const fetchMerchants = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/merchants',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                merchants.value = Array.isArray(paginator.data)
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
                errorMessage.value =
                    error.response?.data?.message ||
                    'The merchants could not be loaded.';

                merchants.value = [];
            } finally {
                loading.value = false;
            }
        };

        const applyFilters = async () => {
            await fetchMerchants(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                live_access: '',
            };

            await fetchMerchants(1);
        };

        const openMerchant = async (merchant) => {
            selectedMerchant.value = merchant;
            modalOpen.value = true;
            detailsLoading.value = true;
            successMessage.value = '';
            errorMessage.value = '';

            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/merchants/${merchant.id}`
                );

                selectedMerchant.value =
                    response.data?.data || merchant;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The merchant details could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeModal = () => {
            if (processing.value) {
                return;
            }

            modalOpen.value = false;
            selectedMerchant.value = {};
            document.body.style.overflow = '';
        };

        const performAction = async (action) => {
            if (
                !selectedMerchant.value?.id ||
                processing.value
            ) {
                return;
            }

            const merchantName =
                selectedMerchant.value.business_name ||
                'this merchant';

            const messages = {
                approve:
                    `Approve ${merchantName}?`,

                reject:
                    `Reject ${merchantName}?`,

                suspend:
                    `Suspend ${merchantName} and disable live access?`,
            };

            const confirmed = await verificationModal.confirm({
                title: action === 'suspend' ? 'Suspend merchant?' : 'Confirm merchant action',
                message: messages[action],
                confirmText: action === 'suspend' ? 'Suspend merchant' : 'Continue',
                danger: action === 'suspend',
            });
            if (!confirmed) return;

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/merchants/${selectedMerchant.value.id}/${action}`
                );

                successMessage.value =
                    response.data?.message ||
                    'Merchant updated successfully.';

                const updatedMerchant =
                    response.data?.data || {};

                selectedMerchant.value = {
                    ...selectedMerchant.value,
                    ...updatedMerchant,
                };

                await fetchMerchants(
                    pagination.value.current_page
                );
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The merchant could not be updated.';
            } finally {
                processing.value = false;
            }
        };

        const merchantInitials = (merchant) => {
            return (
                merchant.business_name ||
                merchant.user?.name ||
                'M'
            )
                .split(' ')
                .filter(Boolean)
                .map((word) => word.charAt(0))
                .join('')
                .slice(0, 2)
                .toUpperCase();
        };

        const walletBalance = (merchant) => {
            return Number(
                merchant.wallet?.available_balance ||
                merchant.wallet?.balance ||
                0
            );
        };

        const formatCurrency = (value) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value || 0));
        };

        const formatDateTime = (value) => {
            if (!value) {
                return 'N/A';
            }

            return new Date(value).toLocaleString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        };

        const formatStatus = (status) => {
            const labels = {
                pending: 'Pending',
                submitted: 'Submitted',
                under_review: 'Under Review',
                approved: 'Approved',
                verified: 'Verified',
                active: 'Active',
                rejected: 'Rejected',
                suspended: 'Suspended',
            };

            return labels[status] || status || 'Pending';
        };

        const statusClass = (status) => {
            if (
                [
                    'approved',
                    'verified',
                    'active',
                ].includes(status)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (
                [
                    'pending',
                    'submitted',
                    'under_review',
                ].includes(status)
            ) {
                return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
            }

            if (
                [
                    'rejected',
                    'suspended',
                ].includes(status)
            ) {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
        };

        const handleKeydown = (event) => {
            if (
                event.key === 'Escape' &&
                modalOpen.value &&
                !processing.value
            ) {
                closeModal();
            }
        };

        onMounted(() => {
            window.addEventListener(
                'keydown',
                handleKeydown
            );

            fetchMerchants();
        });

        onBeforeUnmount(() => {
            window.removeEventListener(
                'keydown',
                handleKeydown
            );

            document.body.style.overflow = '';
        });

        return {
            loading,
            detailsLoading,
            processing,
            modalOpen,
            merchants,
            selectedMerchant,
            successMessage,
            errorMessage,
            filters,
            pagination,
            summaries,
            fetchMerchants,
            applyFilters,
            resetFilters,
            openMerchant,
            closeModal,
            performAction,
            merchantInitials,
            walletBalance,
            formatCurrency,
            formatDateTime,
            formatStatus,
            statusClass,
        };
    },
};
</script>

<style scoped>
.detail-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
}

.detail-heading {
    font-size: 1rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .detail-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .detail-heading {
    color: white;
}
</style>