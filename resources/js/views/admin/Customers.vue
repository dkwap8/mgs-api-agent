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
                    Customer management
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    Customers
                </h2>

                <p
                    class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                >
                    View customers across every merchant, inspect KYC status,
                    review virtual accounts and control customer access.
                </p>
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                :disabled="loading"
                @click="refreshPage"
            >
                <span>↻</span>

                {{ loading ? 'Refreshing…' : 'Refresh customers' }}
            </button>
        </section>

        <!-- Summary cards -->
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
                class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_220px_220px_auto]"
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
                        placeholder="Search name, email, phone or merchant..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm outline-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950 dark:focus:bg-slate-950"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                >
                    <option value="">
                        All account statuses
                    </option>

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
                        Inactive
                    </option>

                    <option value="blocked">
                        Blocked
                    </option>
                </select>

                <select
                    v-model="filters.kyc_status"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-950"
                >
                    <option value="">
                        All KYC statuses
                    </option>

                    <option value="pending">
                        Pending KYC
                    </option>

                    <option value="verified">
                        Verified KYC
                    </option>

                    <option value="rejected">
                        Rejected KYC
                    </option>
                </select>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white transition hover:bg-indigo-700"
                    >
                        Apply
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
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
                class="h-[350px] animate-pulse rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="h-12 w-12 rounded-full bg-slate-200 dark:bg-slate-800"
                ></div>

                <div
                    class="mt-5 h-5 w-2/3 rounded bg-slate-200 dark:bg-slate-800"
                ></div>

                <div
                    class="mt-3 h-4 w-1/2 rounded bg-slate-200 dark:bg-slate-800"
                ></div>
            </article>
        </section>

        <!-- Empty -->
        <section
            v-else-if="customers.length === 0"
            class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-20 text-center dark:border-slate-700 dark:bg-slate-900"
        >
            <span
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-3xl dark:bg-indigo-950"
            >
                👥
            </span>

            <h3 class="mt-5 text-xl font-black">
                No customers found
            </h3>

            <p class="mt-2 text-sm text-slate-500">
                No customer matches the selected filters.
            </p>

            <button
                type="button"
                class="mt-5 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                @click="resetFilters"
            >
                Clear filters
            </button>
        </section>

        <!-- Customer cards -->
        <section
            v-else
            class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="customer in customers"
                :key="customer.id"
                class="flex min-h-[370px] flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                    >
                        {{ customerInitials(customer) }}
                    </span>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-black"
                        :class="statusClass(customer.status)"
                    >
                        {{ formatStatus(customer.status) }}
                    </span>
                </div>

                <div class="mt-5">
                    <h3
                        class="line-clamp-2 text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ fullName(customer) }}
                    </h3>

                    <p class="mt-2 truncate text-sm text-slate-500">
                        {{
                            customer.merchant?.business_name ||
                            'Merchant unavailable'
                        }}
                    </p>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Email
                        </dt>

                        <dd
                            class="max-w-[65%] truncate text-right font-semibold"
                        >
                            {{ customer.email || 'N/A' }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Phone
                        </dt>

                        <dd class="font-semibold">
                            {{ customer.phone || 'N/A' }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            KYC Status
                        </dt>

                        <dd>
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-black"
                                :class="kycClass(customer.kyc_status)"
                            >
                                {{ formatKyc(customer.kyc_status) }}
                            </span>
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Virtual Accounts
                        </dt>

                        <dd class="font-black">
                            {{
                                customer.virtual_accounts_count ??
                                customer.virtual_accounts?.length ??
                                0
                            }}
                        </dd>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">
                            Customer ID
                        </dt>

                        <dd class="font-mono text-xs font-bold">
                            #{{ customer.id }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-auto pt-6">
                    <button
                        type="button"
                        class="flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                        @click="openCustomer(customer)"
                    >
                        View customer
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
                of {{ pagination.total || 0 }} customers
            </p>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                    :disabled="pagination.current_page <= 1"
                    @click="fetchCustomers(pagination.current_page - 1)"
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
                    class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-bold disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700"
                    :disabled="
                        pagination.current_page >=
                        pagination.last_page
                    "
                    @click="fetchCustomers(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </section>

        <!-- Customer modal -->
        <Teleport to="body">
            <div
                v-if="modalOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5"
                @click.self="closeModal"
            >
                <div
                    class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 shadow-2xl dark:border-slate-700 dark:bg-slate-950"
                >
                    <!-- Header -->
                    <header
                        class="flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-5 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="
                                        statusClass(
                                            selectedCustomer.status
                                        )
                                    "
                                >
                                    {{
                                        formatStatus(
                                            selectedCustomer.status
                                        )
                                    }}
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-black"
                                    :class="
                                        kycClass(
                                            selectedCustomer.kyc_status
                                        )
                                    "
                                >
                                    {{
                                        formatKyc(
                                            selectedCustomer.kyc_status
                                        )
                                    }}
                                </span>
                            </div>

                            <h3 class="mt-3 truncate text-2xl font-black">
                                {{ fullName(selectedCustomer) }}
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Customer ID: #{{ selectedCustomer.id }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-xl text-slate-500 transition hover:bg-slate-100 dark:hover:bg-slate-800"
                            @click="closeModal"
                        >
                            ✕
                        </button>
                    </header>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-7">
                        <div
                            v-if="detailsLoading"
                            class="py-24 text-center text-slate-500"
                        >
                            Loading customer details…
                        </div>

                        <div v-else class="space-y-6">
                            <section class="grid gap-5 lg:grid-cols-2">
                                <!-- Customer -->
                                <article class="customer-detail-card">
                                    <h4 class="customer-detail-heading">
                                        Customer Information
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="First Name"
                                            :value="
                                                selectedCustomer.first_name
                                            "
                                        />

                                        <DetailRow
                                            label="Last Name"
                                            :value="
                                                selectedCustomer.last_name
                                            "
                                        />

                                        <DetailRow
                                            label="Email"
                                            :value="
                                                selectedCustomer.email
                                            "
                                        />

                                        <DetailRow
                                            label="Phone"
                                            :value="
                                                selectedCustomer.phone
                                            "
                                        />

                                        <DetailRow
                                            label="Created"
                                            :value="
                                                formatDateTime(
                                                    selectedCustomer.created_at
                                                )
                                            "
                                        />
                                    </dl>
                                </article>

                                <!-- Merchant -->
                                <article class="customer-detail-card">
                                    <h4 class="customer-detail-heading">
                                        Merchant Information
                                    </h4>

                                    <dl class="mt-5 space-y-4">
                                        <DetailRow
                                            label="Business Name"
                                            :value="
                                                selectedCustomer.merchant
                                                    ?.business_name
                                            "
                                        />

                                        <DetailRow
                                            label="Business Email"
                                            :value="
                                                selectedCustomer.merchant
                                                    ?.business_email
                                            "
                                        />

                                        <DetailRow
                                            label="Business Phone"
                                            :value="
                                                selectedCustomer.merchant
                                                    ?.business_phone
                                            "
                                        />

                                        <DetailRow
                                            label="Merchant ID"
                                            :value="
                                                selectedCustomer.merchant_id
                                                    ? `#${selectedCustomer.merchant_id}`
                                                    : 'N/A'
                                            "
                                        />
                                    </dl>
                                </article>
                            </section>

                            <!-- Verification -->
                            <article class="customer-detail-card">
                                <div
                                    class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div>
                                        <h4 class="customer-detail-heading">
                                            KYC Verification
                                        </h4>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Identity and account verification status.
                                        </p>
                                    </div>

                                    <span
                                        class="w-fit rounded-full px-3 py-1.5 text-xs font-black"
                                        :class="
                                            kycClass(
                                                selectedCustomer.kyc_status
                                            )
                                        "
                                    >
                                        {{
                                            formatKyc(
                                                selectedCustomer.kyc_status
                                            )
                                        }}
                                    </span>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <DetailBox
                                        label="NIN"
                                        :value="
                                            maskSensitive(
                                                selectedCustomer.nin
                                            )
                                        "
                                    />

                                    <DetailBox
                                        label="BVN"
                                        :value="
                                            maskSensitive(
                                                selectedCustomer.bvn
                                            )
                                        "
                                    />
                                </div>
                            </article>

                            <!-- Virtual accounts -->
                            <article class="customer-detail-card">
                                <div
                                    class="flex items-center justify-between gap-4"
                                >
                                    <div>
                                        <h4 class="customer-detail-heading">
                                            Virtual Accounts
                                        </h4>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Accounts assigned to this customer.
                                        </p>
                                    </div>

                                    <span
                                        class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-black text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{
                                            selectedCustomer.virtual_accounts
                                                ?.length || 0
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="
                                        selectedCustomer.virtual_accounts
                                            ?.length
                                    "
                                    class="mt-5 grid gap-4 md:grid-cols-2"
                                >
                                    <div
                                        v-for="account in selectedCustomer.virtual_accounts"
                                        :key="account.id"
                                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950"
                                    >
                                        <div
                                            class="flex items-start justify-between gap-3"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate font-black"
                                                >
                                                    {{
                                                        account.bank_name ||
                                                        account.provider ||
                                                        'Virtual Account'
                                                    }}
                                                </p>

                                                <p
                                                    class="mt-2 font-mono text-lg font-black tracking-wider"
                                                >
                                                    {{
                                                        account.account_number ||
                                                        'N/A'
                                                    }}
                                                </p>

                                                <p
                                                    class="mt-1 truncate text-sm text-slate-500"
                                                >
                                                    {{
                                                        account.account_name ||
                                                        'Account name unavailable'
                                                    }}
                                                </p>
                                            </div>

                                            <span
                                                class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                                :class="
                                                    statusClass(
                                                        account.status
                                                    )
                                                "
                                            >
                                                {{
                                                    formatStatus(
                                                        account.status
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-else
                                    class="mt-5 rounded-2xl bg-slate-50 p-7 text-center text-sm text-slate-500 dark:bg-slate-950"
                                >
                                    No virtual account is linked to this customer.
                                </p>
                            </article>
                        </div>
                    </div>

                    <!-- Actions -->
                    <footer
                        class="border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-7"
                    >
                        <div
                            class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <button
                                type="button"
                                class="rounded-xl px-5 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                :disabled="processing"
                                @click="closeModal"
                            >
                                Close
                            </button>

                            <button
                                v-if="
                                    selectedCustomer.status === 'blocked'
                                "
                                type="button"
                                class="rounded-xl bg-emerald-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:opacity-50"
                                :disabled="processing"
                                @click="updateCustomerAccess('unblock')"
                            >
                                {{
                                    processing
                                        ? 'Processing…'
                                        : 'Unblock customer'
                                }}
                            </button>

                            <button
                                v-else
                                type="button"
                                class="rounded-xl border border-red-200 bg-red-50 px-6 py-3 text-sm font-black text-red-700 transition hover:bg-red-100 disabled:opacity-50 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
                                :disabled="processing"
                                @click="updateCustomerAccess('block')"
                            >
                                {{
                                    processing
                                        ? 'Processing…'
                                        : 'Block customer'
                                }}
                            </button>
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
                        'rounded-2xl bg-slate-50 p-4 dark:bg-slate-950',
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
                                'mt-2 break-words font-mono text-sm font-black',
                        },
                        props.value || 'N/A'
                    ),
                ]
            );
    },
});

