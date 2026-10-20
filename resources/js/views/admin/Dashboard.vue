<template>
    <div class="space-y-6">
        <!-- Welcome -->
        <section
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <p
                    class="text-xs font-black uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400"
                >
                    Platform overview
                </p>

                <h2
                    class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl"
                >
                    {{ greeting }}, {{ firstName }}
                </h2>

                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    Monitor merchants, businesses, transactions and platform operations.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    :disabled="loading"
                    @click="fetchDashboard"
                >
                    {{ loading ? 'Refreshing…' : '↻ Refresh data' }}
                </button>

                <router-link
                    to="/admin-panel/business-activations"
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                >
                    Review activations
                </router-link>
            </div>
        </section>

        <!-- Error -->
        <section
            v-if="errorMessage"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300"
        >
            {{ errorMessage }}
        </section>

        <!-- Main statistics -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="stat in primaryStats"
                :key="stat.label"
                class="group flex min-h-[160px] flex-col justify-between rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl"
                        :class="stat.iconClass"
                    >
                        {{ stat.icon }}
                    </span>

                    <span
                        class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider"
                        :class="stat.badgeClass"
                    >
                        {{ stat.badge }}
                    </span>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                        {{ stat.label }}
                    </p>

                    <p class="mt-1 text-3xl font-black text-slate-950 dark:text-white">
                        {{ stat.value }}
                    </p>
                </div>
            </article>
        </section>

        <!-- Financial overview -->
        <section
            class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-violet-700 to-purple-800 p-6 text-white shadow-2xl shadow-indigo-900/20 sm:p-7"
        >
            <div
                class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full border-[48px] border-white/5"
            ></div>

            <div
                class="pointer-events-none absolute -bottom-28 left-1/3 h-72 w-72 rounded-full bg-fuchsia-400/10 blur-3xl"
            ></div>

            <div class="relative z-10">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-indigo-100">
                            Platform financial overview
                        </p>

                        <h3 class="mt-2 text-3xl font-black sm:text-4xl">
                            ₦{{ formatCurrency(totalTransactionValue) }}
                        </h3>

                        <p class="mt-1 text-sm text-indigo-100">
                            Total processed transaction value
                        </p>
                    </div>

                    <router-link
                        to="/admin-panel/reports"
                        class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white/15 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/25"
                    >
                        View detailed reports →
                    </router-link>
                </div>

                <div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">
                            Today revenue
                        </p>

                        <p class="mt-2 text-xl font-black">
                            ₦{{ formatCurrency(todayRevenue) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">
                            Wallet balances
                        </p>

                        <p class="mt-2 text-xl font-black">
                            ₦{{ formatCurrency(totalWalletBalance) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">
                            Pending settlements
                        </p>

                        <p class="mt-2 text-xl font-black">
                            ₦{{ formatCurrency(pendingSettlements) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-200">
                            Successful transactions
                        </p>

                        <p class="mt-2 text-xl font-black">
                            {{ successfulTransactions }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Operational cards -->
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <router-link
                v-for="item in operationalStats"
                :key="item.label"
                :to="item.route"
                class="flex min-h-[135px] items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-900"
            >
                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl"
                    :class="item.iconClass"
                >
                    {{ item.icon }}
                </span>

                <span>
                    <span class="block text-2xl font-black text-slate-950 dark:text-white">
                        {{ item.value }}
                    </span>

                    <span class="mt-1 block text-sm font-semibold text-slate-500">
                        {{ item.label }}
                    </span>
                </span>
            </router-link>
        </section>

        <!-- Activity area -->
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(340px,1fr)]">
            <!-- Recent business activations -->
            <article
                class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6"
                >
                    <div>
                        <h3 class="text-lg font-black">
                            Recent business applications
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Latest merchant business activation submissions.
                        </p>
                    </div>

                    <router-link
                        to="/admin-panel/business-activations"
                        class="text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        View all
                    </router-link>
                </div>

                <div v-if="loading" class="py-12 text-center text-sm text-slate-500">
                    Loading applications…
                </div>

                <div
                    v-else-if="recentActivations.length === 0"
                    class="py-12 text-center"
                >
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl dark:bg-slate-800"
                    >
                        🏢
                    </span>

                    <p class="mt-4 font-bold">
                        No business applications yet
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        New activation requests will appear here.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr
                                class="border-b border-slate-200 text-left text-[11px] font-black uppercase tracking-wider text-slate-400 dark:border-slate-800"
                            >
                                <th class="px-5 py-3 sm:px-6">
                                    Business
                                </th>

                                <th class="px-5 py-3">
                                    Type
                                </th>

                                <th class="px-5 py-3">
                                    Status
                                </th>

                                <th class="px-5 py-3">
                                    Submitted
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="activation in recentActivations"
                                :key="activation.id"
                                class="border-b border-slate-100 last:border-0 dark:border-slate-800"
                            >
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-bold text-slate-950 dark:text-white">
                                        {{ activation.business_name || 'Untitled business' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ activation.business_email || activation.user?.email || 'No email' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4">
                                    <span class="text-sm font-semibold capitalize">
                                        {{ activation.business_type || 'starter' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="statusClass(activation.status)"
                                    >
                                        {{ formatStatus(activation.status) }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ formatDate(activation.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <!-- Recent transactions -->
            <article
                class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-5 dark:border-slate-800"
                >
                    <div>
                        <h3 class="text-lg font-black">
                            Latest transactions
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Most recent platform activity.
                        </p>
                    </div>

                    <router-link
                        to="/admin-panel/transactions"
                        class="text-sm font-bold text-indigo-600 hover:underline dark:text-indigo-400"
                    >
                        View all
                    </router-link>
                </div>

                <div v-if="loading" class="py-12 text-center text-sm text-slate-500">
                    Loading transactions…
                </div>

                <div
                    v-else-if="recentTransactions.length === 0"
                    class="py-12 text-center"
                >
                    <span
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl dark:bg-slate-800"
                    >
                        ↻
                    </span>

                    <p class="mt-4 font-bold">
                        No transactions yet
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Transaction activity will appear here.
                    </p>
                </div>

                <div v-else class="divide-y divide-slate-100 px-5 dark:divide-slate-800">
                    <div
                        v-for="transaction in recentTransactions"
                        :key="transaction.id || transaction.reference"
                        class="flex items-center justify-between gap-4 py-4"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    isSuccessfulTransaction(transaction)
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                "
                            >
                                {{
                                    isSuccessfulTransaction(transaction)
                                        ? '✓'
                                        : '◷'
                                }}
                            </span>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">
                                    {{
                                        transaction.reference ||
                                        transaction.description ||
                                        'Transaction'
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{ formatDate(transaction.created_at) }}
                                </p>
                            </div>
                        </div>

                        <p class="shrink-0 text-sm font-black text-slate-950 dark:text-white">
                            ₦{{ formatCurrency(transaction.amount) }}
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <!-- System health -->
        <section
            class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black">
                        System status
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ summary.health ? 'Last application health check: '+new Date(summary.health.checked_at).toLocaleString() : 'Application health has not been checked. Run the health command or enable the scheduler.' }}
                    </p>
                </div>

                <span
                    class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                >
                    <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                    {{ summary.health_status || 'not_monitored' }}
                </span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="service in systemServices"
                    :key="service.name"
                    class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <div>
                        <p class="font-bold">
                            {{ service.name }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ service.description }}
                        </p>
                    </div>

                    <span class="h-3 w-3 rounded-full bg-slate-400"></span>
                </div>
            </div>
        </section>
    </div>
</template>

<script>
import {
    computed,
    onMounted,
    ref,
} from 'vue';

import axios from 'axios';

export default {
    name: 'AdminDashboard',

    setup() {
        const user = JSON.parse(
            localStorage.getItem('admin') || '{}'
        );

        const loading = ref(false);
        const errorMessage = ref('');

        const summary = ref({});
        const merchants = ref([]);
        const transactions = ref([]);
        const activations = ref([]);
        const payouts = ref([]);
        const customers = ref([]);

        const firstName = computed(() => {
            return (user.name || 'Administrator')
                .split(' ')[0];
        });

        const greeting = computed(() => {
            const hour = new Date().getHours();

            if (hour < 12) {
                return 'Good morning';
            }

            if (hour < 17) {
                return 'Good afternoon';
            }

            return 'Good evening';
        });

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-NG', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(amount || 0));
        };

        const formatDate = (date) => {
            if (!date) {
                return 'N/A';
            }

            return new Date(date).toLocaleDateString('en-NG', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        const normalizeCollection = (response) => {
            const data = response?.data?.data;

            if (Array.isArray(data)) {
                return data;
            }

            if (Array.isArray(data?.data)) {
                return data.data;
            }

            return [];
        };

        const totalBusinesses = computed(() => summary.value.total_businesses ?? 0);

        const pendingActivations = computed(() => summary.value.pending_activations ?? 0);

        const approvedBusinesses = computed(() => summary.value.approved_businesses ?? 0);

        const successfulTransactions = computed(() => summary.value.successful_transactions ?? 0);

        const totalTransactionValue = computed(() => summary.value.total_transaction_value ?? 0);

        const todayRevenue = computed(() => summary.value.today_revenue ?? 0);

        const totalWalletBalance = computed(() => summary.value.total_wallet_balance ?? 0);

        const pendingSettlements = computed(() => summary.value.pending_settlements ?? 0);

        const primaryStats = computed(() => [
            {
                label: 'Total Merchants',
                value: summary.value.total_merchants ?? 0,
                icon: '🏪',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                badge: 'All time',
                badgeClass:
                    'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
            },
            {
                label: 'Total Businesses',
                value: totalBusinesses.value,
                icon: '🏢',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
                badge: 'Registered',
                badgeClass:
                    'bg-violet-50 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
            },
            {
                label: 'Pending Activations',
                value: pendingActivations.value,
                icon: '◷',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                badge: 'Action',
                badgeClass:
                    'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                label: 'Approved Businesses',
                value: approvedBusinesses.value,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                badge: 'Verified',
                badgeClass:
                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            },
        ]);

        const operationalStats = computed(() => [
            {
                label: 'Total Transactions',
                value: summary.value.total_transactions ?? 0,
                icon: '↻',
                iconClass:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                route: '/admin-panel/transactions',
            },
            {
                label: 'Customers',
                value: summary.value.total_customers ?? 0,
                icon: '♟',
                iconClass:
                    'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
                route: '/admin-panel/customers',
            },
            {
                label: 'Pending Payouts',
                value: summary.value.pending_payouts ?? 0,
                icon: '⇧',
                iconClass:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                route: '/admin-panel/payouts',
            },
            {
                label: 'Successful Transactions',
                value: successfulTransactions.value,
                icon: '✓',
                iconClass:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                route: '/admin-panel/transactions',
            },
        ]);

        const recentActivations = computed(() => {
            return activations.value.slice(0, 5);
        });

        const recentTransactions = computed(() => {
            return transactions.value.slice(0, 5);
        });

        const systemServices = [
            {
                name: 'API Gateway',
                description: 'Requests processing',
            },
            {
                name: 'Database',
                description: 'MariaDB connection',
            },
            {
                name: 'Queue Workers',
                description: 'Background processing',
            },
            {
                name: 'Notification Service',
                description: 'Email and alerts',
            },
        ];

        function isSuccessfulTransaction(transaction) {
            return [
                'success',
                'successful',
                'completed',
                'paid',
                'approved',
            ].includes(
                String(transaction.status || '').toLowerCase()
            );
        }

        const formatStatus = (status) => {
            const labels = {
                pending: 'Pending',
                under_review: 'Under review',
                approved: 'Approved',
                rejected: 'Rejected',
            };

            return labels[status] || status || 'Pending';
        };

        const statusClass = (status) => {
            const classes = {
                pending:
                    'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
                under_review:
                    'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                approved:
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                rejected:
                    'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
            };

            return (
                classes[status] ||
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
            );
        };

        const fetchDashboard = async () => {
            loading.value = true;
            errorMessage.value = '';

            const requests = await Promise.allSettled([
                axios.get('/api/v1/admin/merchants'),
                axios.get('/api/v1/admin/transactions'),
                axios.get('/api/v1/admin/business-activations'),
                axios.get('/api/v1/admin/payouts'),
                axios.get('/api/v1/admin/customers'),
                axios.get('/api/v1/admin/dashboard/summary'),
            ]);

            const [
                merchantsResult,
                transactionsResult,
                activationsResult,
                payoutsResult,
                customersResult,
                summaryResult,
            ] = requests;

            if (summaryResult.status === 'fulfilled') summary.value = summaryResult.value.data?.data || {};

            if (merchantsResult.status === 'fulfilled') {
                merchants.value = normalizeCollection(
                    merchantsResult.value
                );
            }

            if (transactionsResult.status === 'fulfilled') {
                transactions.value = normalizeCollection(
                    transactionsResult.value
                );
            }

            if (activationsResult.status === 'fulfilled') {
                activations.value = normalizeCollection(
                    activationsResult.value
                );
            }

            if (payoutsResult.status === 'fulfilled') {
                payouts.value = normalizeCollection(
                    payoutsResult.value
                );
            }

            if (customersResult.status === 'fulfilled') {
                customers.value = normalizeCollection(
                    customersResult.value
                );
            }

            const failedRequests = requests.filter(
                (result) => result.status === 'rejected'
            );

            if (failedRequests.length) {
                errorMessage.value =
                    'Some dashboard sections could not be loaded. The available information is shown below.';

                console.error(
                    'Admin dashboard request errors:',
                    failedRequests.map(
                        (result) =>
                            result.reason?.response?.data ||
                            result.reason
                    )
                );
            }

            loading.value = false;
        };

        onMounted(fetchDashboard);

        return {
            loading,
            errorMessage,
            firstName,
            greeting,
            primaryStats,
            operationalStats,
            recentActivations,
            recentTransactions,
            totalTransactionValue,
            todayRevenue,
            totalWalletBalance,
            pendingSettlements,
            successfulTransactions,
            systemServices, summary,
            formatCurrency,
            formatDate,
            formatStatus,
            statusClass,
            isSuccessfulTransaction,
            fetchDashboard,
        };
    },
};
</script>