export default {
    name: 'AdminCustomers',

    components: {
        DetailRow,
        DetailBox,
    },

    setup() {
        const loading = ref(false);
        const detailsLoading = ref(false);
        const processing = ref(false);
        const modalOpen = ref(false);

        const customers = ref([]);
        const selectedCustomer = ref({});

        const stats = ref({
            total: 0,
            active: 0,
            blocked: 0,
            verified: 0,
            pending_kyc: 0,
            rejected_kyc: 0,
        });

        const filters = ref({
            search: '',
            status: '',
            kyc_status: '',
        });

        const pagination = ref({
            current_page: 1,
            last_page: 1,
            from: 0,
            to: 0,
            total: 0,
        });

        const successMessage = ref('');
        const errorMessage = ref('');

        const summaries = computed(() => [
            {
                label: 'Total Customers',
                value: stats.value.total,
                icon: '👥',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Active Customers',
                value: stats.value.active,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
            {
                label: 'Verified KYC',
                value: stats.value.verified,
                icon: '🪪',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'Blocked Customers',
                value: stats.value.blocked,
                icon: '!',
                iconClass:
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
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

        const fetchCustomers = async (page = 1) => {
            loading.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.get(
                    '/api/v1/admin/customers',
                    {
                        params: buildParams(page),
                    }
                );

                const paginator = response.data?.data || {};

                customers.value = Array.isArray(paginator.data)
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
                    'The customers could not be loaded.';

                customers.value = [];
            } finally {
                loading.value = false;
            }
        };

        const fetchStats = async () => {
            try {
                const response = await axios.get(
                    '/api/v1/admin/customers/stats'
                );

                stats.value = {
                    ...stats.value,
                    ...(response.data?.data || {}),
                };
            } catch (error) {
                console.error(
                    'Customer statistics failed:',
                    error.response?.data || error
                );
            }
        };

        const refreshPage = async () => {
            await Promise.all([
                fetchCustomers(pagination.value.current_page),
                fetchStats(),
            ]);
        };

        const applyFilters = async () => {
            await fetchCustomers(1);
        };

        const resetFilters = async () => {
            filters.value = {
                search: '',
                status: '',
                kyc_status: '',
            };

            await fetchCustomers(1);
        };

        const openCustomer = async (customer) => {
            selectedCustomer.value = customer;
            modalOpen.value = true;
            detailsLoading.value = true;
            successMessage.value = '';
            errorMessage.value = '';

            document.body.style.overflow = 'hidden';

            try {
                const response = await axios.get(
                    `/api/v1/admin/customers/${customer.id}`
                );

                selectedCustomer.value =
                    response.data?.data || customer;
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The customer details could not be loaded.';
            } finally {
                detailsLoading.value = false;
            }
        };

        const closeModal = () => {
            if (processing.value) {
                return;
            }

            modalOpen.value = false;
            selectedCustomer.value = {};

            document.body.style.overflow = '';
        };

        const updateCustomerAccess = async (action) => {
            if (
                !selectedCustomer.value?.id ||
                processing.value
            ) {
                return;
            }

            const customerName =
                fullName(selectedCustomer.value);

            const confirmed = await verificationModal.confirm({
                title: action === 'block' ? 'Block customer?' : 'Unblock customer?',
                message: action === 'block' ? `Block ${customerName}?` : `Unblock ${customerName}?`,
                confirmText: action === 'block' ? 'Block customer' : 'Unblock customer',
                danger: action === 'block',
            });

            if (!confirmed) {
                return;
            }

            processing.value = true;
            errorMessage.value = '';
            successMessage.value = '';

            try {
                const response = await axios.post(
                    `/api/v1/admin/customers/${selectedCustomer.value.id}/${action}`
                );

                selectedCustomer.value = {
                    ...selectedCustomer.value,
                    ...(response.data?.data || {}),
                };

                successMessage.value =
                    response.data?.message ||
                    'Customer updated successfully.';

                await Promise.all([
                    fetchCustomers(
                        pagination.value.current_page
                    ),
                    fetchStats(),
                ]);
            } catch (error) {
                errorMessage.value =
                    error.response?.data?.message ||
                    'The customer could not be updated.';
            } finally {
                processing.value = false;
            }
        };

        const fullName = (customer) => {
            return [
                customer?.first_name,
                customer?.last_name,
            ]
                .filter(Boolean)
                .join(' ')
                .trim() || 'Unnamed Customer';
        };

        const customerInitials = (customer) => {
            return fullName(customer)
                .split(' ')
                .filter(Boolean)
                .map((word) => word.charAt(0))
                .join('')
                .slice(0, 2)
                .toUpperCase();
        };

        const formatStatus = (status) => {
            const labels = {
                active: 'Active',
                inactive: 'Inactive',
                blocked: 'Blocked',
                enabled: 'Active',
                disabled: 'Inactive',
            };

            return labels[status] || status || 'Inactive';
        };

        const statusClass = (status) => {
            if (
                [
                    'active',
                    'enabled',
                ].includes(status)
            ) {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (status === 'blocked') {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
        };

        const formatKyc = (status) => {
            const labels = {
                pending: 'Pending KYC',
                verified: 'Verified KYC',
                rejected: 'Rejected KYC',
            };

            return labels[status] || status || 'Pending KYC';
        };

        const kycClass = (status) => {
            if (status === 'verified') {
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
            }

            if (status === 'rejected') {
                return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300';
            }

            return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
        };

        const maskSensitive = (value) => {
            const text = String(value || '');

            if (!text) {
                return 'Not provided';
            }

            if (text.length <= 4) {
                return '••••';
            }

            return `${text.slice(0, 3)}${'•'.repeat(
                Math.max(text.length - 5, 4)
            )}${text.slice(-2)}`;
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

        const handleKeydown = (event) => {
            if (
                event.key === 'Escape' &&
                modalOpen.value &&
                !processing.value
            ) {
                closeModal();
            }
        };

        onMounted(async () => {
            window.addEventListener(
                'keydown',
                handleKeydown
            );

            await Promise.all([
                fetchCustomers(),
                fetchStats(),
            ]);
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
            customers,
            selectedCustomer,
            filters,
            pagination,
            successMessage,
            errorMessage,
            summaries,
            fetchCustomers,
            refreshPage,
            applyFilters,
            resetFilters,
            openCustomer,
            closeModal,
            updateCustomerAccess,
            fullName,
            customerInitials,
            formatStatus,
            statusClass,
            formatKyc,
            kycClass,
            maskSensitive,
            formatDateTime,
        };
    },
};
</script>

<style scoped>
.customer-detail-card {
    border: 1px solid rgb(226 232 240);
    border-radius: 1.5rem;
    background: white;
    padding: 1.25rem;
    box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
}

.customer-detail-heading {
    font-size: 1rem;
    font-weight: 900;
    color: rgb(15 23 42);
}

:global(.dark) .customer-detail-card {
    border-color: rgb(30 41 59);
    background: rgb(15 23 42);
}

:global(.dark) .customer-detail-heading {
    color: white;
}
</style>